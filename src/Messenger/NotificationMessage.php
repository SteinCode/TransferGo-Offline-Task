<?php

declare(strict_types=1);

namespace App\Messenger;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Message object representing a notification to be sent to a user via one or more channels.
 *
 * This message is dispatched to the Messenger bus and handled asynchronously.
 * It contains all necessary data for rendering and delivering a notification.
 *
 * @final
 */

final class NotificationMessage
{
    /**
     * @var string The user identifier to whom the notification is addressed.
     */
    private string $userId;

    /**
     * @var string[] The channels to use for notification delivery (e.g., ['sms', 'email']).
     */
    private array $channels;

    /**
     * @var array<string,string> The recipient addresses per channel (e.g., ['sms' => '+123456789', 'email' => 'user@example.com']).
     */
    private array $to;

    /**
     * @var array<string,mixed> The data to be injected into the template.
     */
    private array $data;

    /**
     * @var string|null The subject of the notification (optional, for channels like email).
     */
    private ?string $subject;

    /**
     * @var string|null The body of the notification (optional, for direct content overrides).
     */
    private ?string $body;

    /**
     * Constructor.
     *
     * @param string $userId   The user identifier.
     * @param string[] $channels   The channels to use for notification delivery.
     * @param array<string,string> $to   The recipient addresses per channel.
     * @param array<string,mixed> $data   The data for the template.
     * @param string|null $subject   The notification subject (optional).
     * @param string|null $body      The notification body (optional).
     */
    public function __construct(
        string $userId,
        array $channels,
        array $to,

        array $data,
        ?string $subject = null,
        ?string $body = null
    ) {
        $this->userId = $userId;
        $this->channels = $channels;
        $this->to = $to;
        $this->data = $data;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Gets the user identifier.
     *
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Gets the channels for notification delivery.
     *
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Gets the recipient addresses per channel.
     *
     * @return array<string,string>
     */
    public function getTo(): array
    {
        return $this->to;
    }


    /**
     * Gets the data for the template.
     *
     * @return array<string,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Gets the notification subject.
     *
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Gets the notification body.
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }
}
