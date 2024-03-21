<?php

namespace App\Form;

use App\Entity\User;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
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
                ],
            ])
            ->add('email', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('email.not_empty','validators'),
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false, // Make this field not required by default
                'invalid_message' => 'The password fields must match.',
                'attr' => ['autocomplete' => 'new-password'],
                'first_options' => [
                    'label' => 'Password',
                    'mapped' => false
                ],
                'second_options' => [
                    'label' => 'Repeat New Password',
                    'mapped' => false
                ],
                // Initially, do not add NotBlank constraint here
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => $this->translationService->sessionTranslate('password.limit', 'validators', ['{{ limit }}' => 6]),
                        'max' => 4096, // max length allowed by Symfony for security reasons
                    ]),
                ],
            ]);

        // Add the PRE_SUBMIT event listener to dynamically add constraints
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            // Check if plainPassword is filled out
            if (!empty($data['plainPassword']['first'])) {
                $form->get('plainPassword')->add('first', PasswordType::class, [
                    'constraints' => [
                        new NotBlank([
                            'message' => $this->translationService->sessionTranslate('password.not_empty','validators'),
                        ]),
                    ],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
