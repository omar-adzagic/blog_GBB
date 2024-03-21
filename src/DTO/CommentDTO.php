<?php

namespace App\DTO;

class CommentDTO {
    public ?int $id;
    public ?string $content;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?UserDTO $author;

    public function __construct(?int $id, ?string $content, ?string $createdAt, ?string $updatedAt, ?UserDTO $author) {
        $this->id = $id;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->author = $author;
    }

    public static function createFromEntity($comment): CommentDTO
    {
        $createdAt = $comment->getCreatedAt() !== null ? $comment->getCreatedAt()->format('d/m/Y H:i') : null;
        $updatedAt = $comment->getUpdatedAt() !== null ? $comment->getUpdatedAt()->format('d/m/Y H:i') : null;

        $author = $comment->getAuthor();
        $authorDTO = UserDTO::createFromEntity($author);
        $userProfile = $author->getUserProfile();
        $userProfileDTO = UserProfileDTO::createFromEntity($userProfile);
        $authorDTO->setUserProfile($userProfileDTO);

        return new CommentDTO(
            $comment->getId(),
            $comment->getContent(),
            $createdAt,
            $updatedAt,
            $authorDTO
        );
    }

    public static function createFromEntities($entitiesArray): array
    {
        return array_map(function ($comment) {
            return self::createFromEntity($comment);
        }, $entitiesArray);
    }
}
