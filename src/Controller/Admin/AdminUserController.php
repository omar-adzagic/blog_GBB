<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 * @IsGranted("ROLE_ADMIN")
 */
class AdminUserController extends AbstractController
{
    /**
     * @Route("/users", name="admin_user_index")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function index(
        UserRepository $userRepository,
        PaginationService $paginationService
    )
    {
        $userId = $this->getUser()->getId();
        $queryBuilder = $userRepository->findAllWithoutUserLatestQB($userId);

        $pagination = $paginationService->paginate($queryBuilder);

        return $this->render('admin/users/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/users/create", name="admin_user_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository
    )
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        User $user,
        UserPasswordHasherInterface $userPasswordHasher,
        Request $request,
        UserRepository $userRepository
    )
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

        $responseData = [
            'form' => $form,
            'user' => $user
        ];
        return $this->renderForm('admin/users/edit.html.twig', $responseData);
    }

    /**
     * @Route("/users/{user}", name="admin_user_show")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/users/{user}/delete", name="admin_user_delete")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(User $user, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User has been deleted.');

        return $this->redirectToRoute('admin_user_index');
    }
}
