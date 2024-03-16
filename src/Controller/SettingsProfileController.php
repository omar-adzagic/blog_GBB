<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Form\ProfileImageType;
use App\Repository\PostRepository;
use App\Repository\UserFavoriteRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class SettingsProfileController extends AbstractController
{
    /**
     * @Route("/profile", name="app_profile")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function profile(Request $request, UserRepository $users): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userProfile = $user->getUserProfile() ?? new UserProfile();

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userProfile = $form->getData();
            $user->setUserProfile($userProfile);
            $users->add($user, true);
            $this->addFlash('success', 'Your user profile settings were saved.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('settings_profile/profile.html.twig', [
            'form' => $form->createView(),
            'tab' => 'basic',
            'templatePart' => 'settings_profile/_profile_form.html.twig'
        ]);
    }

    /**
     * @Route("/profile-image", name="app_profile_image")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function profileImage(Request $request, SluggerInterface $slugger, UserRepository $users): Response
    {
        $form = $this->createForm(ProfileImageType::class);
        /** @var User $user */
        $user = $this->getUser();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profileImageFile = $form->get('profileImage')->getData();

            if ($profileImageFile) {
                $originalFileName = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFileName);
                $newFileName = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();

                try {
                    $profileImageFile->move($this->getParameter('profiles_directory'), $newFileName);
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }

                $profile = $user->getUserProfile() ?? new UserProfile();
                $profile->setImage($newFileName);
                $user->setUserProfile($profile);
                $users->add($user, true);
                $this->addFlash('success', 'Your profile image was updated.');

                return $this->redirectToRoute('app_profile_image');
            }
        }

        return $this->render('settings_profile/profile_image.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/favorite-posts", name="app_favorite_posts")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function favoritePosts(
        Request $request,
        UserFavoriteRepository $userFavoriteRepository
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        $userFavorites = $userFavoriteRepository->findFavoritePostsByUserId($userId);

        return $this->render('settings_profile/profile.html.twig', [
            'tab' => 'favorite-posts',
            'templatePart' => 'post/_favorite_posts.html.twig',
            'userFavorites' => $userFavorites,
        ]);
    }

    /**
     * @Route("/user-activities", name="app_user_activities")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function userActivities(
        Request $request,
        UserRepository $userRepository,
        PostRepository $postRepository
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        $activities = $userRepository->findUserActivities($userId);

        return $this->render('settings_profile/profile.html.twig', [
            'tab' => 'user-activities',
            'templatePart' => 'settings_profile/user_activities.html.twig',
            'activities' => $activities,
        ]);
    }
}
