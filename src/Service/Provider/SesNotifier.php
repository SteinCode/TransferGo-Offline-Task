<?php

declare(strict_types=1);

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

/**
 * Notifier implementation for sending email notifications via AWS SES.
 *
 * This class uses Symfony's Mailer component to send emails. It implements the NotifierInterface
 * and only supports the 'email' channel. The sender address is injected via constructor.
 *
 * @see NotifierInterface
 */
class SesNotifier implements NotifierInterface
{
    /**
     * @var MailerInterface The mailer service used to send emails.
     */
    private MailerInterface $mailer;

    /**
     * @var string The sender email address.
     */
    private string $from;

    /**
     * @var LoggerInterface The logger for recording send attempts.
     */
    private LoggerInterface $logger;

    /**
     * SesNotifier constructor.
     *
     * @param MailerInterface $mailer The mailer service.
     * @param string $from The sender email address.
     * @param LoggerInterface $logger The logger for recording send attempts.
     */
    public function __construct(MailerInterface $mailer, string $from, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->from = $from;
        $this->logger = $logger;
    }

    /**
     * Returns true if this notifier supports the given channel.
     *
     * @param string $channel The notification channel (e.g., 'email').
     * @return bool True if supported, false otherwise.
     */
    public function supports(string $channel): bool
    {
        return $channel === 'email';
    }

    /**
     * Sends the notification message as an email via SES.
     *
     * @param NotificationMessage $message The notification message to send.
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface If sending fails.
     */
    public function send(NotificationMessage $message): void
    {
        $email = (new Email())
            ->from($this->from)
            ->to($message->getTo()['email'])
            ->subject($message->getSubject() ?? 'No subject')
            ->html($message->getBody() ?? 'no body');

        try {
            $this->mailer->send($email);
            $this->logger->info('SES email sent', [
                'to' => $message->getTo()['email'],
                'subject' => $message->getSubject(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('SES email failed', [
                'to' => $message->getTo()['email'],
                'subject' => $message->getSubject(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}