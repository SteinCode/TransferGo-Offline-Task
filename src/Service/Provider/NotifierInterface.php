<?php

declare(strict_types=1);

namespace App\Service\Provider;

use App\Messenger\NotificationMessage;

interface NotifierInterface
{
    /**
     * Return true if this provider can handle the given channel (e.g. 'email', 'sms', 'push').
     */
    public function supports(string $channel): bool;

    /**
     * Send the given notification; throw on failure so the caller can fall back.
     */
    public function send(NotificationMessage $message): void;
}