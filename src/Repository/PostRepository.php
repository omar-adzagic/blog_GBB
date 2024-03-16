<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Post $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Post $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findAllWithCommentsQB(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->addSelect('c')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->leftJoin('p.likedByUsers', 'l')
            ->addSelect('l')
            ->orderBy('p.created_at', 'DESC');
    }

    public function findAllWithComments(): array
    {
        return $this->findAllWithCommentsQB()
            ->getQuery()
            ->getResult();
    }

    public function findPublishedWithComments(): array
    {
        return $this->findAllWithCommentsQB()
            ->where('p.is_published = 1')
            ->getQuery()
            ->getResult();
    }

    public function findAllByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->addSelect('u')
            ->where('u = :user')
            ->setParameter(
                'user',
                $user instanceof User ? $user->getId() : $user
            )
            ->getQuery()
            ->getResult();
    }

    public function findAllWithCommentCountQueryBuilder($currentUserId)
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                'p.id',
                'u.id AS user_id',
                'u.email AS user_email',
                'p.title',
                'p.content',
                'p.image',
                'p.slug',
                'p.is_published',
                'p.created_at',
                '(SELECT COUNT(l2) FROM App\Entity\UserLike l2 WHERE l2.post = p.id) AS totalLikes',
                '(SELECT COUNT(c2) FROM App\Entity\Comment c2 WHERE c2.post = p.id) AS totalComments'
            )
            ->innerJoin('p.user', 'u');

        if ($currentUserId) {
            // Add conditional likes information to the query
            $qb->addSelect(
                "(SELECT COUNT(l) FROM App\Entity\UserLike l WHERE l.post = p AND l.user = :currentUserId) AS isLikedByCurrentUser"
            )
                ->addSelect(
                    "(SELECT COUNT(f) FROM App\Entity\UserFavorite f WHERE f.post = p AND f.user = :currentUserId) AS isFavoredByCurrentUser"
                )
                ->setParameter('currentUserId', $currentUserId);
        }

        $qb->orderBy('p.created_at', 'DESC');

        return $qb;
    }

    public function findPublishedWithCommentCountQueryBuilder(int $userId)
    {
        return $this->findAllWithCommentCountQueryBuilder($userId)
            ->where('p.is_published = 1');
    }

    public function findPostWithTags(int $postId)
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.postTags', 'pt')
            ->leftJoin('pt.tag', 't')
            ->addSelect('pt', 't')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return Post[] Returns an array of Post objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Post
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
