<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\PaginationService;
use App\Service\PostManager;
use App\Service\TagManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/admin")
 * @IsGranted("ROLE_ADMIN")
 */
class AdminPostController extends AbstractController
{
    /**
     * @Route("/posts", name="admin_post_index")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function index(PostRepository $postRepository, PaginationService $paginationService)
    {
        $queryBuilder = $postRepository->findAllWithCommentsQB();
        $pagination = $paginationService->paginate($queryBuilder);

        return $this->render('admin/posts/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/posts/create", name="admin_post_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(
        Request $request,
        PostRepository $postRepository,
        TagManager $tagManager,
        PostManager $postManager,
        EntityManagerInterface $entityManager
    ) {
        $user = $this->getUser();
        $form = $this->createForm(PostType::class, new Post());
        $form->handleRequest($request);
        $post = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                // Assuming the form modifies the $post object directly
                $post->setUser($user);
                $slug = $postManager->generateSlug($post->getTitle());
                $post->setSlug($slug);
                $postRepository->add($post, true); // Assuming there's a save method to persist and flush the Post entity

                $requestPostTagIds = explode(',', $request->request->get('postTags', ''));
                $submittedTagIds = array_map(function ($tagIdString) {
                    return (int)$tagIdString;
                }, $requestPostTagIds);
                $submittedTagIds = array_filter($submittedTagIds);

                if (count($submittedTagIds)) {
                    $tagManager->addTagsToPost($post, $submittedTagIds, $user);
                }
                $entityManager->commit();

                $this->addFlash('success', 'Post has been created.');
                return $this->redirectToRoute('admin_post_index');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred. The post could not be created.');
            }
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function show(
        Post $post,
        Request $request,
        PostRepository $postRepository,
        CommentRepository $commentRepository
    ): Response
    {
        $user = $this->getUser();
        $commentForm = $this->createForm(CommentType::class, new Comment());
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $comment->setAuthor($user);
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
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
        $user = $this->getUser();
        $post = $postRepository->findPostWithTags($post);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setIsPublished($form->get('is_published')->getData());

            $requestPostTagIds = explode(',', $request->request->get('postTags', ''));
            $submittedTagIds = array_map(function ($tagIdString) { return (int) $tagIdString; }, $requestPostTagIds);
            $submittedTagIds = array_filter($submittedTagIds);

            if (count($submittedTagIds)) {
                $tagManager->updatePostTags($post, $submittedTagIds, $user);
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(Post $post, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post has been deleted.');

        return $this->redirectToRoute('admin_post_index');
    }
}
