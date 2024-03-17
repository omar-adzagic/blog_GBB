<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{
    /**
     * @Route("/change-locale/{_locale}", name="change_locale", requirements={"_locale"="en|hr"})
     */
    public function changeLocale(Request $request, $_locale): RedirectResponse
    {
        // Check if the requested locale is supported
        if (!in_array($_locale, $this->getParameter('supported_locales_list'))) {
            throw $this->createNotFoundException('The language does not exist');
        }

        // Set the locale for the current and future requests
        $session = $request->getSession();
        $session->set('_locale', $_locale);
        $request->setLocale($_locale);

        // Redirect to the previous page
        return new RedirectResponse($request->headers->get('referer'));
    }
}