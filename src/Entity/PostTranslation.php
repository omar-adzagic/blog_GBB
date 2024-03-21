<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\Entity\Post;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_translations",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *         "locale", "object_id", "field"
 *     })}
 * )
 */
class PostTranslation extends AbstractPersonalTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $object;

    /**
     * Convenient constructor
     *
     * @param string $locale
     * @param string $field
     * @param string $value
     */
    public function __construct($locale, $field, $value)
    {
        $this->setLocale($locale);
        $this->setField($field);
        $this->setContent($value);
    }

    /**
     * @Groups({"post_translation"})
     */
    private $title;
    /**
     * @Groups({"post_translation"})
     */
    private $postContent;


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPostContent(): ?string
    {
        return $this->postContent;
    }

    public function setPostContent($content): self
    {
        $this->postContent = $content;

        return $this;
    }

    /**
     * @Groups({"post_translation"})
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @Groups({"post_translation"})
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * @Groups({"post_translation"})
     */
    public function getContent(): ?string
    {
        return $this->content;
    }
}
