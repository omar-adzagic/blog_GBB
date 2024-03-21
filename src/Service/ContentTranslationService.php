<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentTranslationService extends TranslationAbstract
{
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack
    )
    {
        parent::__construct($parameterBag, $requestStack);
        $this->entityManager = $entityManager;
    }

    public function setLocaleCreateFormFields($form, array $fields)
    {
        foreach ($this->getSupportedLocales() as $locale) {
            foreach ($fields as $field) {
                $form->get($field . '_' . $locale)->setData('');
            }
        }
    }

    public function setLocaleEditFormFields($form, $entity)
    {
        foreach ($entity->getTranslations() as $translation) {
            $field = $translation->getField() . '_' . $translation->getLocale();
            $form->get($field)->setData($translation->getContent());
        }
    }

    public function setCreateTranslatableFields($entity, $fields, $transEntity, $source)
    {
        foreach ($this->getSupportedLocales() as $locale) {
            foreach ($fields as $field) {
                $content = $source($field, $locale);
                $entityLocaleTranslation = new $transEntity($locale, $field, $content);
                $entityLocaleTranslation->setObject($entity);
                $this->entityManager->persist($entityLocaleTranslation);
            }
        }
    }

    public function setCreateTranslatableFormFields($form, $entity, $fields, $transEntity)
    {
        $this->setCreateTranslatableFields($entity, $fields, $transEntity, function($field, $locale) use ($form) {
            return $form->get($field . '_' . $locale)->getData();
        });
        $this->entityManager->flush();
    }

    public function setEditTranslatableFields($form, $entity)
    {
        foreach ($entity->getTranslations() as $translation) {
            $locale = $translation->getLocale();
            $field = $translation->getField();
            $translation->setContent($form->get($field . '_' . $locale)->getData());
        }
        $this->entityManager->flush();
    }

    public function getTranslationContentForField(iterable $translations, string $field): ?string
    {
        $locale = $this->getSessionLocale();
        foreach ($translations as $translation) {
            if ($translation->getField() === $field && $translation->getLocale() === $locale) {
                return $translation->getContent();
            }
        }

        return null;
    }
}
