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
    /**
     * @Route("/favorite/{id}", name="app_favorite")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function addToFavorites(
        Post $post,
        Request $request,
        UserFavoriteRepository $userFavoriteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        // Check if the user already favored the post
        $alreadyFavored = $userFavoriteRepository->findOneBy(['user' => $currentUser, 'post' => $post]);
        if (!$alreadyFavored) {
            $userFavorite = new UserFavorite();
            $userFavorite->setUser($currentUser);
            $userFavorite->setPost($post);

            // EntityManager is used to persist the UserFavorite entity
            $entityManager->persist($userFavorite);
            $entityManager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }

    /**
     * @Route("/remove-favorite/{id}", name="app_remove_favorite")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function removeFromFavorites(
        Post $post,
        PostRepository $posts,
        Request $request,
        UserFavoriteRepository $userFavoriteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        // Find the specific UserFavorite instance for the current user and the post
        $userFavorite = $userFavoriteRepository->findOneBy([
            'user' => $currentUser,
            'post' => $post
        ]);

        // If a UserFavorite instance is found, remove it
        if ($userFavorite) {
            $entityManager->remove($userFavorite);
            $entityManager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }
}
