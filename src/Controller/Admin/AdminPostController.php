<?php

// src/Controller/Admin/AdminPostController.php
namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class AdminPostController extends AbstractController
{
    /**
     * @Route("/posts", name="admin_post_index")
     */
    public function index(PostRepository $postRepository)
    {
        $posts = $postRepository->findAllWithComments();
        // Fetch posts from the database
        // Render the template with posts data
        return $this->render('admin/posts/index.html.twig', [
            'posts' => $posts
        ]);
    }

    /**
     * @Route("/posts/{post}", name="admin_post_show")
     */
    public function show(
        Post $post,
        Request $request,
        PostRepository $postRepository,
        CommentRepository $commentRepository
    )
    {
        $commentForm = $this->createForm(CommentType::class, new Comment());

        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $comment->setAuthor($this->getUser());
            $comment->setPost($post);
            $commentRepository->add($comment, true);
            $this->addFlash('success', 'Your comment has been added.');
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView()
        ]);
    }

    /**
     * @Route("/posts/{post}/edit", name="admin_post_edit")
     */
    public function edit(Post $post, Request $request, PostRepository $postRepository)
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->add($form->getData(), true);
            $this->addFlash('success', 'Post has been updated.');
            return $this->redirectToRoute('admin_post_index');
        }

        return $this->renderForm(
            'admin/posts/edit.html.twig',
            ['form' => $form, 'post' => $post]
        );
    }

    /**
     * @Route("/posts/{post}/delete", name="admin_post_delete")
     */
    public function delete(Post $post, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post has been deleted.');

        return $this->redirectToRoute('admin_post_index');
    }

    // Add methods for creating, editing, and deleting posts
}