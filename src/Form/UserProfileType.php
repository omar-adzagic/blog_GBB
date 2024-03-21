<?php

namespace App\Form;

use App\Entity\UserProfile;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserProfileType extends AbstractType
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('user.name_max_length','validators'),
                    ]),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1024,
                        'maxMessage' => $this->translationService->sessionTranslate('user.bio_max_length','validators'),
                    ]),
                ],
            ])
            ->add('websiteUrl', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('profile.website_url_max_length','validators'),
                    ]),
                ],
            ])
            ->add('location', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('profile.location_max_length','validators'),
                    ]),
                ],
            ])
            ->add(
                'dateOfBirth',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'constraints' => [
                        new LessThan([
                            'value' => 'today',
                            'message' => $this->translationService->sessionTranslate('past_date','validators'),
                        ]),
                    ],
                ]
            )
            ->add('image', FileType::class, [
                'label' => 'Profile image (JPG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => $this->translationService->sessionTranslate('image.valid_format','validators'),
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class,
        ]);
    }
}
