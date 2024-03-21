<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 *
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function add(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function findByNameLike(string $searchTerm)
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
            ->getQuery()
            ->getResult();
    }

    public function findAllLatestQB(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.translations', 'tt')
            ->select('t', 'tt')
            ->orderBy('t.created_at', 'DESC');
    }

    public function findAllLatest(): array
    {
        return $this->findAllLatestQB()
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tags by a list of ids.
     *
     * @param array $tagIds Array of Tag IDs
     * @return Tag[] Returns an array of Tag objects
     */
    public function findByIds(array $tagIds): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.id IN (:tagIds)')
            ->setParameter('tagIds', $tagIds)
            ->getQuery()
            ->getResult();
    }
}
