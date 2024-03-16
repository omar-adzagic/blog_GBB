<?php

// src/Controller/Admin/AdminPostController.php
namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Services\PostManager;
use App\Services\TagManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

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
     * @Route("/posts/create", name="admin_post_create")
     */
    public function create(
        Request $request,
        PostRepository $postRepository,
        TagManager $tagManager,
        PostManager $postManager
    ) {
        $form = $this->createForm(PostType::class, new Post());
        $form->handleRequest($request);
        $post = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            // Assuming the form modifies the $post object directly
            $post->setUser($this->getUser());
            $slug = $postManager->generateSlug($post->getTitle());
            $post->setSlug($slug);
            $postRepository->add($post, true); // Assuming there's a save method to persist and flush the Post entity

            $requestPostTagIds = explode(',', $request->request->get('postTags', ''));
            $submittedTagIds = array_map(function ($tagIdString) {return (int) $tagIdString; }, $requestPostTagIds);
            $submittedTagIds = array_filter($submittedTagIds);

            if (count($submittedTagIds)) {
                $tagManager->addTagsToPost($post, $submittedTagIds, $this->getUser());
            }

            $this->addFlash('success', 'Post has been created.');
            return $this->redirectToRoute('admin_post_index');
        }

        // Initially, no tags are associated with the new post, so no need to serialize post tags here
        return $this->renderForm(
            'admin/posts/create.html.twig',
            [
                'form' => $form,
                'post' => $post
            ]
        );
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
    public function edit(
        int $post,
        Request $request,
        EntityManagerInterface $entityManager,
        PostRepository $postRepository,
        SerializerInterface $serializer,
        TagManager $tagManager
    )
    {
        $post = $postRepository->findPostWithTags($post);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setIsPublished($form->get('is_published')->getData());

            $requestPostTagIds = explode(',', $request->request->get('postTags', ''));
            $submittedTagIds = array_map(function ($tagIdString) { return (int) $tagIdString; }, $requestPostTagIds);
            $submittedTagIds = array_filter($submittedTagIds);

            if (count($submittedTagIds)) {
                $tagManager->updatePostTags($post, $submittedTagIds, $this->getUser());
            }
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post has been updated.');
            return $this->redirectToRoute('admin_post_index');
        }

        $postTagsJson = $serializer->serialize($post->getPostTags(), 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);

        return $this->renderForm(
            'admin/posts/edit.html.twig',
            [
                'form' => $form,
                'post' => $post,
                'postTagsJson' => $postTagsJson,
            ]
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