<?php

namespace App\Repository;

use App\Entity\UserLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserLike|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserLike|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserLike[]    findAll()
 * @method UserLike[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLike::class);
    }

    public function countLikesForPostIds(array $postIds): array
    {
        $qb = $this->createQueryBuilder('ul')
            ->select('IDENTITY(ul.post) AS postId, COUNT(ul.id) AS likeCount')
            ->where('ul.post IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('ul.post')
            ->getQuery();

        $results = $qb->getResult();

        $likesCountByPostId = array_fill_keys($postIds, 0);

        foreach ($results as $result) {
            $likesCountByPostId[$result['postId']] = (int) $result['likeCount'];
        }

        return $likesCountByPostId;
    }
}
