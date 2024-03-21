<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class TranslationAbstract {
    private $locales;
    private $currentRequest;

    public function __construct(ParameterBagInterface $parameterBag, RequestStack $requestStack)
    {
        $this->locales = $parameterBag->get('supported_locales_list');
        $this->currentRequest = $requestStack->getCurrentRequest();
    }

    public function getSessionLocale(): string
    {
        if (null === $this->currentRequest) {
            return 'en';
        }

        return $this->currentRequest->getSession()->get('_locale', 'en');
    }

    public function getSupportedLocales(): array
    {
        return $this->locales;
    }
}