<?php

namespace App\Form;

use App\Entity\UserProfile;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;

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
                        'maxMessage' => $this->translationService->validatorTranslate('user.name_max_length'),
                    ]),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1024,
                        'maxMessage' => $this->translationService->validatorTranslate('user.bio_max_length'),
                    ]),
                ],
            ])
            ->add('websiteUrl', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->validatorTranslate('profile.website_url_max_length'),
                    ]),
                ],
            ])
            ->add('location', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->validatorTranslate('profile.location_max_length'),
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
                            'message' => $this->translationService->validatorTranslate('past_date'),
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
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => $this->translationService->validatorTranslate('image.valid_format'),
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
