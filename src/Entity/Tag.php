<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Entity\TagTranslation;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\TranslationEntity(class="App\Entity\TagTranslation")
 */
class Tag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("tag_search")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     * @Groups("tag_search")
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @ORM\OneToMany(targetEntity=TagTranslation::class, mappedBy="object", orphanRemoval=true)
     */
    private $translations;

    /**
     * @ORM\OneToMany(targetEntity=PostTag::class, mappedBy="tag", cascade={"remove"})
     */
    private $postTags;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated_at;

    public function __construct()
    {
        $this->postTags = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getPostTags(): Collection
    {
        return $this->postTags;
    }

    public function addPostTag(PostTag $postTag): self
    {
        if (!$this->postTags->contains($postTag)) {
            $this->postTags[] = $postTag;
            $postTag->setTag($this);
        }

        return $this;
    }

    public function removePostTag(PostTag $postTag): self
    {
        if ($this->postTags->removeElement($postTag)) {
            // set the owning side to null (unless already changed)
            if ($postTag->getTag() === $this) {
                $postTag->setTag(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param mixed $updated_at
     */
    public function setUpdatedAt($updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param mixed $created_at
     */
    public function setCreatedAt($created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setTranslatableLocale($locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTranslations(): Collection {
        return $this->translations;
    }

    public function addTranslation(TagTranslation $translation): self {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this); // Assuming the method is named setObject in TagTranslation
        }
        return $this;
    }

    public function removeTranslation(TagTranslation $translation): self {
        if ($this->translations->removeElement($translation)) {
            // Set the owning side to null
            if ($translation->getObject() === $this) {
                $translation->setObject(null);
            }
        }
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->created_at = new DateTimeImmutable();

        // If the updated_at field is not set, initialize it as well.
        if ($this->updated_at === null) {
            $this->updated_at = new DateTimeImmutable();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }
}

