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
        $channels = $this->resolveChannels($request);

        [$to, $errors] = $this->validateRecipients($request, $channels);

        if (empty($channels)) {
            return new Response(
                sprintf(
                    'Invalid channel(s) specified. Allowed channels: %s',
                    implode(', ', self::ALLOWED_CHANNELS)
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($to)) {
            return new Response(
                'No valid channels. Errors: ' . implode('; ', $errors),
                Response::HTTP_BAD_REQUEST
            );
        }

        $message = $this->buildMessage($request, $to);

        $this->dispatch($message, $to, $errors);

        return $this->summaryResponse($to, $errors);
    }

    /**
     * Resolve and filter requested channels from the HTTP request.
     *
     * Reads the "channels" query parameter (comma-separated), trims each value,
     * and returns only those that appear in the ALLOWED_CHANNELS constant.
     *
     * @param Request $request The current HTTP request.
     *
     * @return array
     */
    private function resolveChannels(Request $request)
    {
        $requestedChannels = array_filter(
            array_map('trim', explode(',', $request->query->get('channels', 'email')))
        );

        return array_intersect($requestedChannels, self::ALLOWED_CHANNELS);
    }

    /**
     * Validate recipient addresses for each channel and collect errors.
     *
     * Iterates over the given channels, pulls the corresponding query parameter
     * (toEmail or toSms), applies format validation, and builds a map of
     * channel⇒address for those that passed. Any failures are recorded.
     *
     * @param Request $request  The current HTTP request.
     * @param string[] $channels List of channels to validate (e.g. ['email','sms']).
     *
     * @return array()
     */
    private function validateRecipients($request, $channels)
    {
        $to = [];
        $errors = [];

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    $email = $request->query->get('toEmail');
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $to['email'] = $email;
                    } else {
                        $errors[] = 'Email invalid or missing.';
                    }
                    break;
                case 'sms':
                    $smsRaw = (string) $request->query->get('toSms', '');
                    $digits = preg_replace('/\D+/', '', $smsRaw);
                    $sms = '+' . $digits;

                    if (preg_match('/^\+[1-9]\d{1,14}$/', $sms)) {
                        $to['sms'] = $sms;
                    } else {
                        $errors[] = "SMS invalid or missing";
                    }
            }
        }
        return [$to, $errors];
    }

    /**
     * Build the NotificationMessage for dispatching.
     *
     * Uses the validated recipients map and pulls subject/body overrides
     * from the request (or falls back to defaults) to instantiate the message.
     *
     * @param Request $request The current HTTP request.
     * @param array<string,string> $to Validated map of channel⇒address.
     *
     * @return NotificationMessage
     */
    private function buildMessage($request, $to)
    {
        return new NotificationMessage(
            userId: 'demo-user',
            channels: array_keys($to),
            to: $to,
            data: [],
            subject: $request->query->get('subject', self::DEFAULT_SUBJECT),
            body: $request->query->get('body', self::DEFAULT_BODY)
        );
    }

    /**
     * Dispatch the notification and log outcomes.
     *
     * Sends the given message via the Messenger bus, logs which channels were sent
     * and which were skipped. Rethrows any exception encountered so that the
     * controller can handle it.
     *
     * @param NotificationMessage  $msg The message to dispatch.
     * @param array<string,string> $to Map of channels to addresses actually sent.
     * @param string[] $errors Validation errors collected earlier.
     *
     * @throws \Throwable If dispatching fails.
     */
    private function dispatch(NotificationMessage $msg, array $to, array $errors): void
    {
        try {
            $this->bus->dispatch($msg);
            $this->logger->info('Notification dispatched', [
                'sent'    => array_keys($to),
                'skipped' => array_values(
                    array_diff(self::ALLOWED_CHANNELS, array_keys($to))
                ),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Dispatch failed', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create an HTTP response summarizing sent channels and errors.
     *
     * Formats a human-readable message indicating which channels succeeded
     * and any validation errors that occurred.
     *
     * @param array<string,string> $to Map of channels⇒addresses that were sent.
     * @param string[] $errors List of validation error messages.
     *
     * @return Response The summary response to return to the client.
     */
    private function summaryResponse(array $to, array $errors): Response
    {
        $sent = array_keys($to);
        $fullMessage = [];

        if ($sent) {
            $fullMessage[] = 'Sent via: ' . implode(', ', $sent);
        }
        if ($errors) {
            $fullMessage[] = 'Errors: ' . implode('; ', $errors);
        }

        return new Response(implode('. ', $fullMessage));
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
        $toEmail = $request->query->get('to',);

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return new Response('Invalid email address.', Response::HTTP_BAD_REQUEST);
        }

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['email'],
            to: ['email' => $toEmail],
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
