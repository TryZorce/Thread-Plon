<?php

namespace App\Controller;

use App\Entity\Response as EntityResponse;
use App\Entity\Thread;
use App\Form\ResponseType;
use App\Form\ThreadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

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
    public function threadDetails($id, Request $request, EntityManagerInterface $entityManagerInterface): HttpFoundationResponse
    {
        $threadRepository = $this->entityManager->getRepository(Thread::class);
        $thread = $threadRepository->findOneBy(['id' => $id]);




        // POUR LES REPONSES


        $response = new EntityResponse();
        $form = $this->createForm(ResponseType::class, $response);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response->setCreatedAt(new \DateTime());
            $response->setUpdatedAt(new \DateTime());


            $user = $this->getUser();

            // Assurez-vous que l'utilisateur est valide avant de définir l'identifiant
            if ($user) {
                // Utilisez la méthode setUserId() pour définir l'utilisateur
                $thread->setUserId($user);
            }



            $thread->setStatus("ouvert");
            $entityManagerInterface->persist($thread);
            $entityManagerInterface->flush();
        }

        // FIN DES REPONSES


        return $this->render('thread/details.html.twig', [
            'controller_name' => 'ThreadController',
            'responseform' => $form->createView(),
            'thread' => $thread,
        ]);
    }

    #[Route('/thread/{id}/update', name: 'app_thread_update')]
    public function threadUpdate($id, Request $request, EntityManagerInterface $entityManager)
    {

        $threadRepository = $entityManager->getRepository(Thread::class);
        $thread = $threadRepository->findOneBy(['id' => $id]);
        $form = $this->createForm(ThreadType::class, $thread);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread->setUpdatedAt(new \DateTime());
            $entityManager->persist($thread);
            $entityManager->flush();
        }

        return $this->render('thread/update.html.twig', [
            'controller_name' => 'ThreadController',
            'updateform' => $form->createView()
        ]);
    }


    #[Route('/thread/{id}/delete', name: 'app_thread_delete')]
    public function threadDelete($id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $threadRepository = $entityManager->getRepository(Thread::class);
        $thread = $threadRepository->find($id);
        $entityManager->remove($thread);
        $entityManager->flush();

        return $this->redirectToRoute('app_thread');
    }
}
