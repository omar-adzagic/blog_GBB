<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
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
