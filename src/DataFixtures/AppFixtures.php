<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher) {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    private function createUser(ObjectManager $manager, string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $password
            )
        );
        $manager->persist($user);

        return $user;
    }

    private function createPost(ObjectManager $manager, User $user, string $title, string $content): void
    {
        $post = new Post();
        $post->setTitle($title);
        $post->setContent($content);
        $post->setUser($user);
        $manager->persist($post);
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = $this->createUser($manager, 'test@test.com', '12341234');
        $user2 = $this->createUser($manager, 'john@test.com', '12341234');

        $this->createPost($manager, $user1, 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Maxime mollitia, molestiae quas vel sint commodi repudiandae consequuntur voluptatum laborum numquam blanditiis harum quisquam eius sed odit fugiat iusto fuga praesentium optio, eaque rerum! Provident similique accusantium nemo autem.');
        $this->createPost($manager, $user1, 'Veritatis obcaecati tenetur', 'Veritatis obcaecati tenetur iure eius earum ut molestias architecto voluptate aliquam nihil, eveniet aliquid culpa officia aut! Impedit sit sunt quaerat, odit, tenetur error, harum nesciunt ipsum debitis quas aliquid.');
        $this->createPost($manager, $user2, 'Reprehenderit, quia', 'Reprehenderit, quia. Quo neque error repudiandae fuga? Ipsa laudantium molestias eos  sapiente officiis modi at sunt excepturi expedita sint? Sed quibusdam recusandae alias error harum maxime adipisci amet laborum. Perspiciatis  minima nesciunt dolorem! Officiis iure rerum voluptates a cumque velit  quibusdam sed amet tempora.');

        $manager->flush();
    }
}
