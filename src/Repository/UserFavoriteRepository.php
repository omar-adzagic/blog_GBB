<?php

namespace App\Repository;

use App\Entity\UserFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
     * @return QueryBuilder Returns an QueryBuilder with specific fields from Post entities and the UserFavorite identifier.
     */
    public function findFavoritePostsByUserIdQB(int $userId): QueryBuilder
    {
        return $this->createQueryBuilder('uf')
            ->innerJoin('uf.user', 'ufu')
            ->innerJoin('uf.post', 'p')
            ->leftJoin('ufu.userProfile', 'ufup')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.user', 'pu')
            ->leftJoin('p.translations', 'ptr')
            ->leftJoin('pu.userProfile', 'puup')
            ->leftJoin('p.postTags', 'pt')
            ->leftJoin('pt.tag', 'ptt')
            ->leftJoin('ptt.translations', 'pttttr')
            ->addSelect('p', 'pu', 'puup', 'c', 'ufu', 'ufup', 'pt', 'ptr', 'ptt', 'pttttr')
            ->where('uf.user = :userId')
            ->setParameter('userId', $userId);
    }

    public function findFavoritePostsByUserId(int $userId): array
    {
        return $this->findFavoritePostsByUserIdQB($userId)
            ->getQuery()
            ->getResult();
    }

    public function findFavoritePostsByPostIdsAndUserId(array $postIds, int $userId): array
    {
        if (empty($postIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('uf');
        $qb->innerJoin('uf.post', 'p')
            ->innerJoin('uf.user', 'u')
            ->select('p.id')
            ->where($qb->expr()->in('p.id', ':postIds'))
            ->setParameter('postIds', $postIds)
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId);

        $qbResult = $qb->getQuery()->getResult();

        $resultMap = [];
        foreach ($qbResult as $result) {
            $resultMap[$result['id']] = true;
        }

        return $resultMap;
    }

    public function findLikedAndFavoredPostsByUserId(int $userId, array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('uf');

        $qb->select('p.id')
            ->innerJoin('uf.post', 'p')
            ->leftJoin('p.likedByUsers', 'l', 'WITH', 'l.user = :userId')
            ->where('uf.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere($qb->expr()->in('p.id', ':postIds'))
            ->setParameter('postIds', $postIds)
            ->groupBy('p.id')
            ->having('COUNT(l.id) > 0');

        $queryResult = $qb->getQuery()->getResult();

        $likedAndFavoredIdsMap = array_fill_keys($postIds, false);
        foreach ($queryResult as $item) {
            $likedAndFavoredIdsMap[$item['id']] = true;
        }

        return $likedAndFavoredIdsMap;
    }
}
