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
    public function index(
        PostRepository $postRepository,
        PaginationService $paginationService,
        ContentTranslationService $contentTranslationService
    ): Response
    {
        $queryBuilder = $postRepository->findAllWithCommentsQB();
        $pagination = $paginationService->paginate($queryBuilder);

        $items = $pagination->getItems();
        $postsDTO = PostDTO::createFromEntities($items, $contentTranslationService);
        $pagination->setItems($postsDTO);

        return $this->render('admin/posts/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * @Route("/posts/create", name="admin_post_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(
        Request $request,
        TagManager $tagManager,
        PostManager $postManager,
        EntityManagerInterface $entityManager,
        ContentTranslationService $contentTranslationService
    ) {
        $user = $this->getUser();
        $form = $this->createForm(PostType::class, new Post());
        $contentTranslationService->setLocaleCreateFormFields($form, ['title', 'content']);
        $form->handleRequest($request);
        $post = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $postManager->saveCreatePost($post, $form);

                $contentTranslationService->setCreateTranslatableFormFields(
                    $form, $post, ['title', 'content'], PostTranslation::class
                );

                $requestTagIdsString = $request->request->get('postTags', '');
                $submittedTagIds = $postManager->extractTagIdsFromRequestData($requestTagIdsString);

                if (count($submittedTagIds)) {
                    $tagManager->addTagsToPost($post, $submittedTagIds);
                }

                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Post has been created.');
                return $this->redirectToRoute('admin_post_index');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred. The post could not be created.');
            }
        }

        $responseData = [
            'form' => $form,
            'post' => $post,
            'locales' => $contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/posts/create.html.twig', $responseData);
    }

    /**
     * @Route("/posts/{post}", name="admin_post_show")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function show(
        Post $post,
        Request $request,
        PostRepository $postRepository,
        CommentManager $commentManager,
        ContentTranslationService $contentTranslationService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $post = $postRepository->findUserPostWithRelations($post->getId(), $user->getId());

        $commentForm = $this->createForm(CommentType::class, new Comment());
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $commentManager->saveComment($post, $comment);
            $this->addFlash('success', 'Your comment has been added.');

            return $this->redirectToRoute('admin_post_show', ['post' => $post->getId()]);
        }

        $postDTO = PostDTO::createFromEntity($post, $contentTranslationService);
        $postDTO->setIsLiked($post->isLikedByUser($user));
        $postDTO->setIsFavorite($post->isFavoredByUser($user));
        $postDTO->setLikesCount($postRepository->countLikesForPost($post->getId()));

        return $this->render('post/show.html.twig', [
            'post' => $postDTO,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    /**
     * @Route("/posts/{post}/edit", name="admin_post_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        int $post,
        Request $request,
        PostRepository $postRepository,
        PostTagRepository $postTagRepository,
        SerializerInterface $serializer,
        TagManager $tagManager,
        PostManager $postManager,
        ContentTranslationService $contentTranslationService,
        EntityManagerInterface $entityManager
    )
    {
        $user = $this->getUser();
        $post = $postRepository->findUserPostWithRelations($post, $user->getId());

        $form = $this->createForm(PostType::class, $post);
        $contentTranslationService->setLocaleEditFormFields($form, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $postManager->saveCreatePost($post, $form);
                $contentTranslationService->setEditTranslatableFields($form, $post);

                $requestTagIdsString = $request->request->get('postTags', '');
                $submittedTagIds = $postManager->extractTagIdsFromRequestData($requestTagIdsString);
                if (count($submittedTagIds)) {
                    $tagManager->updatePostTags($post, $submittedTagIds);
                }

                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Post has been updated.');
                return $this->redirectToRoute('admin_post_index');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred. The post could not be created.');
            }
        }

        $postTagIds = HelperService::getIdsFromDoctrine($post->getPostTags()->toArray());
        $postTags = $postTagRepository->findPostTagByIdsWithRelations($postTagIds);
        $tagDTOs = TagDTO::createFromPostTags($postTags, $contentTranslationService);
        $postTagsJson = $serializer->serialize($tagDTOs, 'json');

        $responseData = [
            'form' => $form,
            'post' => $post,
            'postTagsJson' => $postTagsJson,
            'locales' => $contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/posts/edit.html.twig', $responseData);
    }

    /**
     * @Route("/posts/{post}/delete", name="admin_post_delete")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(Post $post, PostManager $postManager, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->beginTransaction();
        try {
            $postManager->deleteOldImage($post);

            $entityManager->remove($post);
            $entityManager->flush();

            $entityManager->commit();

            $this->addFlash('success', 'Post has been deleted.');
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('error', 'An error occurred while deleting the post.');
        }

        return $this->redirectToRoute('admin_post_index');
    }
}
