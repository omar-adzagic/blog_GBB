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
            ->leftJoin('p.translations', 'ptr')
            ->leftJoin('p.user', 'u')
            ->leftJoin('u.userProfile', 'up')
            ->leftJoin('p.likedByUsers', 'l')
            ->leftJoin('p.postTags', 'pt')
            ->leftJoin('pt.tag', 't')
            ->leftJoin('t.translations', 'ttr')
            ->addSelect('c', 'u', 'up', 'l', 'pt', 't', 'ptr', 'ttr')
            ->orderBy('p.created_at', 'DESC');
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

    //          ->innerJoin('p.user', 'u')
//            ->where('pt.locale = :locale')
//            ->setParameter('locale', $locale)
//                '(SELECT COUNT(l2) FROM App\Entity\UserLike l2 WHERE l2.post = p.id) AS totalLikes',
//                '(SELECT COUNT(c2) FROM App\Entity\Comment c2 WHERE c2.post = p.id) AS totalComments'
//        if ($currentUserId) {
//            // Add conditional likes information to the query
//            $qb->addSelect(
//                "(SELECT COUNT(l) FROM App\Entity\UserLike l WHERE l.post = p AND l.user = :currentUserId) AS isLikedByCurrentUser"
//            )
//                ->addSelect(
//                    "(SELECT COUNT(f) FROM App\Entity\UserFavorite f WHERE f.post = p AND f.user = :currentUserId) AS isFavoredByCurrentUser"
//                )
//                ->setParameter('currentUserId', $currentUserId);
//        }

    private function findAllWithCommentCountQB(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.translations', 'pt')
            ->select('p', 'pt')
            ->where('p.is_published = 1');
    }
    public function findAllWithCommentCountQueryBuilder($title): QueryBuilder
    {
        // First, build a query to select all post IDs that match the criteria
        if (!empty($title)) {
            $subQueryBuilder = $this->createQueryBuilder('p')
                ->select('p.id')
                ->leftJoin('p.translations', 'pt')
                ->where('p.is_published = 1')
                ->andWhere("pt.field = 'title'")
                ->andWhere('pt.content LIKE :title')
                ->setParameter('title', '%' . $title . '%')
                ->groupBy('p.id');

            $matchingPostIds = $subQueryBuilder->getQuery()->getResult();
            $matchingPostIds = array_column($matchingPostIds, 'id');

            // If no posts match the title, return an empty QueryBuilder
            if (empty($matchingPostIds)) {
                return $this->createQueryBuilder('p')->where('1 = 0'); // This ensures an empty result.
            }

            // Then, build the main query to fetch all matching posts along with all their translations
            $queryBuilder = $this->findAllWithCommentCountQB();
            $queryBuilder
                ->andWhere('p.id IN (:matchingPostIds)')
                ->setParameter('matchingPostIds', $matchingPostIds);
        } else {
            // If no title is provided, just fetch all posts that are published.
            $queryBuilder = $this->findAllWithCommentCountQB();
        }

        return $queryBuilder->orderBy('p.created_at', 'DESC');
    }

    public function findCommentCountsByPostIds(array $postIds): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder();

        $qb->select('p.id AS postId, COUNT(c.id) AS totalComments')
            ->from('App\Entity\Post', 'p')
            ->leftJoin('p.comments', 'c', 'WITH', 'c.post = p.id')
            ->where($qb->expr()->in('p.id', ':postIds'))
            ->setParameter('postIds', $postIds)
            ->groupBy('p.id');

        $qbResult = $qb->getQuery()->getResult();
        $totalCommentsPostIdsMap = [];
        foreach ($qbResult as $item) {
            $totalCommentsPostIdsMap[$item['postId']] = $item['totalComments'];
        }

        return $totalCommentsPostIdsMap;
    }

    public function findUserPostWithRelations(int $postId, int $userId)
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->leftJoin('p.postTags', 'pt')
            ->leftJoin('pt.tag', 'ptt')
            ->leftJoin('ptt.translations', 'pttr')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.translations', 'ptr')
            ->leftJoin('p.likedByUsers', 'l', 'WITH', 'l.user = :userId')
            ->leftJoin('p.favoredByUsers', 'f', 'WITH', 'f.user = :userId')
            ->leftJoin('u.userProfile', 'up')
            ->leftJoin('c.author', 'ca')
            ->leftJoin('ca.userProfile', 'caup')
            ->addSelect('p', 'c', 'l', 'f', 'u', 'up', 'pt', 'ptr', 'ca', 'caup', 'ptt', 'pttr')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneBySlugWithRelationships($slug, $userId)
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->leftJoin('u.userProfile', 'up')
            ->leftJoin('p.postTags', 'pt')
            ->leftJoin('pt.tag', 'ptt')
            ->leftJoin('p.translations', 'ptr')
            ->leftJoin('ptt.translations', 'pttr')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('c.author', 'ca')
            ->leftJoin('ca.userProfile', 'caup')
            ->addSelect('p', 'c', 'u', 'up', 'pt', 'ptr', 'ptt', 'ca', 'caup', 'pttr');

        // Conditionally add joins and parameters related to likes and favorites
        if ($userId !== null) {
            $qb->leftJoin('p.likedByUsers', 'ul', 'WITH', 'ul.user = :userId')
                ->leftJoin('p.favoredByUsers', 'uf', 'WITH', 'uf.user = :userId')
                ->addSelect('ul', 'uf')
                ->setParameter('userId', $userId);
        }

        $qb->where('p.slug = :slug')
            ->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function countLikesForPost($postId)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(l.id)
            FROM App\Entity\UserLike l
            WHERE l.post = :postId'
        )
            ->setParameter('postId', $postId)
            ->getSingleScalarResult();
    }

    public function findLikedPostsByPostIdsAndUserId(array $postIds, int $userId): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->leftJoin('p.likedByUsers', 'ul')
            ->leftJoin('ul.user', 'u')
            ->select('p.id')
            ->where($qb->expr()->in('p.id', ':postIds'))
            ->setParameter('postIds', $postIds)
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId);

        $qbResult = $qb->getQuery()->getResult();

        $result = array_fill_keys($postIds, false);
        foreach ($qbResult as $item) {
            $result[$item['id']] = true;
        }

        return $result;
    }
}
