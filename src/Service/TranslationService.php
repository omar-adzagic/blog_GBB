<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationService extends TranslationAbstract
{
    private $translator;

    public function __construct(
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack,
        TranslatorInterface $translator
    )
    {
        parent::__construct($parameterBag, $requestStack);
        $this->translator = $translator;
    }

    public function sessionTranslate($translationKey, $group='messages', $params=[]): string
    {
        return $this->translator->trans($translationKey, $params, $group, $this->getSessionLocale());
    }

    public function getPostIndexTranslations(): array
    {
        return [
            'author' => $this->sessionTranslate('author'),
            'written' => $this->sessionTranslate('written'),
            'like' => $this->sessionTranslate('like'),
            'unlike' => $this->sessionTranslate('unlike'),
            'add_to_favorites' => $this->sessionTranslate('add_to_favorites'),
            'remove_from_favorites' => $this->sessionTranslate('remove_from_favorites'),
            'edit' => $this->sessionTranslate('actions.edit'),
            'comments' => $this->sessionTranslate('the_comments'),
        ];
    }
}
