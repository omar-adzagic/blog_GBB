<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
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
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups("tag_search")
     */
    private $name;

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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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

