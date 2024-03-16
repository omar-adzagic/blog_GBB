<?php

namespace App\Services;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;

class PostManager
{
    private $entityManager;
    private $slugify;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->slugify = new Slugify();
    }

    public function generateSlug($title): string
    {
        $slugify = new Slugify();
        $originalSlug = $slugify->slugify($title);

        // You might still want to check if this exact slug exists and append a count or random string in edge cases.
        $query = $this->entityManager->getRepository(Post::class)->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $originalSlug)
            ->getQuery();

        $results = $query->getResult();
        if (count($results) > 0) {
            return $slugify->slugify($originalSlug . '-' . microtime(true));
        }

        return $originalSlug;
    }
}
