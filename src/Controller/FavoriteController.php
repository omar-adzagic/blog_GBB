<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\UserFavorite;
use App\Repository\PostRepository;
use App\Repository\UserFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteController extends AbstractController
{
    private $entityManager;
    private $userFavoriteRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserFavoriteRepository $userFavoriteRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->userFavoriteRepository = $userFavoriteRepository;
    }

    /**
     * @Route("/favorite/{id}", name="app_favorite")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function addToFavorites(Post $post, Request $request): Response {
        $currentUser = $this->getUser();

        // Check if the user already favored the post
        $alreadyFavored = $this->userFavoriteRepository->findOneBy([
            'user' => $currentUser,
            'post' => $post
        ]);
        if (!$alreadyFavored) {
            $userFavorite = new UserFavorite();
            $userFavorite->setUser($currentUser);
            $userFavorite->setPost($post);

            // EntityManager is used to persist the UserFavorite entity
            $this->entityManager->persist($userFavorite);
            $this->entityManager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }

    /**
     * @Route("/remove-favorite/{id}", name="app_remove_favorite")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function removeFromFavorites(Post $post, Request $request): Response {
        $currentUser = $this->getUser();

        // Find the specific UserFavorite instance for the current user and the post
        $userFavorite = $this->userFavoriteRepository->findOneBy([
            'user' => $currentUser,
            'post' => $post
        ]);

        // If a UserFavorite instance is found, remove it
        if ($userFavorite) {
            $this->entityManager->remove($userFavorite);
            $this->entityManager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }
}
