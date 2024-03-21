<?php

namespace App\Controller;

use App\DTO\UserProfileDTO;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\PostRepository;
use App\Service\FileUploader;
use App\Service\ProfileManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile/{id}", name="app_profile_show", requirements={"id"="\d+"})
     */
    public function show(User $user): Response {
        $userProfile = $user->getUserProfile();
        if ($userProfile) {
            $userProfile = UserProfileDTO::createFromEntity($userProfile);
        }
        return $this->render('settings_profile/show.html.twig', [
            'profile' => $userProfile
        ]);
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
                $this->addFlash('success', 'Your user profile settings were saved.');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred. Your user profile could not be updated.');
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
