<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserFavoriteRepository;
use App\Repository\UserLikeRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class PostManager
{
    private EntityManagerInterface $entityManager;
    private $slugify;
    private AuthorizationCheckerInterface $authorizationChecker;

    private FileUploader $fileUploader;
    private ?UserInterface $authUser;
    private $userLikeRepository;
    private $userFavoriteRepository;
    private $postRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        FileUploader $fileUploader,
        Security $security,
        UserLikeRepository $userLikeRepository,
        UserFavoriteRepository $userFavoriteRepository,
        PostRepository $postRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->slugify = new Slugify();
        $this->authorizationChecker = $authorizationChecker;
        $this->fileUploader = $fileUploader;
        $this->authUser = $security->getUser();
        $this->userLikeRepository = $userLikeRepository;
        $this->userFavoriteRepository = $userFavoriteRepository;
        $this->postRepository = $postRepository;
    }

    public function setPost($post, $form)
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $post->setIsPublished($form->get('is_published')->getData());
        }
        $post->setTitle(null);
        $post->setUser($this->authUser);
        $slug = $this->generateSlug($form->get('title_en')->getData());
        $post->setSlug($slug);
    }

    public function saveCreatePost($post, $form)
    {
        $this->setPost($post, $form);
        $this->entityManager->persist($post);
        $this->entityManager->flush();
    }

    public function saveEditPost($post, $form)
    {
        $this->setPost($post, $form);
        $this->entityManager->flush();
    }

    public function setPostImageLogic($post, $form, $callback)
    {
        $postImageFile = $form->get('image')->getData();
        if ($postImageFile) {
            $callback();
            $newFileName = $this->fileUploader->upload($postImageFile, 'post_images/');
            $post->setImage($newFileName);
        }
    }

    public function extractTagIdsFromRequestData($tagIdsString): array
    {
        $requestPostTagIds = explode(',', $tagIdsString);
        $submittedTagIds = array_map(function ($tagIdString) {
            return (int)$tagIdString;
        }, $requestPostTagIds);

        return array_filter($submittedTagIds);
    }

    public function savePostImage($post, $form)
    {
        $this->setPostImageLogic($post, $form, function() {});
    }

    public function updatePostImage($post, $form)
    {
        $this->setPostImageLogic($post, $form, function () use ($post) {
            $this->deleteOldImage($post);
        });
    }

    public function deleteOldImage($post)
    {
        $oldFilename = $post->getImage();
        if ($oldFilename) {
            $this->fileUploader->deleteFile($oldFilename, '/post_images');
        }
    }

    public function generateSlug($title): string
    {
        $slugify = new Slugify();
        $originalSlug = $slugify->slugify($title);

        // You might still want to check if this exact slug exists and append a count or random string in edge cases.
        $query = $this->entityManager->getRepository(Post::class)->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $originalSlug)
            ->getQuery();

        $results = $query->getResult();
        if (count($results) > 0) {
            return $slugify->slugify($originalSlug . '-' . microtime(true));
        }

        return $originalSlug;
    }
}
