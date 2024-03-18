<?php

namespace App\Repository;

use App\Entity\UserFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserFavorite|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFavorite|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFavorite[]    findAll()
 * @method UserFavorite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFavorite::class);
    }

    /**
     * Find all favorites (and associated posts) for a given user ID, returning specific post fields.
     *
     * @param int $userId The ID of the user.
     * @return array Returns an array of arrays with specific fields from Post entities and the UserFavorite identifier.
     */
    public function findFavoritePostsByUserId(int $userId): array
    {
        $queryBuilder = $this->createQueryBuilder('uf')
            ->innerJoin('uf.post', 'p')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.user', 'u')
            ->addSelect('p', 'c', 'u')
            ->where('uf.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery();

        return $queryBuilder->getResult();
    }

    public function findLikedAndFavoredPostsByUserId(int $userId, array $postIds)
    {
        $qb = $this->createQueryBuilder('uf');

        $qb->select('p.id')
            ->innerJoin('uf.post', 'p')
            ->leftJoin('p.likedByUsers', 'l', 'WITH', 'l.user = :userId')
            ->where('uf.user = :userId')
            ->andWhere($qb->expr()->in('p.id', $postIds))
            ->setParameter('userId', $userId)
            ->groupBy('p.id')
            ->having('COUNT(l.id) > 0');

        return $qb->getQuery()->getResult();
    }
}
