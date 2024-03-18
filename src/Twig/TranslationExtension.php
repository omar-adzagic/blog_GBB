<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TranslationExtension extends AbstractExtension
{
    private $translator;
    private $session;

    public function __construct(TranslatorInterface $translator, SessionInterface $session)
    {
        $this->translator = $translator;
        $this->session = $session;
    }

    public function getFilters(): array
    {
        return [
            // Defines a new Twig filter 'trans_session' we can use in templates
            new TwigFilter('trans_session', [$this, 'translateWithSessionLocale']),
        ];
    }

    public function translateWithSessionLocale(string $key, array $parameters = [], string $domain = null, ?string $locale = null): string
    {
        // If no locale is explicitly passed, try to get it from the session
        $locale = $locale ?? $this->session->get('_locale', 'en');
        return $this->translator->trans($key, $parameters, $domain, $locale);
    }
}
