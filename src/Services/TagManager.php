<?php

namespace App\Services;

use App\Entity\Post;
use App\Entity\PostTag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TagManager
{
    private $entityManager;
    private $tagRepository;

    public function __construct(EntityManagerInterface $entityManager, TagRepository $tagRepository)
    {
        $this->entityManager = $entityManager;
        $this->tagRepository = $tagRepository;
    }

    public function addTagsToPost(Post $post, array $submittedTagIds, UserInterface $user)
    {
        $tags = $this->tagRepository->findBy(['id' => $submittedTagIds]);

        foreach ($tags as $tag) {
            $this->createAndPersistPostTag($post, $tag, $user);
        }

        $this->entityManager->flush();
    }

    public function updatePostTags(Post $post, array $submittedTagIds, UserInterface $user): void
    {
        $currentTagIds = array_map(function ($postTag) {
            return $postTag->getTag()->getId();
        }, $post->getPostTags()->toArray());

        $tagsToAdd = array_diff($submittedTagIds, $currentTagIds);
        $tagsToRemove = array_diff($currentTagIds, $submittedTagIds);

        $tags = $this->tagRepository->findBy(['id' => $tagsToAdd]);

        foreach ($tags as $tag) {
            $this->createAndPersistPostTag($post, $tag, $user);
        }

        foreach ($post->getPostTags() as $postTag) {
            if (in_array($postTag->getTag()->getId(), $tagsToRemove)) {
                $this->entityManager->remove($postTag);
            }
        }

        $this->entityManager->flush();
    }

    private function createAndPersistPostTag(Post $post, $tag, UserInterface $user): void
    {
        $postTag = new PostTag();
        $postTag->setPost($post);
        $postTag->setTag($tag);
        $postTag->setCreatedBy($user);
        $this->entityManager->persist($postTag);
        $post->addPostTag($postTag);
    }
}
