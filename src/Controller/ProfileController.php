<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\UserProfileType;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
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
    public function show(
        User $user,
        PostRepository $posts
    ): Response {
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
        EntityManagerInterface $entityManager
    ): Response {
        $existingUserProfile = $user->getUserProfile();
        $userProfile = $existingUserProfile ?? new UserProfile();

        $form = $this->createForm(UserProfileType::class, $userProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userProfile = $form->getData();
            if (!$existingUserProfile) {
                $entityManager->persist($userProfile);
                $user->setUserProfile($userProfile);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Your user profile settings were saved.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('settings_profile/edit.html.twig', [
            'user' => $user,
            'posts' => $posts->findAllByUser($user),
            'form' => $form->createView(),
        ]);
    }
}
