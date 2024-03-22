<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function sessionTranslate($translationKey, $group = 'messages', $params = []): string
    {
        return $this->translator->trans($translationKey, $params, $group, $this->getSessionLocale());
    }

    private function translateParameters(array $params): array
    {
        if (count($params) > 0) {
            return array_map(function($param) {
                return $this->sessionTranslate($param);
            }, $params);
        }
        return $params;
    }

    public function messageTranslate($translationKey, $params = []): string
    {
        $params = $this->translateParameters($params);
        return $this->sessionTranslate($translationKey, 'messages', $params);
    }

    public function validatorTranslate($translationKey, $params = []): string
    {
        $params = $this->translateParameters($params);
        return $this->sessionTranslate($translationKey, 'validators', $params);
    }

    public function getPostIndexTranslations(): array
    {
        return [
            'author' => $this->sessionTranslate('author'),
            'written' => $this->sessionTranslate('written'),
            'like' => $this->sessionTranslate('post.like'),
            'unlike' => $this->sessionTranslate('post.unlike'),
            'add_to_favorites' => $this->sessionTranslate('post.add_to_favorites'),
            'remove_from_favorites' => $this->sessionTranslate('post.remove_from_favorites'),
            'edit' => $this->sessionTranslate('actions.edit'),
            'comments' => $this->sessionTranslate('the_comments'),
        ];
    }
}
