<?php

namespace App\Repository;

use App\Entity\PostTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostTag>
 *
 * @method PostTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostTag[]    findAll()
 * @method PostTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostTag::class);
    }

    public function findPostTagByIdsWithRelations(array $postTagIds): array
    {
        return $this->createQueryBuilder('pt')
            ->join('pt.tag', 't')
            ->leftJoin('t.translations', 'tt')
            ->select('pt', 't', 'tt')
            ->where('pt.id IN (:postTagIds)')
            ->setParameter('postTagIds', $postTagIds)
            ->getQuery()
            ->getResult();
    }
}
