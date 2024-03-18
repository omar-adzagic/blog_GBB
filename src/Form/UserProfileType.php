<?php

namespace App\Form;

use App\Entity\UserProfile;
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
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $session = $requestStack->getSession();
        $this->locale = $session->get('_locale', 'en');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('user.name_max_length', [], 'validators', $this->locale),
                    ]),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'constraints' => [
                    new Length([
                        'max' => 1024,
                        'maxMessage' => $this->translator->trans('user.bio_max_length', [], 'validators', $this->locale),
                    ]),
                ],
            ])
            ->add('websiteUrl', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('profile.website_url_max_length', [], 'validators', $this->locale),
                    ]),
                ],
            ])
            ->add('location', null, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('profile.location_max_length', [], 'validators', $this->locale),
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
                            'message' => $this->translator->trans('past_date', [], 'validators', $this->locale),
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
                        'mimeTypesMessage' => $this->translator->trans('image.valid_format', [], 'validators', $this->locale),
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
