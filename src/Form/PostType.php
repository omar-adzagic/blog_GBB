<?php

namespace App\Form;

use App\Entity\Post;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

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
                'maxSize' => '1024k',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                ],
                'mimeTypesMessage' => $this->translationService->sessionTranslate('image.valid_format','validators'),
            ]),
        ];

        if (!$isUpdate) {
            $imageConstraints[] = new NotBlank([
                'message' => $this->translationService->sessionTranslate('image.not_empty','validators'),
            ]);
        }

        $builder
            ->add('title_en', TextType::class, [
                'label' => $this->translationService->sessionTranslate('post.title_en'),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('title_not_empty','validators'),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('post.title_max_length','validators'),
                    ]),
                ],
            ])
            ->add('title_hr', TextType::class, [
                'label' => $this->translationService->sessionTranslate('post.title_hr','messages'),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('title_not_empty','validators'),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('post.title_max_length','validators'),
                    ]),
                ],
            ])
            ->add('content_en', TextareaType::class, [
                'label' => $this->translationService->sessionTranslate('post.content_en','messages'),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('content_not_empty','validators'),
                    ]),
                    new Length([
                        'max' => 10000,
                        'maxMessage' => $this->translationService->sessionTranslate('post.content_max_length','validators'),
                    ]),
                ],
            ])
            ->add('content_hr', TextareaType::class, [
                'label' => $this->translationService->sessionTranslate('post.content_hr'),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('content_not_empty','validators'),
                    ]),
                    new Length([
                        'max' => 10000,
                        'maxMessage' => $this->translationService->sessionTranslate('post.content_max_length','validators'),
                    ]),
                ],
            ])
            ->add('is_published', CheckboxType::class, [
                'label' => 'Published?',
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
