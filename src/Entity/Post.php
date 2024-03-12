<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="posts")
 * @ORM\HasLifecycleCallbacks()
 */
class Post
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="post", orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="likedPosts")
     */
    private $likedByUsers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="favoredPosts")
     */
    private $favoredByUsers;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $is_published = false;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updated_at;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->likedByUsers = new ArrayCollection();
        $this->favoredByUsers = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getLikedByUsers(): Collection
    {
        return $this->likedByUsers;
    }

    public function addLikedByUser(User $likedByUser): self
    {
        if (!$this->likedByUsers->contains($likedByUser)) {
            $this->likedByUsers[] = $likedByUser;
            $likedByUser->addLikedPost($this);
        }

        return $this;
    }

    public function removeLikedByUser(User $likedByUser): self
    {
        if ($this->likedByUsers->removeElement($likedByUser)) {
            $likedByUser->removeLikedPost($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFavoredByUsers(): Collection
    {
        return $this->favoredByUsers;
    }

    public function addFavoredByUser(User $favoredByUser): self
    {
        if (!$this->favoredByUsers->contains($favoredByUser)) {
            $this->favoredByUsers[] = $favoredByUser;
            $favoredByUser->addFavoredPost($this);
        }

        return $this;
    }

    public function removeFavoredByUser(User $favoredByUser): self
    {
        if ($this->favoredByUsers->removeElement($favoredByUser)) {
            $favoredByUser->removeFavoredPost($this);
        }

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->is_published;
    }

    public function setIsPublished(bool $is_published): self
    {
        $this->is_published = $is_published;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
