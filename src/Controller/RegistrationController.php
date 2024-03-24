<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\FileService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        FileService $fileService
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $imageFile = $form->get('userProfile')->get('image')->getData();
                if ($imageFile) {
                    $newFileName = $fileService->upload($imageFile, '/profile_images');
                    $user->getUserProfile()->setImage($newFileName);
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $entityManager->commit();

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.registration_success',
                    )
                );

                return $this->redirectToRoute('app_post');
            } catch (\Exception $e) {
                $entityManager->rollBack();
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.registration_failed',
                    )
                );
            }
        }

        $responseData = [
            'registrationForm' => $form->createView(),
        ];
        return $this->render('registration/register.html.twig', $responseData);
    }
}
