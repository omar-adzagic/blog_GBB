<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class LikeController extends AbstractController
{
    /**
     * @Route("/like/{id}", name="app_like")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function like(
        Post $post,
        PostRepository $posts,
        Request $request
    ): Response {
        $currentUser = $this->getUser();
        $post->addLikedBy($currentUser);
        $posts->add($post, true);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/unlike/{id}", name="app_unlike")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function unlike(
        Post $post,
        PostRepository $posts,
        Request $request
    ): Response {
        $currentUser = $this->getUser();
        $post->removeLikedBy($currentUser);
        $posts->add($post, true);

        return $this->redirect($request->headers->get('referer'));
    }
}
