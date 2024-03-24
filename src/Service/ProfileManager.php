<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ProfileManager
{
    private $entityManager;
    private $authUser;
    private $fileService;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        FileService $fileService
    )
    {
        $this->entityManager = $entityManager;
        $this->fileService = $fileService;
        $this->authUser = $security->getUser();
    }

    public function saveProfileImage($form, $profile, $existingUserProfile)
    {
        $profileImageFile = $form->get('image')->getData();

        if ($profileImageFile) {
            if ($existingUserProfile) {
                $oldFilename = $existingUserProfile->getImage();
                if ($oldFilename) {
                    $this->fileService->deleteFile($oldFilename, '/profile_images');
                }
            }

            $newFileName = $this->fileService->upload($profileImageFile, '/profile_images');
            $profile->setImage($newFileName);
            $this->fileService->resizeImage('profile_images/' . $profile->getImage(), 400, 400);
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
