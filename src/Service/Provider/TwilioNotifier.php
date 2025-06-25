<?php

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class TwilioNotifier implements NotifierInterface
{
    private Client $twilioClient;
    private string $form;
    private LoggerInterface $logger;

    public function __construct(Client $twilioClient, string $form, LoggerInterface $logger)
    {
        $this->twilioClient = $twilioClient;
        $this->form = $form;
        $this->logger = $logger;
    }

    public function supports(string $channel): bool
    {
        return $channel === 'sms';
    }

    public function send(NotificationMessage $message): void
    {
        $to = $message->getTo()['sms'] ?? null;
        $body = $message->getBody() ?? $message->getSubject() ?? '';

        if (!$to) {
            throw new \InvalidArgumentException('No SMS recipient defined');
        }


    }


}