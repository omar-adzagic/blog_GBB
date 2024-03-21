<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentTranslationExtension extends AbstractExtension
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_translation', [$this, 'getTranslation']),
        ];
    }

    public function getTranslation($entity, $field): ?string
    {
        $locale = $this->session->get('_locale', 'en');

        foreach ($entity->getTranslations() as $translation) {
            if ($field === $translation->getField() && $translation->getLocale() === $locale) {
                return $translation->getContent();
            }
        }
        return '';
    }
}
