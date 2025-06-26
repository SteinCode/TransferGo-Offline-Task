<?php

declare(strict_types=1);

namespace App\Service;

use App\Messenger\NotificationMessage;
use App\Service\Provider\NotifierInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service responsible for sending notifications to users via multiple channels and providers.
 *
 * This service iterates over all requested channels and attempts to send the notification using all
 * available providers that support the channel. If all providers fail for a channel, an exception is thrown.
 * Audit logs are written for successful sends, and errors are logged for failures.
 */
class NotificationService
{
    /**
     * @var iterable<NotifierInterface> The notification providers available for sending messages.
     */
    private iterable $providers;

    /**
     * @var LoggerInterface Logger for general errors and failures.
     */
    private LoggerInterface $logger;

    /**
     * @var LoggerInterface Logger for notification audit events.
     */
    private LoggerInterface $notifierLogger;

    /**
     * NotificationService constructor.
     *
     * @param iterable<NotifierInterface> $providers The notification providers.
     * @param LoggerInterface $logger Logger for errors.
     * @param LoggerInterface $notifierLogger Logger for audit events.
     */
    public function __construct(
        iterable $providers,
        LoggerInterface $logger,
        #[Autowire('%monolog.logger.notification%')]
        LoggerInterface $notifierLogger
    ) {
        $this->providers = $providers;
        $this->logger = $logger;
        $this->notifierLogger = $notifierLogger;
    }

    /**
     * Sends a notification message to the user via all requested channels.
     *
     * For each channel, all providers that support it are tried in order. If all providers fail,
     * an exception is thrown. Audit logs are written for successful sends, and errors are logged for failures.
     *
     * @param NotificationMessage $msg The notification message to send.
     *
     * @throws \Exception If all providers fail for any channel.
     */
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

            if (!$sent) {
                throw new \Exception(
                    "All providers failed, Last error: " . ($lastException ? $lastException->getMessage() : 'unknown'),
                    0,
                    $lastException
                );
            }
        }
    }
}
