<?php

namespace App\DTO;

final class Notification
{
    public string $userId;
    public array $channels;
    public array $to;
    public string $template;
    public array $data;
}
