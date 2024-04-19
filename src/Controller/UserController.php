<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/profil', name: 'app_user')]
    public function detail(): HttpFoundationResponse
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            throw $this->createNotFoundException('User not found');
        }
    
        return $this->render('user/profil.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user
        ]);
    }

    #[Route('/profil/{id}', name: 'app_user_detail')]
    public function profildetail($id): HttpFoundationResponse
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($id);
    
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
    
        return $this->render('user/detail.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user
        ]);
    }
    

    #[Route('/profil/{id}/update', name: 'app_user_update')]
    public function update($id, Request $request): HttpFoundationResponse
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($id);
    
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $user->setUpdatedAt(new \DateTime());
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(UserType::class, $user, [
            'isAdmin' => $isAdmin,
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
    
            return $this->redirectToRoute('app_user_detail', ['id' => $id]);
        }
    
        return $this->render('user/update.html.twig', [
            'controller_name' => 'UserController',
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/profil/{id}/delete', name: 'app_user_delete')]
    public function delete($id, EntityManagerInterface $entityManager): HttpFoundationResponse
    {
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($id);
    
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
    
        foreach ($user->getResponses() as $response) {
            foreach ($response->getVotes() as $vote) {
                $entityManager->remove($vote);
            }
            $entityManager->remove($response);
        }
    
        foreach ($user->getThreads() as $thread) {
            foreach ($thread->getResponses() as $response) {
                foreach ($response->getVotes() as $vote) {
                    $entityManager->remove($vote);
                }
                $entityManager->remove($response);
            }
            $entityManager->remove($thread);
        }
        foreach ($user->getVotes() as $vote) {
            $entityManager->remove($vote);
        }

        $entityManager->remove($user);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_thread');
    }
    
    
    
}
