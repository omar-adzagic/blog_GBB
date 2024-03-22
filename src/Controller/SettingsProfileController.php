<?php

namespace App\Controller;

use App\DTO\UserFavoriteDTO;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\UserFavoriteRepository;
use App\Repository\UserLikeRepository;
use App\Repository\UserRepository;
use App\Service\ContentTranslationService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsProfileController extends AbstractController
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @Route("/profile", name="app_profile")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userProfile = $user->getUserProfile() ?? new UserProfile();

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $userProfile = $form->getData();
                $user->setUserProfile($userProfile);
                $entityManager->persist($user);
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

        $responseData = [
            'form' => $form->createView(),
            'tab' => 'basic',
            'templatePart' => 'settings_profile/_profile_form.html.twig'
        ];
        return $this->render('settings_profile/profile.html.twig', $responseData);
    }

    /**
     * @Route("/favorite-posts", name="app_favorite_posts")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function favoritePosts(
        UserFavoriteRepository $userFavoriteRepository,
        UserLikeRepository $userLikeRepository,
        ContentTranslationService $contentTranslationService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        $userFavorites = $userFavoriteRepository->findFavoritePostsByUserId($userId);
        $userFavoriteDTOs = UserFavoriteDTO::createFromEntities($userFavorites, $contentTranslationService);
        $userFavoritePostIds = array_map(function($userFavorite) {
            return $userFavorite->getPost()->getId();
        }, $userFavorites);

        $likedAndFavoredIdsMap = $userFavoriteRepository->findLikedAndFavoredPostsByUserId($userId, $userFavoritePostIds);
        $totalLikesMap = $userLikeRepository->countLikesForPostIds($userFavoritePostIds);
        foreach ($userFavoriteDTOs as $userFavoriteDTO) {
            $userFavoriteDTO->post->setLikesCount($totalLikesMap[$userFavoriteDTO->post->id]);
            $userFavoriteDTO->post->setIsFavorite(true);
            $userFavoriteDTO->post->setIsLiked($likedAndFavoredIdsMap[$userFavoriteDTO->post->id]);
        }

        $responseData = [
            'tab' => 'favorite-posts',
            'templatePart' => 'post/_favorite_posts.html.twig',
            'userFavorites' => $userFavoriteDTOs,
        ];
        return $this->render('settings_profile/profile.html.twig', $responseData);
    }

    /**
     * @Route("/user-activities", name="app_user_activities")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function userActivities(UserRepository $userRepository, TranslationService $translationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        $locale = $translationService->getSessionLocale();
        $activities = $userRepository->findUserActivities($userId, $locale);

        $responseData = [
            'tab' => 'user-activities',
            'templatePart' => 'settings_profile/user_activities.html.twig',
            'activities' => $activities,
        ];
        return $this->render('settings_profile/profile.html.twig', $responseData);
    }
}
