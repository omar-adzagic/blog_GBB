<?php

namespace App\Form;

use App\Entity\Tag;
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
            ->add('name_en', TextType::class, [
                'label' => 'Tag Name in English',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('name_not_empty', [], 'validators', $this->locale),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('tag.name_max_length', [], 'validators', $this->locale),
                    ]),
                ],
            ])
            ->add('name_hr', TextType::class, [
                'label' => 'Tag Name in Croatian',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('name_not_empty', [], 'validators', $this->locale),
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('tag.name_max_length', [], 'validators', $this->locale),
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
