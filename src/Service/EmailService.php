<?php

namespace App\Service;

use App\Message\SendEmailMessage;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private $userRepository;
    private $messageBus;

    public function __construct(UserRepository $userRepository, MessageBusInterface $messageBus)
    {
        $this->userRepository = $userRepository;
        $this->messageBus = $messageBus;
    }

    public function sendAdminNewCommentNotification($post, $comment)
    {
        $adminUser = $this->userRepository->findFirstAdmin();
        if ($adminUser) {
            $email = (new TemplatedEmail())
                ->from(new Address('getByBus@test.com', 'Blog GBB'))
                ->to($adminUser->getEmail())
                ->subject('New Comment was Made')
                ->htmlTemplate('email/new_comment.html.twig')
                ->context([
                    'admin' => $adminUser,
                    'comment' => $comment,
                    'post' => $post,
                ]);

            $this->messageBus->dispatch(new SendEmailMessage($email));
        }
    }
}
