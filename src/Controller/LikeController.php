<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\UserLike;
use App\Repository\UserLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LikeController extends AbstractController
{
    /**
     * @Route("/like/{id}", name="app_like", methods={"POST"})
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function like(
        Post $post,
        Request $request,
        UserLikeRepository $userLikeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        // Check if the user already liked the post
        if (!$userLikeRepository->findOneBy(['user' => $currentUser, 'post' => $post])) {
            $userLike = new UserLike();
            $userLike->setUser($currentUser);
            $userLike->setPost($post);

            // EntityManager is used to persist the UserLike entity
            $entityManager->persist($userLike);
            $entityManager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }

    /**
     * @Route("/unlike/{id}", name="app_unlike")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function unlike(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        // Find the specific UserLike instance for the current user and the post
        $userLike = $entityManager->getRepository(UserLike::class)->findOneBy([
            'user' => $currentUser,
            'post' => $post
        ]);

        // If a UserLike instance is found, remove it
        if ($userLike) {
            $entityManager->remove($userLike);
            $entityManager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }
}
