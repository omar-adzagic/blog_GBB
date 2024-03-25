<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PostControllerTest extends WebTestCase
{
    /**
     * INDEX
     */
    public function testIndex(): void
    {
        $client = static::createClient();

        $res = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div a', 'Home');
    }

    /**
     * CREATE
     */

//    public function testCreatePostWhileLoggedIn(): void
//    {
//        $client = static::createClient();
//
//        // Assume you have a method to log in as an admin user
//        $this->logInAsAdmin($client);
//
//        $crawler = $client->request('GET', '/post/create');
//
//        $this->assertResponseIsSuccessful();
//    }

//    private function logInAsAdmin($client)
//    {
//        $session = self::$container->get('session');
//
//        // Assuming you have a security firewall named 'main'
//        $firewallContext = 'main';
//
//        $adminUser = $this->getAdminUser();
//
//        $token = new UsernamePasswordToken($adminUser, null, $firewallContext, $adminUser->getRoles());
//        self::$container->get('security.token_storage')->setToken($token);
//
//        // If the session is not started, start it
//        if (!$session->isStarted()) {
//            $session->start();
//        }
//
//        $session->set('_security_'.$firewallContext, serialize($token));
//        $session->save();
//
//        $cookie = new Cookie($session->getName(), $session->getId());
//        $client->getCookieJar()->set($cookie);
//    }
}
