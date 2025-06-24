<?php

namespace App\Service;

use App\Messenger\NotificationMessage;
use App\Service\Provider\NotifierInterface;

class NotificationService
{
    /** @var iterable<NotifierInterface> */
    public function __construct(private iterable $providers)
    {
    }

    public function notify(NotificationMessage $msg): void
    {
        foreach ($msg->getChannels() as $channel) {
            foreach ($this->providers as $p) {
                if (!$p->supports($channel)) {
                    continue;
                }
                try {
                    $p->send($msg);
                    break;
                } catch (\Throwable $e) {
                    // will log a problem and try another provider
                }
            }
        }
    }
}
