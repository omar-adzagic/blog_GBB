<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Service\PostManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

class UserFixtures extends Fixture
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;
    private $postManager;
    private $env;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
        PostManager $postManager,
        ParameterBagInterface $params
    ) {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->postManager = $postManager;
        $this->env = $params->get('kernel.environment');
    }

    public function load(ObjectManager $manager): void
    {
        if ($this->env !== 'dev') {
            return;
        }

        $usersData = Yaml::parse(file_get_contents(__DIR__ . '\Resources\users_data.yaml'));

        foreach ($usersData as $userData) {
            $profile = $this->createUserProfile($manager, $userData['name'], $userData['image']);
            $user = $this->createUser(
                $manager, $userData['email'], $userData['username'], $userData['password'], $userData['roles']
            );
            $user->setUserProfile($profile);
        }

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        string $email,
        string $username,
        string $password,
        array $roles
    ): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(
            $this->userPasswordHasher->hashPassword($user, $password)
        );

        if (!empty($roles)) {
            $user->setRoles($roles);
        }

        $manager->persist($user);

        return $user;
    }

    private function createUserProfile(ObjectManager $manager, string $name, string $image): UserProfile
    {
        $userProfile = new UserProfile();
        $userProfile->setName($name);
        $userProfile->setImage($image);

        $manager->persist($userProfile);

        return $userProfile;
    }
}
