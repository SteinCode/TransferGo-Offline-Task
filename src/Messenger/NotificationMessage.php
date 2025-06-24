<?php
// src/Messenger/NotificationMessage.php

namespace App\Messenger;

final class NotificationMessage
{
    private string $userId;
    private array $channels;
    private array $to;
    private string $template;
    private array $data;
    private ?string $subject;
    private ?string $body;

    public function __construct(
        string $userId,
        array $channels,
        array $to,
        string $template,
        array $data,
        ?string $subject = null,
        ?string $body = null
    ) {
        $this->userId = $userId;
        $this->channels = $channels;
        $this->to = $to;
        $this->template = $template;
        $this->data = $data;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    /** @return string[] */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /** @return array<string,string> */
    public function getTo(): array
    {
        return $this->to;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /** @return mixed[] */
    public function getData(): array
    {
        return $this->data;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }
}
