<?php

namespace App\DTO;

class UserProfileDTO {
    public ?int $id;
    public ?string $name;
    public ?string $bio;
    public ?string $websiteUrl;
    public ?string $location;
    public ?string $dateOfBirth;
    public ?string $image;

    public function __construct(
        ?int $id=null, ?string $name=null, ?string $bio=null, ?string $websiteUrl=null,
        ?string $location=null, ?string $dateOfBirth=null, ?string $image=null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->bio = $bio;
        $this->websiteUrl = $websiteUrl;
        $this->location = $location;
        $this->dateOfBirth = $dateOfBirth;
        $this->image = $image;
    }

    public static function createFromEntity($userProfile): UserProfileDTO
    {
        $dateOfBirth = $userProfile->getDateOfBirth() !== null ? $userProfile->getDateOfBirth()->format('d/m/Y') : null;

        return new UserProfileDTO(
            $userProfile->getId(),
            $userProfile->getName(),
            $userProfile->getBio(),
            $userProfile->getWebsiteUrl(),
            $userProfile->getLocation(),
            $dateOfBirth,
            $userProfile->getImage()
        );
    }
}
