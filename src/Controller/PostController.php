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
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostController extends AbstractController
{
    private $translationService;
    private $contentTranslationService;
    private $postManager;
    private $entityManager;
    public function __construct(
        TranslationService $translationService,
        ContentTranslationService $contentTranslationService,
        PostManager $postManager,
        EntityManagerInterface $entityManager
    )
    {
        $this->translationService = $translationService;
        $this->contentTranslationService = $contentTranslationService;
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
    }

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
        EmailService $emailService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        $post = $postRepository->findOneBySlugWithRelationships($slug, $userId);

        if (!$post) {
            throw $this->createNotFoundException(
                $this->translationService->messageTranslate(
                    'exceptions.no_slug_post',
                    ['{{slug}}' => $slug]
                )
            );
        }

        $commentForm = $this->createForm(CommentType::class, new Comment());
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $commentManager->saveComment($post, $comment);

            $emailService->sendAdminNewCommentNotification($post, $comment);

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_success',
                    ['{{entity}}' => 'the_comment', '{{action}}' => 'actions.added']
                )
            );
            return $this->redirectToRoute('app_post_show', ['slug' => $post->getSlug()]);
        }

        $postDTO = PostDTO::createFromEntity($post, $this->contentTranslationService);
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
    public function create(Request $request): Response
    {
        $newPost = new Post();

        $form = $this->createForm(PostType::class, $newPost);
        $this->contentTranslationService->setLocaleCreateFormFields($form, ['title', 'content']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->beginTransaction();
            try {
                $post = $form->getData();
                $this->postManager->saveCreatePost($post, $form);
                $this->postManager->savePostImage($post, $form);
                $this->contentTranslationService->setCreateTranslatableFormFields(
                    $form, $post, ['title', 'content'], PostTranslation::class
                );

                $this->entityManager->flush();
                $this->entityManager->commit();

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.created']
                    )
                );

                return $this->redirectToRoute('app_post');
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_failed',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.created']
                    )
                );
            }
        }

        $responseData = [
            'form' => $form,
            'post' => $newPost,
            'locales' => $this->contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('post/create.html.twig', $responseData);
    }

    /**
     * @Route("/post/{post}/edit", name="app_post_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Post $post, Request $request): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $this->contentTranslationService->setLocaleEditFormFields($form, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->beginTransaction();

            try {
                $this->postManager->saveEditPost($post, $form);
                $this->postManager->updatePostImage($post, $form);
                $this->contentTranslationService->setEditTranslatableFields($form, $post);

                $this->entityManager->commit();

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.updated']
                    )
                );
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_failed',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.created']
                    )
                );
            }

            return $this->redirectToRoute('app_post');
        }

        $responseData = [
            'form' => $form,
            'post' => $post,
            'locales' => $this->contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('post/edit.html.twig', $responseData);
    }
}
