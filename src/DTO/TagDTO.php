<?php

namespace App\DTO;

use App\Entity\PostTag;
use App\Entity\Tag;
use App\Service\ContentTranslationService;
use DateTimeInterface;

class TagDTO
{
    public int $id;
    public ?string $name;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(int $id, ?string $name, ?string $createdAt, ?string $updatedAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Creates a TagDTO from a PostTag entity.
     */
    public static function createFromPostTag(
        PostTag $postTag,
        ContentTranslationService $contentTranslationService
    ): self {
        return self::createTagDTOFromEntity($postTag->getTag(), $contentTranslationService);
    }

    /**
     * Creates an array of TagDTOs from an array of PostTags.
     */
    public static function createFromPostTags(array $postTags, ContentTranslationService $contentTranslationService): array
    {
        return array_map(function($postTag) use ($contentTranslationService) {
            return self::createFromPostTag($postTag, $contentTranslationService);
        }, $postTags);
    }

    /**
     * Creates a TagDTO from a Tag entity.
     */
    public static function createFromTag(Tag $tag, ContentTranslationService $contentTranslationService): self
    {
        return self::createTagDTOFromEntity($tag, $contentTranslationService);
    }

    /**
     * Creates an array of TagDTOs from an array of Tags.
     */
    public static function createFromTags(array $tags, ContentTranslationService $contentTranslationService): array
    {
        return array_map(function ($tag) use ($contentTranslationService) {
            return self::createFromTag($tag, $contentTranslationService);
        }, $tags);
    }

    /**
     * Private helper to reduce duplication in creating TagDTO from a Tag entity.
     */
    private static function createTagDTOFromEntity(Tag $tag, ContentTranslationService $contentTranslationService): self
    {
        $nameTranslation = $contentTranslationService->getTranslationContentForField($tag->getTranslations(), 'name');
        $createdAt = self::formatDate($tag->getCreatedAt());
        $updatedAt = self::formatDate($tag->getUpdatedAt());

        return new self($tag->getId(), $nameTranslation, $createdAt, $updatedAt);
    }

    /**
     * Formats a DateTimeInterface object into a string.
     */
    private static function formatDate(?DateTimeInterface $dateTime): ?string
    {
        return $dateTime !== null ? $dateTime->format('d/m/Y H:i') : null;
    }
}