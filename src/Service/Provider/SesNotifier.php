<?php

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SesNotifier implements NotifierInterface
{
    private MailerInterface $mailer;
    private string $from;

    public function __construct(MailerInterface $mailer, string $from)
    {
        $this->mailer = $mailer;
        $this->from = $from;
    }

    public function supports(string $channel): bool
    {
        return $channel === 'email';
    }

    public function send(NotificationMessage $message): void
    {
        $email = (new Email())
            ->from($this->from)
            ->to($message->getTo()['email'])
            ->subject($message->getSubject() ?? 'No subject')
            ->html($message->getBody() ?? '<p>(no body)</p>')
        ;

        $this->mailer->send($email);
    }
}