<?php

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;
use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\TransportException;

class SnsNotifier implements NotifierInterface
{
    public function __construct(
        private SnsClient $snsClient,
        private LoggerInterface $logger
    ) {
    }

    public function supports(string $channel): bool
    {
        return $channel === 'sms';
    }

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
