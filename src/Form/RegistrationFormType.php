<?php

namespace App\Form;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Service\TranslationService;
use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationFormType extends AbstractType
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('user.username_not_empty','validators'),
                    ]),
                ]
            ])
            ->add('email', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('email.not_empty','validators'),
                    ]),
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => $this->translationService->sessionTranslate('agree_terms_fail','validators'),
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => $this->translationService->sessionTranslate('password.dont_match','validators'),
                'attr' => ['autocomplete' => 'new-password'],
                'first_options' => [
                    'label' => 'Password',
                    'mapped' => false
                ],
                'second_options' => [
                    'label' => 'Repeated password',
                    'mapped' => false
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('password.not_empty','validators'),
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => $this->translationService->sessionTranslate('password.limit', 'validators', ['{{ limit }}' => 6]),
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('userProfile', UserProfileType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
