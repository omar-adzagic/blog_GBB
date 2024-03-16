<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use App\Services\PostManager;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;
    private $postManager;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, PostManager $postManager) {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->postManager = $postManager;
    }

    private function createUser(
        ObjectManager $manager,
        string $email,
        string $username,
        string $password,
        array $roles = []
    ): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $password
            )
        );

        if (!empty($roles)) {
            $user->setRoles($roles);
        }

        $manager->persist($user);

        return $user;
    }

    private function createPost(
        ObjectManager $manager,
        User $user,
        string $title,
        string $content
    ): void
    {
        $post = new Post();
        $post->setTitle($title);
        $post->setContent($content);
        $slug = $this->postManager->generateSlug($post->getTitle());
        $post->setSlug($slug);
        $post->setUser($user);
        $manager->persist($post);
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = $this->createUser($manager, 'test@test.com', 'test', '12341234');
        $user2 = $this->createUser($manager, 'john@test.com', 'john', '12341234');
        $this->createUser($manager, 'admin@test.com', 'Admin', '12341234', ['ROLE_ADMIN']);

        $this->createPost($manager, $user1, 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Maxime mollitia, molestiae quas vel sint commodi repudiandae consequuntur voluptatum laborum numquam blanditiis harum quisquam eius sed odit fugiat iusto fuga praesentium optio, eaque rerum! Provident similique accusantium nemo autem.');
        $this->createPost($manager, $user1, 'Veritatis obcaecati tenetur', 'Veritatis obcaecati tenetur iure eius earum ut molestias architecto voluptate aliquam nihil, eveniet aliquid culpa officia aut! Impedit sit sunt quaerat, odit, tenetur error, harum nesciunt ipsum debitis quas aliquid.');
        $this->createPost($manager, $user2, 'Reprehenderit, quia', 'Reprehenderit, quia. Quo neque error repudiandae fuga? Ipsa laudantium molestias eos  sapiente officiis modi at sunt excepturi expedita sint? Sed quibusdam recusandae alias error harum maxime adipisci amet laborum. Perspiciatis  minima nesciunt dolorem! Officiis iure rerum voluptates a cumque velit  quibusdam sed amet tempora.');

        $manager->flush();
    }
}
