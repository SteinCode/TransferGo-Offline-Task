<?php
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

    #[Route("/test-email", name: "test_email", methods: ["GET"])]
    public function testEmail(Request $request): Response
    {
        $toEmail = $request->query->get('to', 'b4lbo123@gmail.com');

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['email'],
            to: ['email' => $toEmail],
            template: 'TEST_EMAIL',
            data: [],
            subject: '',
            body: '<p>This email was sent via AWS SES from Symfony</p>'
        );

        try {
            $this->bus->dispatch($message);
        } catch (TransportExceptionInterface $e) {
            return new Response('Failed to send email: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response("Email sent to: $toEmail");
    }

    #[Route("/test-sms", "test-sms", methods: ['GET'])]
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
            body: 'Hello hello, how are you?'
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
