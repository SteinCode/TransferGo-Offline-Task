<?php

declare(strict_types=1);

namespace App\Service;

use App\Messenger\NotificationMessage;
use App\Service\Provider\NotifierInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NotificationService
{
    /** @var iterable<NotifierInterface> */
    public function __construct(
        private iterable $providers,
        private LoggerInterface $logger,
        #[Autowire('%monolog.logger.notification%')]
        private LoggerInterface $notifierLogger
    ) {
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
                    $this->notifierLogger->info('notification.audit', [
                        'user_id' => $msg->getUserId(),
                        'channel' => $channel,
                        'recipient' => $msg->getTo()[$channel] ?? null,
                        // Monolog already stamps the date/time; you can omit the next line if you like:
                        'sent_at' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
                    ]);
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
