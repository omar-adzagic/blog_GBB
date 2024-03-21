<?php

namespace App\Controller\Api;

use App\DTO\PostDTO;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserFavoriteRepository;
use App\Repository\UserLikeRepository;
use App\Service\ContentTranslationService;
use App\Service\HelperService;
use App\Service\PaginationService;
use App\Service\TranslationService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class PostController extends AbstractController
{
    /**
     * @Route("/posts", name="get_posts", methods={"GET"})
     */
    public function getPosts(
        Request $request,
        PostRepository $postRepository,
        PaginatorInterface $paginator,
        UserLikeRepository $userLikeRepository,
        UserFavoriteRepository $userFavoriteRepository,
        TranslationService $translationService,
        ContentTranslationService $contentTranslationService,
        PaginationService $paginationService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;

        $title = $request->query->get('title', '');

        // Query to get all posts (or you might modify it to get a QueryBuilder instance)
        $queryBuilder = $postRepository->findAllWithCommentCountQueryBuilder($title);

        $limit = $request->query->getInt('limit', 6);
        $pagination = $paginationService->paginate($queryBuilder, $limit);

        $posts = $pagination->getItems();
        $postsDTOs = PostDTO::createFromEntities($posts, $contentTranslationService);
        $postIds = HelperService::getIdsFromDoctrine($posts);
        $totalLikesPostIdsMap = $userLikeRepository->countLikesForPostIds($postIds);
        $totalCommentsPostIdsMap = $postRepository->findCommentCountsByPostIds($postIds);

        $userFavoritePostIdsMap = [];
        $userLikePostIdsMap = [];
        if ($user) {
            $userFavoritePostIdsMap = $userFavoriteRepository->findFavoritePostsByPostIdsAndUserId($postIds, $userId);
            $userLikePostIdsMap = $postRepository->findLikedPostsByPostIdsAndUserId($postIds, $userId);
        }

        foreach ($postsDTOs as $postDTO) {
            $postDTO->setLikesCount($totalLikesPostIdsMap[$postDTO->id]);
            $postDTO->setCommentsCount($totalCommentsPostIdsMap[$postDTO->id]);
            if ($user) {
                $postDTO->setIsLiked($userLikePostIdsMap[$postDTO->id]);
                $postDTO->setIsFavorite(isset($userFavoritePostIdsMap[$postDTO->id]));
            }
        }

        $translations = $translationService->getPostIndexTranslations();

        $responseData = [
            'posts' => $postsDTOs,
            'total' => $pagination->getTotalItemCount(),
            'page' => $pagination->getCurrentPageNumber(),
            'limit' => $limit,
            'is_authenticated' => $this->isGranted('IS_AUTHENTICATED_FULLY'),
            'auth_id' => $userId,
            'translations' => $translations,
        ];

        return $this->json($responseData, Response::HTTP_OK);
    }
}
