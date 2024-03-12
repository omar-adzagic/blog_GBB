<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    /**
     * @Route("/post", name="app_post")
     */
    public function index(PostRepository $posts): Response
    {
        return $this->render('post/index.html.twig', ['posts' => $posts->findAllWithComments()]);
    }

    /**
     * @Route("/post/top-liked", name="app_post_topliked")
     */
    public function topLiked(PostRepository $posts): Response
    {
        return $this->render('post/top_liked.html.twig', ['posts' => $posts]); // $posts->findAllWithMinLikes(2)
    }

    /**
     * @Route("/post/follows", name="app_post_follows")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function follows(PostRepository $posts): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        return $this->render('post/follows.html.twig', ['posts' => $posts]); // $posts->findAllByAuthors($currentUser->getFollows())
    }

    /**
     * @Route("/post/{post}", name="app_post_show")
     * @IsGranted("VIEW", subject="post")
     */
    public function showOne(Post $post): Response
    {
        return $this->render('post/show.html.twig', ['post' => $post]);
    }

    /**
     * @Route("/post/add", name="app_post_add", priority=2)
     * @IsGranted("ROLE_WRITER")
     */
    public function add(Request $request, PostRepository $posts): Response
    {
        $form = $this->createForm(PostType::class, new Post());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setAuthor($this->getUser());
            $posts->add($post, true);
            $this->addFlash('success', 'Your post has been added.');
            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('post/add.html.twig', ['form' => $form]);
    }

    /**
     * @Route("/post/{post}/edit", name="app_post_edit")
     * @IsGranted("EDIT", subject="post")
     */
    public function edit(Post $post, Request $request, PostRepository $posts): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $posts->add($form->getData(), true);
            $this->addFlash('success', 'Your post has been updated.');
            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('post/edit.html.twig', ['form' => $form, 'post' => $post]);
    }

    /**
     * @Route("/post/{post}/comment", name="app_post_comment")
     * @IsGranted("ROLE_COMMENTER")
     */
    public function addComment(Post $post, Request $request, CommentRepository $comments): Response
    {
        $form = $this->createForm(CommentType::class, new Comment());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setPost($post);
            $comment->setAuthor($this->getUser());
            $comments->add($comment, true);
            $this->addFlash('success', 'Your comment has been posted.');

            return $this->redirectToRoute('app_post_show', ['post' => $post->getId()]);
        }

        return $this->renderForm('post/comment.html.twig', ['form' => $form, 'post' => $post]);
    }
}
