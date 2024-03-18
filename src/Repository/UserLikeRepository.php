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

    public function countLikesForPostIds(array $postIds)
    {
        $qb = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.post) AS postId, COUNT(l.id) AS likeCount')
            ->where('l.post IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('l.post')
            ->getQuery();

        $results = $qb->getResult();

        $likesCountByPostId = [];
        foreach ($results as $result) {
            $likesCountByPostId[$result['postId']] = (int) $result['likeCount'];
        }

        return $likesCountByPostId;
    }
}
