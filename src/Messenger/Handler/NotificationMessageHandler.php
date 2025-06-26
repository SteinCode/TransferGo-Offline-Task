<?php

declare(strict_types=1);

namespace App\Messenger\Handler;

use App\Messenger\NotificationMessage;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles NotificationMessage messages dispatched to the Messenger bus.
 *
 * This handler delegates the notification logic to the NotificationService.
 * If all notification providers fail, an exception is thrown.
 *
 * @see NotificationService
 * @see NotificationMessage
 */
#[AsMessageHandler]
class NotificationMessageHandler
{
    /**
     * @var NotificationService The service responsible for sending notifications.
     */
    private NotificationService $service;

    /**
     * NotificationMessageHandler constructor.
     *
     * @param NotificationService $service The notification service to use.
     */
    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Invokes the handler to process the NotificationMessage.
     *
     * @param NotificationMessage $message The notification message to process.
     *
     * @throws \Exception If all notification providers fail.
     */
    public function __invoke(NotificationMessage $message): void
    {
        $this->service->notify($message);
    }
}
