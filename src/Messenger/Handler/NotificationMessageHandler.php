<?php

namespace App\Messenger\Handler;

use App\Messenger\NotificationMessage;
use App\Service\NotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotificationMessageHandler
{
    public function __construct(private NotificationService $service)
    {
    }

    public function __invoke(NotificationMessage $message): void
    {
        $this->service->notify($message);
    }
}
