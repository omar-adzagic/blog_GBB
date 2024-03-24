<?php

namespace App\Form;

use App\Entity\Post;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostType extends AbstractType
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $post = $options['data'];
        $isUpdate = $post && $post->getId();

        $imageConstraints = [
            new File([
                'maxSize' => '2024k',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                ],
                'mimeTypesMessage' => $this->translationService->validatorTranslate('image.valid_format'),
            ]),
        ];

        if (!$isUpdate) {
            $imageConstraints[] = new NotBlank([
                'message' => $this->translationService->validatorTranslate('image.not_empty'),
            ]);
        }

        $builder
            ->add('title_en', TextType::class, [
                'label' => ucfirst($this->translationService->messageTranslate('post.title_en')),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->validatorTranslate('title_not_empty'),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->validatorTranslate('post.title_max_length'),
                    ]),
                ],
            ])
            ->add('title_hr', TextType::class, [
                'label' => ucfirst($this->translationService->messageTranslate('post.title_hr')),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->validatorTranslate('title_not_empty'),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->validatorTranslate('post.title_max_length'),
                    ]),
                ],
            ])
            ->add('content_en', TextareaType::class, [
                'label' => ucfirst($this->translationService->messageTranslate('post.content_en')),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->validatorTranslate('content_not_empty'),
                    ]),
                    new Length([
                        'max' => 10000,
                        'maxMessage' => $this->translationService->validatorTranslate('post.content_max_length'),
                    ]),
                ],
            ])
            ->add('content_hr', TextareaType::class, [
                'label' => ucfirst($this->translationService->messageTranslate('post.content_hr')),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->validatorTranslate('content_not_empty'),
                    ]),
                    new Length([
                        'max' => 10000,
                        'maxMessage' => $this->translationService->validatorTranslate('post.content_max_length'),
                    ]),
                ],
            ])
            ->add('is_published', CheckboxType::class, [
                'label' => ucfirst($this->translationService->messageTranslate('post.publish?')),
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'label' => 'Profile image (JPG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => $imageConstraints,
            ])
            ->add('postTags', HiddenType::class, [
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
