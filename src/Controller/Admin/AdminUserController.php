<?php

// src/Controller/Admin/AdminPostController.php
namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class AdminUserController extends AbstractController
{
    /**
     * @Route("/users", name="admin_user_index")
     */
    public function index(UserRepository $userRepository)
    {
        $userId = $this->getUser()->getId();
        $users = $userRepository->findAllWithoutUserLatest($userId);
        // Fetch users from the database
        // Render the template with users data
        return $this->render('admin/users/index.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/users/create", name="admin_user_create")
     */
    public function create(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository)
    {
        $form = $this->createForm(UserType::class, new User());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData(); // Access specific field data

            $user = $form->getData();
            // Assuming you have $userPasswordHasher injected or available in your method
            if ($plainPassword !== null) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            $userRepository->add($form->getData(), true);
            $this->addFlash('success', 'User has been created.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->renderForm(
            'admin/users/create.html.twig',
            ['form' => $form]
        );
    }

    /**
     * @Route("/users/{user}/edit", name="admin_user_edit")
     */
    public function edit(User $user, UserPasswordHasherInterface $userPasswordHasher, Request $request, UserRepository $userRepository)
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData(); // Access specific field data

            $user = $form->getData();
            // Assuming you have $userPasswordHasher injected or available in your method
            if ($plainPassword !== null) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            $userRepository->add($form->getData(), true);
            $this->addFlash('success', 'User has been updated.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->renderForm(
            'admin/users/edit.html.twig',
            ['form' => $form, 'user' => $user]
        );
    }

    /**
     * @Route("/users/{user}", name="admin_user_show")
     */
    public function show(User $user)
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/users/{user}/delete", name="admin_user_delete")
     */
    public function delete(User $user, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User has been deleted.');

        return $this->redirectToRoute('admin_user_index');
    }
}
