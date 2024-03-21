<?php

namespace App\Controller;

use App\DTO\PostDTO;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostTranslation;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Service\CommentManager;
use App\Service\ContentTranslationService;
use App\Service\EmailService;
use App\Service\PostManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostController extends AbstractController
{
    /**
     * @Route("/", name="app_post")
     */
    public function index(): Response
    {
        return $this->render('post/index.html.twig');
    }

    /**
     * @Route("/post/{slug}", name="app_post_show")
     */
    public function showOne(
        string $slug,
        Request $request,
        PostRepository $postRepository,
        CommentManager $commentManager,
        EmailService $emailService,
        ContentTranslationService $contentTranslationService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        $post = $postRepository->findOneBySlugWithRelationships($slug, $userId);

        if (!$post) {
            throw $this->createNotFoundException('No post found for slug ' . $slug);
        }

        $commentForm = $this->createForm(CommentType::class, new Comment());
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $commentManager->saveComment($post, $comment);

            $emailService->sendAdminNewCommentNotification($post, $comment);

            $this->addFlash('success', 'Your comment has been added.');
            return $this->redirectToRoute('app_post_show', ['slug' => $post->getSlug()]);
        }

        $postDTO = PostDTO::createFromEntity($post, $contentTranslationService);
        if ($user) {
            $postDTO->setIsLiked($post->isLikedByUser($user));
            $postDTO->setIsFavorite($post->isFavoredByUser($user));
        }
        $postDTO->setLikesCount($postRepository->countLikesForPost($post->getId()));

        $responseData = [
            'post' => $postDTO,
            'commentForm' => $commentForm->createView(),
        ];
        return $this->render('post/show.html.twig', $responseData);
    }

    /**
     * @Route("/post/create", name="app_post_create", priority=2)
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("ROLE_ADMIN")
     */
    public function create(
        Request $request,
        PostManager $postManager,
        ContentTranslationService $contentTranslationService,
        EntityManagerInterface $entityManager
    ): Response
    {
        $newPost = new Post();

        $form = $this->createForm(PostType::class, $newPost);
        $contentTranslationService->setLocaleCreateFormFields($form, ['title', 'content']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $post = $form->getData();
                $postManager->saveCreatePost($post, $form);
                $postManager->savePostImage($post, $form);
                $contentTranslationService->setCreateTranslatableFormFields(
                    $form, $post, ['title', 'content'], PostTranslation::class
                );

                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Your post has been added.');

                return $this->redirectToRoute('app_post');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred. The post could not be created.');
            }
        }

        $responseData = [
            'form' => $form,
            'post' => $newPost,
            'locales' => $contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('post/create.html.twig', $responseData);
    }

    /**
     * @Route("/post/{post}/edit", name="app_post_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(
        Post $post, Request $request,
        PostManager $postManager,
        ContentTranslationService $contentTranslationService
    ): Response
    {
        $authUserId = $this->getUser()->getId();

        if ($post->getUser()->getId() !== $authUserId) {
            // Throw an AccessDeniedException
            throw new AccessDeniedException('You do not have permission to edit this post.');
        }

        $form = $this->createForm(PostType::class, $post);
        $contentTranslationService->setLocaleEditFormFields($form, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postManager->saveEditPost($post, $form);
            $postManager->updatePostImage($post, $form);
            $contentTranslationService->setEditTranslatableFields($form, $post);

            $this->addFlash('success', 'Your post has been updated.');
            return $this->redirectToRoute('app_post');
        }

        $responseData = [
            'form' => $form,
            'post' => $post,
            'locales' => $contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('post/edit.html.twig', $responseData);
    }
}
