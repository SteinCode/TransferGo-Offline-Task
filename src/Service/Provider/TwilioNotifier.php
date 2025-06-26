<?php

declare(strict_types=1);

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\RestException;

/**
 * Notifier implementation for sending SMS notifications via Twilio.
 *
 * This class uses the Twilio PHP SDK to send SMS messages. It implements the NotifierInterface
 * and only supports the 'sms' channel. Logging is performed for both success and failure cases.
 *
 * @see NotifierInterface
 */
class TwilioNotifier implements NotifierInterface
{
    /**
     * @var Client The Twilio client instance.
     */
    private Client $twilioClient;

    /**
     * @var string The sender phone number registered with Twilio.
     */
    private string $from;

    /**
     * @var LoggerInterface The logger for recording send attempts.
     */
    private LoggerInterface $logger;

    /**
     * TwilioNotifier constructor.
     *
     * @param Client $twilioClient The Twilio client instance.
     * @param string $from The sender phone number.
     * @param LoggerInterface $logger The logger for recording send attempts.
     */
    public function __construct(Client $twilioClient, string $from, LoggerInterface $logger)
    {
        $this->twilioClient = $twilioClient;
        $this->from = $from;
        $this->logger = $logger;
    }

    /**
     * Returns true if this notifier supports the given channel.
     *
     * @param string $channel The notification channel (e.g., 'sms').
     * @return bool True if supported, false otherwise.
     */
    public function supports(string $channel): bool
    {
        return $channel === 'sms';
    }

    /**
     * Sends the notification message as an SMS via Twilio.
     *
     * @param NotificationMessage $message The notification message to send.
     *
     * @throws RestException If the Twilio API returns an error.
     * @throws \InvalidArgumentException If the recipient is missing.
     * @throws \Throwable For any other error during sending.
     */
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