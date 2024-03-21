<?php

namespace App\DTO;

namespace App\DTO;

use DateTimeInterface;

class UserFavoriteDTO {
    public int $id;
    public UserDTO $user;
    public PostDTO $post;
    public DateTimeInterface $createdAt;
    private int $commentsCount;
    private int $likesCount;
    private bool $isLiked;
    private bool $isFavorite;

    public function __construct(
        int $id,
        DateTimeInterface $createdAt,
        UserDTO $user,
        PostDTO $post
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->user = $user;
        $this->post = $post;

        $this->commentsCount = 0;
        $this->likesCount = 0;
        $this->isLiked = false;
        $this->isFavorite = false;
    }

    // Factory method to create a PostDTO from a Post entity
    public static function createFromEntity($userFavorite, $contentTranslationService): self {
        $user = $userFavorite->getUser();
        $userDTO = UserDTO::createFromEntity($user);
        $userProfile = $user->getUserProfile();
        if ($userProfile) {
            $userProfileDTO = UserProfileDTO::createFromEntity($userProfile);
            $userDTO->setUserProfile($userProfileDTO);
        }
        $post = $userFavorite->getPost();
        $postDTO = PostDTO::createFromEntity($post, $contentTranslationService);

        return new self(
            $userFavorite->getId(),
            $userFavorite->getCreatedAt(),
            $userDTO,
            $postDTO
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
