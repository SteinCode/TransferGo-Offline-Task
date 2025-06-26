<?php

declare(strict_types=1);

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\RestException;

class TwilioNotifier implements NotifierInterface
{
    private Client $twilioClient;
    private string $from;
    private LoggerInterface $logger;

    public function __construct(Client $twilioClient, string $from, LoggerInterface $logger)
    {
        $this->twilioClient = $twilioClient;
        $this->from = $from;
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

        try {
            $sms = $this->twilioClient->messages->create($to, [
                'from' => $this->from,
                'body' => $body,
            ]);
            $this->logger->info('Twilio SMS sent', [
                'to' => $to,
                'sid' => $sms->sid,
                'status' => $sms->status,
            ]);
        } catch (RestException $e) {
            $this->logger->error('Twilio REST error', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'httpCode' => $e->getStatusCode(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Twilio SMS failed', [
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}