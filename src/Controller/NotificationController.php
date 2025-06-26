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
    private const DEFAULT_SUBJECT = 'Hello!';
    private const DEFAULT_BODY = 'This is a multi-channel test.';
    private const ALLOWED_CHANNELS = ['email', 'sms'];

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
        $requestedChannels = array_filter(
            array_map('trim', explode(',', $request->query->get('channels', 'email')))
        );

        $channels = array_values(array_intersect($requestedChannels, self::ALLOWED_CHANNELS));
        if (empty($channels)) {
            return new Response(
                sprintf('Invalid channel(s) specified. Allowed channels: %s', implode(', ', self::ALLOWED_CHANNELS)),
                Response::HTTP_BAD_REQUEST
            );
        }

        $to = [];
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    $email = $request->query->get('toEmail');
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $to['email'] = $email;
                    } else {
                        return new Response('Invalid or missing email address.', Response::HTTP_BAD_REQUEST);
                    }
                    break;
                case 'sms':
                    $smsRaw = (string) $request->query->get('toSms', '');
                    $digits = preg_replace('/\D+/', '', $smsRaw);
                    $sms = '+' . $digits;

                    if (!preg_match('/^\+[1-9]\d{1,14}$/', $sms)) {
                        return new Response('Invalid or missing SMS number.', Response::HTTP_BAD_REQUEST);
                    }
                    $to['sms'] = $sms;
                    break;
            }
        }

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: $channels,
            to: $to,
            template: 'TEST_NOTIFICATION',
            data: [],
            subject: $request->query->get('subject', self::DEFAULT_SUBJECT),
            body: $request->query->get('body', self::DEFAULT_BODY)
        );

        try {
            $this->bus->dispatch($message);
            $this->logger->info('Notification dispatched', ['channels' => $channels, 'to' => $to]);
        } catch (\Throwable $e) {
            $this->logger->error('Notification dispatch failed', ['exception' => $e]);

            return new Response(
                'Failed to send notification.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response('Notification sent via: ' . implode(', ', $channels));
    }

    /**
     * @deprecated this method was initially made for testing and learning purposes, use sendNotification() instead.
     * 
     * Send a test email notification.
     *
     * Query parameters:
     * - to: recipient email address..
     *
     * @param Request $request
     * @return Response
     */
    #[Route("/send-email", name: "send_email", methods: ["GET"])]
    public function sendEmail(Request $request): Response
    {
        $toEmail = $request->query->get('to', );

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return new Response('Invalid email address.', Response::HTTP_BAD_REQUEST);
        }

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['email'],
            to: ['email' => $toEmail],
            template: 'TEST_EMAIL',
            data: [],
            subject: 'Just testing',
            body: 'Test email'
        );

        try {
            $this->bus->dispatch($message);
            $this->logger->info('Email dispatched', ['to' => $toEmail]);
        } catch (\Throwable $e) {
            $this->logger->error('Email dispatch failed', ['exception' => $e]);

            return new Response(
                'Failed to send email.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response(sprintf('Email sent to: %s', $toEmail));
    }

    /**
     * @deprecated this method was initially made for testing and learning purposes, use sendNotification() instead.
     * Send a test SMS notification.
     *
     * Query parameters:
     * - to: recipient phone number in E.164 format.
     *
     * @param Request $request
     * @return Response
     */
    #[Route("/send-sms", "send_sms", methods: ['GET'])]
    public function sendSms(Request $request): Response
    {
        $smsRaw = (string) $request->query->get('toSms', '');
        $digits = preg_replace('/\D+/', '', $smsRaw);
        $sms = '+' . $digits;

        if (!preg_match('/^\+[1-9]\d{1,14}$/', $sms)) {
            return new Response('Invalid or missing SMS number.', Response::HTTP_BAD_REQUEST);
        }

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['sms'],
            to: ['sms' => $sms],
            template: 'TEST_SMS',
            data: [],
            subject: '',
            body: 'Hello hello, how are you?'
        );

        try {
            $this->bus->dispatch($message);
            $this->logger->info('SMS dispatched', ['to' => $sms]);
        } catch (\Throwable $e) {
            $this->logger->error('SMS dispatch failed', ['exception' => $e]);

            return new Response(
                'Failed to send SMS.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response(sprintf('SMS sent to: %s', $sms));
    }
}
