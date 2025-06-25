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
            $sent = false;

            foreach ($this->providers as $provider) {
                if (!$provider->supports($channel)) {
                    continue;
                }

                try {
                    $provider->send($msg);
                    $this->logger->info(
                        "Notification sent via " . get_class($provider),
                        ['channel' => $channel]
                    );
                    $sent = true;
                    // (don’t return—keep going so we try all channels)
                } catch (\Throwable $e) {
                    $this->logger->error('Notifier failed', [
                        'channel' => $channel,
                        'provider' => get_class($provider),
                        'error' => $e->getMessage(),
                    ]);
                    $lastException = $e;
                }
            }

            // --- failure check per‐channel, now inside the loop so $channel & $sent exist ---
            if (!$sent) {
                throw new \Exception(
                    "All providers failed for channel \"$channel\", will retry later",
                    0,
                    $lastException
                );
            }
        }
    }

}
