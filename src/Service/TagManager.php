<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\PostTag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TagManager
{
    private EntityManagerInterface $entityManager;
    private TagRepository $tagRepository;
    private ?UserInterface $authUser;

    public function __construct(
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        Security $security
    )
    {
        $this->entityManager = $entityManager;
        $this->tagRepository = $tagRepository;
        $this->authUser = $security->getUser();
    }

    public function saveTagCreate($tag)
    {
        $tag->setName(null);
        $this->entityManager->persist($tag);
        $this->entityManager->flush();
    }

    public function addTagsToPost(Post $post, array $submittedTagIds)
    {
        $tags = $this->tagRepository->findBy(['id' => $submittedTagIds]);

        foreach ($tags as $tag) {
            $this->createAndPersistPostTag($post, $tag);
        }

        $this->entityManager->flush();
    }

    public function updatePostTags(Post $post, array $submittedTagIds): void
    {
        $currentTagIds = array_map(function ($postTag) {
            return $postTag->getTag()->getId();
        }, $post->getPostTags()->toArray());

        $tagsToAdd = array_diff($submittedTagIds, $currentTagIds);
        $tagsToRemove = array_diff($currentTagIds, $submittedTagIds);

        $tags = $this->tagRepository->findBy(['id' => $tagsToAdd]);

        foreach ($tags as $tag) {
            $this->createAndPersistPostTag($post, $tag);
        }

        foreach ($post->getPostTags() as $postTag) {
            if (in_array($postTag->getTag()->getId(), $tagsToRemove)) {
                $this->entityManager->remove($postTag);
            }
        }

        $this->entityManager->flush();
    }

    public function createAndPersistPostTag(Post $post, $tag): void
    {
        $postTag = new PostTag();
        $postTag->setPost($post);
        $postTag->setTag($tag);
        $postTag->setCreatedBy($this->authUser);
        $this->entityManager->persist($postTag);
        $post->addPostTag($postTag);
    }
}
