<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;

class PostType extends AbstractType
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
        $post = $options['data'];
        $isUpdate = $post && $post->getId();

        $imageConstraints = [
            new File([
                'maxSize' => '1024k',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                ],
                'mimeTypesMessage' => $this->translator->trans('image.valid_format', [], 'validators', $this->locale),
            ]),
        ];

        if (!$isUpdate) {
            $imageConstraints[] = new NotBlank([
                'message' => $this->translator->trans('image.not_empty', [], 'validators', $this->locale),
            ]);
        }

        $builder
            ->add('title', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('title_not_empty', [], 'validators', $this->locale),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('post.title_max_length', [], 'validators', $this->locale),
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('content_not_empty', [], 'validators', $this->locale),
                    ]),
                    new Length([
                        'max' => 10000,
                        'maxMessage' => $this->translator->trans('post.content_max_length', [], 'validators', $this->locale),
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
