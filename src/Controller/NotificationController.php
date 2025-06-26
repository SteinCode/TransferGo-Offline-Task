<?php

declare(strict_types=1);

namespace App\Controller;

use App\Messenger\NotificationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    private MessageBusInterface $bus;
    private LoggerInterface $logger;

    public function __construct(MessageBusInterface $bus, LoggerInterface $logger)
    {
        $this->bus = $bus;
        $this->logger = $logger;
    }

    #[Route('/send-notification', name: 'test_notification', methods: ['GET'])]
    public function testNotification(Request $request): Response
    {
        $channelsParam = $request->query->get('channels', 'email');
        $channels = explode(',', $channelsParam);

        $to = [];
        foreach ($channels as $ch) {
            $paramKey = $ch === 'email' ? 'toEmail' : 'toSms';
            if ($value = $request->query->get($paramKey)) {
                // for email: expect full address; for sms: expect phone number
                $to[$ch] = $value;
            }
        }

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: $channels,
            to: $to,
            template: 'TEST_NOTIFICATION',
            data: [],
            subject: $request->query->get('subject', 'Hello!'),
            body: $request->query->get('body', '<p>This is a multi-channel test.</p>')
        );

        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->error('notification dispatch via multi-channel endpoint failed', [
                'exception' => $e->getMessage(),
            ]);
            return new Response(
                'Failed to send notification: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response('Sent via: ' . implode(', ', $channels));
    }


    #[Route("/send-email", name: "test_email", methods: ["GET"])]
    public function testEmail(Request $request): Response
    {
        $toEmail = $request->query->get('to', 'b4lbo123@gmail.com');

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['email'],
            to: ['email' => $toEmail],
            template: 'TEST_EMAIL',
            data: [],
            subject: 'Just testing',
            body: 'Test email.'
        );

        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->error('email dispatch failed', [
                'exception' => $e->getMessage(),
            ]);
            return new Response(
                'Failed to send email: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response("Email sent to: $toEmail");
    }

    #[Route("/send-sms", "test-sms", methods: ['GET'])]
    public function testSms(Request $request): Response
    {
        $toSms = $request->query->get('to', '+37060635443');
        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['sms'],
            to: ['sms' => $toSms],
            data: [],
            template: 'TEST_SMS',
            subject: "",
            body: 'Test SMS'
        );
        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->error('SMS dispatch failed', [
                'exception' => $e->getMessage(),
            ]);
            return new Response(
                'Failed to send SMS: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        return new Response("SMS sent to: $toSms");
    }
}
