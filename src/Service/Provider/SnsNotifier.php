<?php

declare(strict_types=1);

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\TransportException;

/**
 * Notifier implementation for sending SMS notifications via AWS SNS.
 *
 * This class uses the AWS SDK's SnsClient to send SMS messages. It implements the NotifierInterface
 * and only supports the 'sms' channel. Logging is performed for both success and failure cases.
 *
 * @see NotifierInterface
 */
class SnsNotifier implements NotifierInterface
{
    /**
     * SnsNotifier constructor.
     *
     * @param SnsClient $snsClient The AWS SNS client instance.
     * @param LoggerInterface $logger The logger for recording send attempts.
     */
    public function __construct(
        private SnsClient $snsClient,
        private LoggerInterface $logger
    ) {
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
     * Sends the notification message as an SMS via SNS.
     *
     * @param NotificationMessage $msg The notification message to send.
     *
     * @throws \Symfony\Component\Messenger\Exception\TransportException If sending fails.
     * @throws \InvalidArgumentException If the recipient is missing.
     */
    public function send(NotificationMessage $msg): void
    {
        $to = $msg->getTo()['sms'] ?? null;
        $body = $msg->getBody() ?? '';

        if (!$to) {
            throw new \InvalidArgumentException('SNS: no SMS recipient');
        }

        try {
            $result = $this->snsClient->publish([
                'Message' => $body,
                'PhoneNumber' => $to,
            ]);
            $this->logger->info('SNS SMS sent', [
                'messageId' => $result->get('MessageId'),
                'to' => $to,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('SNS SMS failed', [
                'error' => $e->getMessage(),
            ]);
            throw new TransportException(
                'SNS publish failed: ' . $e->getMessage()
            );
        }
    }
}
