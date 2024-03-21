<?php

namespace App\Service;

use App\Repository\CommentRepository;
use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class CommentManager
{
    private $entityManager;
    private $authUser;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->authUser = $security->getUser();
    }

    public function saveComment($post, $comment)
    {
        $comment->setAuthor($this->authUser);
        $comment->setPost($post);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }
}
