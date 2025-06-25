<?php

namespace App\Service;

use App\Messenger\NotificationMessage;
use App\Service\Provider\NotifierInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private LoggerInterface $logger;

    /** @var iterable<NotifierInterface> */
    public function __construct(private iterable $providers, LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function notify(NotificationMessage $msg): void
    {
        $lastException = null;

        foreach ($msg->getChannels() as $channel) {
            foreach ($this->providers as $provider) {
                if (!$provider->supports($channel)) {
                    continue;
                }

                try {
                    $provider->send($msg);
                    return;
                } catch (\Throwable $e) {
                    $this->logger->error('Notifier failed', [
                        'channel' => $channel,
                        'exception' => $e->getMessage(),
                    ]);
                    $lastException = $e;
                }
            }
        }

        if ($lastException) {
            // re-throw so your controller can catch it
            throw $lastException;
        }
    }
}
