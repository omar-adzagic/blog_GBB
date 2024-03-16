<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile/{id}", name="app_profile", requirements={"id"="\d+"})
     */
    public function show(User $user, PostRepository $posts): Response {
        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'posts' => $posts->findAllByUser($user)
        ]);
    }

    /**
     * @Route("/profile/edit/{id}", name="app_profile_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        User $user,
        Request $request,
        PostRepository $posts,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): Response {
        $existingUserProfile = $user->getUserProfile();
        $userProfile = $existingUserProfile ?? new UserProfile();

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $profileImageFile = $form->get('image')->getData();

                if ($profileImageFile) {
                    $oldFilename = $existingUserProfile->getImage();
                    if ($oldFilename) {
                        $fileUploader->deleteFile($oldFilename, '/profile_images');
                    }

                    $newFileName = $fileUploader->upload($profileImageFile, '/profile_images');
                    $userProfile->setImage($newFileName);
                    $user->setUserProfile($userProfile);
                }

                $userProfile = $form->getData();
                if (!$existingUserProfile) {
                    $entityManager->persist($userProfile);
                    $user->setUserProfile($userProfile);
                }
                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Your user profile settings were saved.');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'An error occurred. Your user profile could not be updated.');
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('settings_profile/edit.html.twig', [
            'user' => $user,
            'posts' => $posts->findAllByUser($user),
            'form' => $form->createView(),
        ]);
    }
}
