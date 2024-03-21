<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
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

    public function findAllWithoutUserLatestQB(int $userId): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'up')
            ->orderBy('u.created_at', 'DESC')
            ->where('u.id != :userId')
            ->select('u', 'up')
            ->setParameter('userId', $userId);
    }

    public function findFirstAdmin()
    {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();

        $sql = '
            SELECT id FROM user
            WHERE JSON_CONTAINS(roles, :role) = 1
            LIMIT 1
        ';

        // Execute the query to find the ID of the first admin user
        $stmt = $connection->executeQuery($sql, ['role' => '"ROLE_ADMIN"']);
        $result = $stmt->fetchAssociative();

        if (!$result) {
            return null; // No admin user found
        }

        $userId = $result['id'];

        // Use the found user ID to retrieve and return the User entity
        return $entityManager->getRepository(User::class)->find($userId);
    }

    public function findUserActivities(int $userId, string $locale)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $sql = "
            SELECT 
                activity.type, 
                activity.user_id, 
                activity.post_id, 
                post.slug as post_slug,
                post_translations.content as post_title,
                activity.created_at
            FROM
                (SELECT 'like' AS type, user_id, post_id, created_at 
                 FROM user_like
                 UNION ALL
                 SELECT 'favorite' AS type, user_id, post_id,created_at 
                 FROM user_favorite) AS activity
            JOIN post ON activity.post_id = post.id
            LEFT JOIN post_translations ON post.id = post_translations.object_id
            WHERE activity.user_id = :userId
            AND post_translations.locale = :locale
            AND post_translations.field = 'title'
            ORDER BY activity.created_at DESC
        ";

        $stmt = $conn->prepare($sql);
        $parameters = [
            'userId' => $userId,
            'locale' => $locale,
        ];
        $result = $stmt->executeQuery($parameters);

        return $result->fetchAllAssociative();
    }
}
