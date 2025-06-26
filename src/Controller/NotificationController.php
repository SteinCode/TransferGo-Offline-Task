<?php

declare(strict_types=1);

namespace App\Controller;

use App\Messenger\NotificationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for testing notification dispatch via email and SMS channels.
 * Provides endpoints to demonstrate and test the messaging flow.
 */
class NotificationController extends AbstractController
{
    private MessageBusInterface $bus;
    private LoggerInterface $logger;

    /**
     * NotificationController constructor.
     *
     * @param MessageBusInterface $bus Message bus for dispatching messages
     * @param LoggerInterface $logger Logger for recording events and errors
     */
    public function __construct(MessageBusInterface $bus, LoggerInterface $logger)
    {
        $this->bus = $bus;
        $this->logger = $logger;
    }

    /**
     * Main endpoint to test notification via specified channels (email, sms).
     *
     * Query parameters:
     * - channels: comma-separated channels ('email','sms'), defaults to 'email'.
     * - toEmail: recipient email address for email channel.
     * - toSms: recipient phone number for sms channel.
     * - subject: notification subject, defaults to DEFAULT_SUBJECT.
     * - body: notification body, defaults to DEFAULT_BODY.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/send-notification', name: 'send_notification', methods: ['GET'])]
    public function sendNotification(Request $request): Response
    {
        $channelsParam = $request->query->get('channels', 'email');
        $channels = explode(',', $channelsParam);

        $to = [];
        foreach ($channels as $ch) {
            $paramKey = $ch === 'email' ? 'toEmail' : 'toSms';
            if ($value = $request->query->get($paramKey)) {
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

    /**
     * @deprecated this method was initially made for testing and learning purposes, use sendNotification() instead.
     * 
     * Send a test email notification.
     *
     * Query parameters:
     * - to: recipient email address, defaults to 'b4lbo123@gmail.com'.
     *
     * @param Request $request
     * @return Response
     */
    #[Route("/send-email", name: "send_email", methods: ["GET"])]
    public function sendEmail(Request $request): Response
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

    /**
     * @deprecated this method was initially made for testing and learning purposes, use sendNotification() instead.
     * Send a test SMS notification.
     *
     * Query parameters:
     * - to: recipient phone number in E.164 format, defaults to '+37060635443' (My number).
     *
     * @param Request $request
     * @return Response
     */
    #[Route("/send-sms", "send_sms", methods: ['GET'])]
    public function sendSms(Request $request): Response
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
