<?php

namespace App\DTO;

class UserDTO {
    public ?int $id;
    public ?string $username;
    public ?string $email;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?array $roles;
    public ?UserProfileDTO $userProfile;

    public function __construct(
        ?int $id, ?string $username, ?string $email, ?string $createdAt, ?string $updatedAt, ?array $roles
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->roles = $roles;
    }

    public function setUserProfile(UserProfileDTO $userProfile)
    {
        $this->userProfile = $userProfile;
    }

    public static function createFromEntity($user): UserDTO
    {
        $createdAt = $user->getCreatedAt() !== null ? $user->getCreatedAt()->format('d/m/Y H:i') : null;
        $updatedAt = $user->getUpdatedAt() !== null ? $user->getUpdatedAt()->format('d/m/Y H:i') : null;

        return new UserDTO(
            $user->getId(),
            $user->getUsername(),
            $user->getEmail(),
            $createdAt,
            $updatedAt,
            $user->getRoles(),
        );
    }

    public static function createFromEntities($users): array
    {
        return array_map(function ($user) {
            return self::createFromEntity($user);
        }, $users);
    }
}
