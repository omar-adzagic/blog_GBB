<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = true): void
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
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findAllWithoutUserLatest(int $userId): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.created_at', 'DESC')
            ->where('u.id != :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findUserWithFavoredPosts(int $userId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.userFavorites', 'uf')
            ->addSelect('uf')
            ->leftJoin('uf.post', 'p') // Assuming `post` is the property in UserFavorite that references the Post entity
            ->addSelect('p')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUserActivities(int $userId)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $sql = "
            SELECT 
                activity.type, 
                activity.user_id, 
                activity.post_id, 
                post.title AS post_title,
                post.slug as post_slug,
                activity.created_at
            FROM
                (SELECT 'like' AS type, user_id, post_id, created_at 
                 FROM user_like
                 UNION ALL
                 SELECT 'favorite' AS type, user_id, post_id,created_at 
                 FROM user_favorite) AS activity
            JOIN post ON activity.post_id = post.id
            WHERE activity.user_id = :userId
            ORDER BY activity.created_at DESC
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['userId' => $userId]);

        return $result->fetchAllAssociative();
    }
}
