<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\Index(name="title_index", columns={"title"})})
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\TranslationEntity(class="App\Entity\PostTranslation")
 */
class Post
{
    public const EDIT = 'POST_EDIT';
    public const VIEW = 'POST_VIEW';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("user")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @Groups("user")
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"user"})
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="post", orphanRemoval=true)
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserLike", mappedBy="post", cascade={"remove"})
     */
    private $likedByUsers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserFavorite", mappedBy="post", cascade={"remove"})
     */
    private $favoredByUsers;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups("user")
     */
    private $slug;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private $is_published;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups("user")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updated_at;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Translatable
     */
    private $content;

    /**
     * @ORM\OneToMany(targetEntity=PostTag::class, mappedBy="post", cascade={"remove"})
     */
    private $postTags;

    /**
     * @ORM\OneToMany(targetEntity=PostTranslation::class, mappedBy="object", orphanRemoval=true, fetch="EAGER")
     * @Groups("user")
     */
    private $translations;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->likedByUsers = new ArrayCollection();
        $this->favoredByUsers = new ArrayCollection();
        $this->postTags = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    public function getPostTags(): Collection
    {
        return $this->postTags;
    }

    public function addPostTag(PostTag $postTag): self
    {
        if (!$this->postTags->contains($postTag)) {
            $this->postTags[] = $postTag;
            $postTag->setPost($this); // Ensure the backward reference is correctly set
        }

        return $this;
    }

    public function removePostTag(PostTag $postTag): self
    {
        if ($this->postTags->removeElement($postTag)) {
            // set the owning side to null (unless already changed)
            if ($postTag->getPost() === $this) {
                $postTag->setPost(null); // Correctly nullify the reference if this is the post being removed
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): self
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

    public function addLikedByUser(UserInterface $likedByUser): self
    {
        if (!$this->likedByUsers->contains($likedByUser)) {
            $this->likedByUsers[] = $likedByUser;
            $this->addUserLike($likedByUser);
        }

        return $this;
    }

    public function removeLikedByUser(UserInterface $likedByUser): self
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

    public function addFavoredByUser(UserInterface $favoredByUser): self
    {
        if (!$this->favoredByUsers->contains($favoredByUser)) {
            $this->favoredByUsers[] = $favoredByUser;
            $favoredByUser->addFavoredPost($this);
        }

        return $this;
    }

    public function removeFavoredByUser(UserInterface $favoredByUser): self
    {
        if ($this->favoredByUsers->removeElement($favoredByUser)) {
            $favoredByUser->removeFavoredPost($this);
        }

        return $this;
    }

    public function isLikedByUser(User $user): bool
    {
        foreach ($this->likedByUsers as $like) {
            if ($like->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    public function isFavoredByUser(User $user): bool
    {
        foreach ($this->favoredByUsers as $favorite) {
            if ($favorite->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->is_published;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->is_published = $isPublished;

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

    public function getTranslations(): Collection {
        return $this->translations;
    }

    public function addTranslation(PostTranslation $translation): self {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setObject($this); // Ensure the translation is correctly associated with this Post
        }
        return $this;
    }

    public function removeTranslation(PostTranslation $translation): self {
        if ($this->translations->removeElement($translation)) {
            // Set the owning side to null (unless already changed)
            if ($translation->getObject() === $this) {
                $translation->setObject(null); // Correctly nullify the association
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return $this
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }
}
