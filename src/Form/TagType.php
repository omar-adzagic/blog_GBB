<?php

namespace App\Form;

use App\Entity\Tag;
use App\Service\TranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class TagType extends AbstractType
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name_en', TextType::class, [
                'label' => $this->translationService->sessionTranslate('tag.name_en', 'validators'),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('name_not_empty', 'validators'),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('tag.name_max_length', 'validators'),
                    ]),
                ],
            ])
            ->add('name_hr', TextType::class, [
                'label' => $this->translationService->sessionTranslate('tag.name_hr', 'validators'),
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translationService->sessionTranslate('name_not_empty','validators'),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translationService->sessionTranslate('tag.name_max_length', 'validators'),
                    ]),
                ],
            ])
            ->add('translations', CollectionType::class, [
                'entry_type' => TagTranslationType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__translation_name__',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
        ]);
    }
}
