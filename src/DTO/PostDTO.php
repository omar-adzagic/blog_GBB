<?php

namespace App\DTO;

class PostDTO {
    public ?int $id;
    public ?string $title;
    public ?string $content;
    public ?string $slug;
    public ?string $image;
    public ?bool $isPublished;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?array $postTags;
    public ?array $comments;
    public ?int $commentsCount;
    public ?int $likesCount;
    public ?bool $isLiked;
    public ?bool $isFavorite;
    public ?UserDTO $user;

    public function __construct(
        ?int $id,
        ?string $title,
        ?string $content,
        ?string $slug,
        ?string $image,
        ?bool $isPublished,
        ?string $createdAt,
        ?string $updatedAt,
        ?UserDTO $user,
        ?array $comments = [],
        ?array $postTags = []
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->slug = $slug;
        $this->image = $image;
        $this->isPublished = $isPublished;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->user = $user;
        $this->comments = $comments;
        $this->postTags = $postTags;
    }

    // Factory method to create a PostDTO from a Post entity
    public static function createFromEntity($post, $contentTranslationService): self {
        $user = $post->getUser();
        $userDTO = UserDTO::createFromEntity($user);
        $userProfile = $user->getUserProfile();
        if ($userProfile) {
            $userProfileDTO = UserProfileDTO::createFromEntity($userProfile);
            $userDTO->setUserProfile($userProfileDTO);
        }
        $commentsDTOs = CommentDTO::createFromEntities($post->getComments()->toArray());
        $postTagsDTOs = TagDTO::createFromPostTags($post->getPostTags()->toArray(), $contentTranslationService);

        $createdAt = $post->getCreatedAt() !== null ? $post->getCreatedAt()->format('d/m/Y H:i') : null;
        $updatedAt = $post->getUpdatedAt() !== null ? $post->getUpdatedAt()->format('d/m/Y H:i') : null;

        $postTranslations = $post->getTranslations();
        $titleTranslation = $contentTranslationService->getTranslationContentForField($postTranslations, 'title');
        $contentTranslation = $contentTranslationService->getTranslationContentForField($postTranslations, 'content');

        return new self(
            $post->getId(),
            $titleTranslation,
            $contentTranslation,
            $post->getSlug(),
            $post->getImage(),
            $post->getIsPublished(),
            $createdAt,
            $updatedAt,
            $userDTO,
            $commentsDTOs,
            $postTagsDTOs
        );
    }

    public static function createFromEntities($posts, $contentTranslationService): array
    {
        return array_map(function ($tag) use ($contentTranslationService) {
            return self::createFromEntity($tag, $contentTranslationService);
        }, $posts);
    }

    public function setCommentsCount(int $commentsCount): void
    {
        $this->commentsCount = $commentsCount;
    }

    public function setLikesCount(int $likesCount): void
    {
        $this->likesCount = $likesCount;
    }

    public function setIsLiked(bool $isLiked): void
    {
        $this->isLiked = $isLiked;
    }

    public function setIsFavorite(bool $isFavorite): void
    {
        $this->isFavorite = $isFavorite;
    }
}
