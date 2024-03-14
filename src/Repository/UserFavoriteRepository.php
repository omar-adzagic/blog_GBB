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
            ->where('uf.user = :userId')
            ->setParameter('userId', $userId)
            ->select('uf', 'p')
            ->getQuery();

        return $queryBuilder->getResult();
    }
}
