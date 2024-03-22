<?php

namespace App\Controller;

use App\DTO\UserProfileDTO;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\PostRepository;
use App\Service\ProfileManager;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @Route("/profile/{id}", name="app_profile_show", requirements={"id"="\d+"})
     */
    public function show(User $user): Response {
        $userProfile = $user->getUserProfile();
        if ($userProfile) {
            $userProfile = UserProfileDTO::createFromEntity($userProfile);
        }

        $responseData = ['profile' => $userProfile];
        return $this->render('settings_profile/show.html.twig', $responseData);
    }

    /**
     * @Route("/profile/edit/{id}", name="app_profile_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        User $user,
        Request $request,
        PostRepository $posts,
        EntityManagerInterface $entityManager,
        ProfileManager $profileManager
    ): Response {
        $existingUserProfile = $user->getUserProfile();
        $userProfile = $existingUserProfile ?? new UserProfile();

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $profile = $form->getData();
                $profileManager->saveProfile($profile, $existingUserProfile);
                $profileManager->saveProfileImage($form, $profile, $existingUserProfile);

                $user->setUserProfile($profile);
                $entityManager->flush();

                $entityManager->commit();
                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_user_profile', '{{action}}' => 'actions.saved']
                    )
                );
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_failed',
                        ['{{entity}}' => 'the_user_profile', '{{action}}' => 'actions.updated']
                    )
                );
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('settings_profile/edit.html.twig', [
            'user' => $user,
            'posts' => $posts->findAllByUser($user),
            'form' => $form->createView(),
        ]);
    }
}
