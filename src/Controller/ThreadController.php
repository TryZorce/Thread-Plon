<?php

namespace App\Controller;

use App\Entity\Response as EntityResponse;
use App\Entity\Thread;
use App\Form\ResponseType;
use App\Form\ThreadType;
use App\Entity\Vote;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ThreadController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_thread')]
    public function index(): HttpFoundationResponse
    {
        $threadRepository = $this->entityManager->getRepository(Thread::class);
        $threads = $threadRepository->findAll();

        return $this->render('thread/index.html.twig', [
            'controller_name' => 'ThreadController',
            'threads' => $threads,
        ]);
    }


    #[Route('/thread/create', name: 'app_thread_create')]
    public function threadcreate(Request $request, EntityManagerInterface $entityManagerInterface)
    {
        $thread = new Thread();
        $form = $this->createForm(ThreadType::class, $thread);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread->setCreatedAt(new \DateTime());
            $thread->setUpdatedAt(new \DateTime());


            $user = $this->getUser();
            $thread->setUserId($user);


            $thread->setStatus("ouvert");
            $entityManagerInterface->persist($thread);
            $entityManagerInterface->flush();
        }

        return $this->render('thread/create.html.twig', [
            'controller_name' => 'ThreadController',
            'createform' => $form->createView()
        ]);
    }

    #[Route('/thread/{id}', name: 'app_thread_id')]
    public function threadDetails($id, Request $request, EntityManagerInterface $entityManager): HttpFoundationResponse
    {
        $threadRepository = $entityManager->getRepository(Thread::class);
        $thread = $threadRepository->findOneBy(['id' => $id]);
    
        // POUR LES REPONSES
    
        $response = new EntityResponse();
        $form = $this->createForm(ResponseType::class, $response);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $response->setCreatedAt(new \DateTime());
            $response->setUpdatedAt(new \DateTime());
    
            $user = $this->getUser();

            if ($user) {
                $response->setUserId($user);
            }
    
            $thread->setStatus("ouvert");
            $thread->addResponse($response); 
            $entityManager->persist($thread);
            $entityManager->persist($response); 
            $entityManager->flush();
    
            return new RedirectResponse($this->generateUrl('app_thread_id', ['id' => $id]));
        }
    
        // FIN DES REPONSES
    
        $votes = [];
        foreach ($thread->getResponses() as $response) {
            $votes[$response->getId()] = $entityManager->getRepository(Vote::class)->countVotesForResponse($response);
        }
    
        $user = $this->getUser();
        $userIsResponseCreator = [];
        foreach ($thread->getResponses() as $response) {
            $userIsResponseCreator[$response->getId()] = ($response->getUserId() === $user);
        }
    
        return $this->render('thread/details.html.twig', [
            'controller_name' => 'ThreadController',
            'responseform' => $form->createView(),
            'thread' => $thread,
            'votes' => $votes,
            'userIsResponseCreator' => $userIsResponseCreator,
        ]);        
    }
    
    #[Route('/thread/{threadId}/response/{responseId}/update', name: 'app_response_update')]
    public function responseUpdate($threadId, $responseId, Request $request, EntityManagerInterface $entityManager): HttpFoundationResponse
    {
        $responseRepository = $entityManager->getRepository(EntityResponse::class);
        $response = $responseRepository->find($responseId);
    
        if (!$response) {
            throw $this->createNotFoundException('Response not found');
        }
    
        $userIsResponseCreator = ($response->getUserId() === $this->getUser());
    
        if (!$userIsResponseCreator) {
            throw new AccessDeniedException('You are not allowed to update this response');
        }
    
        $form = $this->createForm(ResponseType::class, $response);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la réponse
            $response->setUpdatedAt(new \DateTime());
            $entityManager->flush();
                return $this->redirectToRoute('app_thread_id', ['id' => $threadId]);
        }
    
        return $this->render('thread/update_response.html.twig', [
            'form' => $form->createView(),
            'userIsResponseCreator' => $userIsResponseCreator,
        ]);
    }
    

    #[Route('/thread/{id}/update', name: 'app_thread_update')]
    public function threadUpdate($id, Request $request): HttpFoundationResponse
    {
        $threadRepository = $this->entityManager->getRepository(Thread::class);
        $thread = $threadRepository->find($id);

        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(ThreadType::class, $thread, [
            'isAdmin' => $isAdmin,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($thread);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_thread');
        }

        return $this->render('thread/update.html.twig', [
            'controller_name' => 'ThreadController',
            'updateform' => $form->createView(),
            'isAdmin' => $isAdmin,
        ]);
    }



    #[Route('/thread/{id}/delete', name: 'app_thread_delete')]
    public function threadDelete($id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $threadRepository = $entityManager->getRepository(Thread::class);
        $thread = $threadRepository->find($id);
    
        if (!$thread) {
            throw $this->createNotFoundException('Thread not found');
        }
        foreach ($thread->getResponses() as $response) {
            foreach ($response->getVotes() as $vote) {
                $entityManager->remove($vote);
            }
        }
        $entityManager->remove($thread);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_thread');
    }
    

    #[Route('/thread/{threadId}/response/{responseId}/vote-up', name: 'vote_up')]
    public function voteUp($threadId, $responseId, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $response = $entityManager->getRepository(EntityResponse::class)->find($responseId);

        if (!$response) {
            return $this->redirectToRoute('previous_page');
        }

        $vote = new Vote();
        $vote->setVote(1);
        $vote->setUserId($user);
        $vote->setResponseId($response);

        $entityManager->persist($vote);
        $entityManager->flush();

        return $this->redirectToRoute('app_thread_id', ['id' => $threadId]);
    }

    #[Route('/thread/{threadId}/response/{responseId}/vote-down', name: 'vote_down')]
    public function voteDown($threadId, $responseId, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $response = $entityManager->getRepository(EntityResponse::class)->find($responseId);

        if (!$response) {
            return $this->redirectToRoute('previous_page');
        }

        $vote = new Vote();
        $vote->setVote(-1);
        $vote->setUserId($user);
        $vote->setResponseId($response);

        $entityManager->persist($vote);
        $entityManager->flush();

        return $this->redirectToRoute('app_thread_id', ['id' => $threadId]);
    }
}
