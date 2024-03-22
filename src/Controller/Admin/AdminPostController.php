<?php

namespace App\Controller\Admin;

use App\DTO\PostDTO;
use App\DTO\TagDTO;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostTranslation;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Repository\PostTagRepository;
use App\Service\CommentManager;
use App\Service\ContentTranslationService;
use App\Service\HelperService;
use App\Service\PaginationService;
use App\Service\PostManager;
use App\Service\TagManager;
use App\Service\TranslationService;
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
    private $translationService;
    private $contentTranslationService;
    private $entityManager;
    private $postManager;
    private $postRepository;
    private $tagManager;
    public function __construct(
        TranslationService $translationService,
        ContentTranslationService $contentTranslationService,
        EntityManagerInterface $entityManager,
        PostManager $postManager,
        PostRepository $postRepository,
        TagManager $tagManager
    )
    {
        $this->translationService = $translationService;
        $this->contentTranslationService = $contentTranslationService;
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
        $this->postRepository = $postRepository;
        $this->tagManager = $tagManager;
    }

    /**
     * @Route("/posts", name="admin_post_index")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function index(PaginationService $paginationService): Response
    {
        $queryBuilder = $this->postRepository->findAllWithCommentsQB();
        $pagination = $paginationService->paginate($queryBuilder);

        $items = $pagination->getItems();
        $postsDTO = PostDTO::createFromEntities($items, $this->contentTranslationService);
        $pagination->setItems($postsDTO);

        return $this->render('admin/posts/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * @Route("/posts/create", name="admin_post_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(Request $request) {
        $user = $this->getUser();
        $form = $this->createForm(PostType::class, new Post());
        $this->contentTranslationService->setLocaleCreateFormFields($form, ['title', 'content']);
        $form->handleRequest($request);
        $post = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->beginTransaction();
            try {
                $this->postManager->saveCreatePost($post, $form);

                $this->contentTranslationService->setCreateTranslatableFormFields(
                    $form, $post, ['title', 'content'], PostTranslation::class
                );

                $requestTagIdsString = $request->request->get('postTags', '');
                $submittedTagIds = $this->postManager->extractTagIdsFromRequestData($requestTagIdsString);

                if (count($submittedTagIds)) {
                    $this->tagManager->addTagsToPost($post, $submittedTagIds);
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.created']
                    )
                );
                return $this->redirectToRoute('admin_post_index');
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
            'post' => $post,
            'locales' => $this->contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/posts/create.html.twig', $responseData);
    }

    /**
     * @Route("/posts/{post}", name="admin_post_show")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function show(Post $post, Request $request, CommentManager $commentManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $post = $this->postRepository->findUserPostWithRelations($post->getId(), $user->getId());

        $commentForm = $this->createForm(CommentType::class, new Comment());
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $commentManager->saveComment($post, $comment);

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_success',
                    ['{{entity}}' => 'the_comment', '{{action}}' => 'actions.added']
                )
            );

            return $this->redirectToRoute('admin_post_show', ['post' => $post->getId()]);
        }

        $postDTO = PostDTO::createFromEntity($post, $this->contentTranslationService);
        $postDTO->setIsLiked($post->isLikedByUser($user));
        $postDTO->setIsFavorite($post->isFavoredByUser($user));
        $likesCount = $this->postRepository->countLikesForPost($post->getId());
        $postDTO->setLikesCount($likesCount);

        $responseData = [
            'post' => $postDTO,
            'commentForm' => $commentForm->createView(),
        ];
        return $this->render('post/show.html.twig', $responseData);
    }

    /**
     * @Route("/posts/{post}/edit", name="admin_post_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        int $post,
        Request $request,
        PostTagRepository $postTagRepository,
        SerializerInterface $serializer
    )
    {
        $user = $this->getUser();
        $post = $this->postRepository->findUserPostWithRelations($post, $user->getId());

        $form = $this->createForm(PostType::class, $post);
        $this->contentTranslationService->setLocaleEditFormFields($form, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->beginTransaction();
            try {
                $this->postManager->saveCreatePost($post, $form);
                $this->contentTranslationService->setEditTranslatableFields($form, $post);

                $requestTagIdsString = $request->request->get('postTags', '');
                $submittedTagIds = $this->postManager->extractTagIdsFromRequestData($requestTagIdsString);
                if (count($submittedTagIds)) {
                    $this->tagManager->updatePostTags($post, $submittedTagIds);
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.updated']
                    )
                );

                return $this->redirectToRoute('admin_post_index');
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_failed',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.updated']
                    )
                );
            }
        }

        $postTagIds = HelperService::getIdsFromDoctrine($post->getPostTags()->toArray());
        $postTags = $postTagRepository->findPostTagByIdsWithRelations($postTagIds);
        $tagDTOs = TagDTO::createFromPostTags($postTags, $this->contentTranslationService);
        $postTagsJson = $serializer->serialize($tagDTOs, 'json');

        $responseData = [
            'form' => $form,
            'post' => $post,
            'postTagsJson' => $postTagsJson,
            'locales' => $this->contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/posts/edit.html.twig', $responseData);
    }

    /**
     * @Route("/posts/{post}/delete", name="admin_post_delete")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(Post $post): RedirectResponse
    {
        $this->entityManager->beginTransaction();
        try {
            $this->postManager->deleteOldImage($post);

            $this->entityManager->remove($post);
            $this->entityManager->flush();

            $this->entityManager->commit();

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_post', '{{action}}' => 'actions.deleted']
                    )
            );
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->addFlash(
                'error',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_failed',
                    ['{{entity}}' => 'the_post', '{{action}}' => 'actions.deleted']
                )
            );
        }

        return $this->redirectToRoute('admin_post_index');
    }
}
