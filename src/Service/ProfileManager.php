<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ProfileManager
{
    private $entityManager;
    private $authUser;
    private $fileUploader;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        FileUploader $fileUploader
    )
    {
        $this->entityManager = $entityManager;
        $this->fileUploader = $fileUploader;
        $this->authUser = $security->getUser();
    }

    public function saveProfileImage($form, $profile, $existingUserProfile)
    {
        $profileImageFile = $form->get('image')->getData();

        if ($profileImageFile) {
            if ($existingUserProfile) {
                $oldFilename = $existingUserProfile->getImage();
                if ($oldFilename) {
                    $this->fileUploader->deleteFile($oldFilename, '/profile_images');
                }
            }

            $newFileName = $this->fileUploader->upload($profileImageFile, '/profile_images');
            $profile->setImage($newFileName);
            $this->entityManager->flush();
        }
    }

    public function saveProfile($profile, $existingUserProfile)
    {
        if (!$existingUserProfile) {
            $this->entityManager->persist($profile);
            $this->authUser->setUserProfile($profile);
        }
        $this->entityManager->flush();
    }
}
