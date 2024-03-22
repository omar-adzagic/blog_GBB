<?php

namespace App\Controller\Admin;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use App\Service\TranslationService;
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
    private $entityManager;
    private $translationService;
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslationService $translationService
    )
    {
        $this->entityManager = $entityManager;
        $this->translationService = $translationService;
    }

    /**
     * @Route("/users", name="admin_user_index")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function index(
        UserRepository $userRepository,
        PaginationService $paginationService
    ): Response
    {
        $userId = $this->getUser()->getId();
        $queryBuilder = $userRepository->findAllWithoutUserLatestQB($userId);
        $pagination = $paginationService->paginate($queryBuilder);
        $userDTOs = UserDTO::createFromEntities($pagination->getItems());
        $pagination->setItems($userDTOs);

        return $this->render('admin/users/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * @Route("/users/create", name="admin_user_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(Request $request, UserPasswordHasherInterface $userPasswordHasher)
    {
        $form = $this->createForm(UserType::class, new User());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user = $form->getData();
            if ($plainPassword !== null) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_success',
                    ['{{entity}}' => 'the_user', '{{action}}' => 'actions.created']
                )
            );

            return $this->redirectToRoute('admin_user_index');
        }

        $responseData = ['form' => $form];
        return $this->renderForm('admin/users/create.html.twig', $responseData);
    }

    /**
     * @Route("/users/{user}/edit", name="admin_user_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        User $user,
        UserPasswordHasherInterface $userPasswordHasher,
        Request $request
    )
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user = $form->getData();
            if ($plainPassword !== null) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_success',
                    ['{{entity}}' => 'the_user', '{{action}}' => 'actions.updated']
                )
            );

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
        $responseData = ['user' => $user];
        return $this->render('admin/users/show.html.twig', $responseData);
    }

    /**
     * @Route("/users/{user}/delete", name="admin_user_delete")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(User $user): RedirectResponse
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            $this->translationService->messageTranslate(
                'flash_messages.operation_success',
                ['{{entity}}' => 'the_user', '{{action}}' => 'actions.deleted']
            )
        );

        return $this->redirectToRoute('admin_user_index');
    }
}
