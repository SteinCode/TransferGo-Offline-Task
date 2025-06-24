<?php
namespace App\Controller;

use App\Messenger\NotificationMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationController extends AbstractController
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @Route("/test-email", name="test_email")
     */
    public function testEmail(Request $request): Response
    {
        $to = $request->query->get('to', 'b4lbo123@gmail.com');

        $message = new NotificationMessage(
            userId: 'demo-user',
            channels: ['email'],
            to: ['email' => $to],
            template: 'TEST_EMAIL',
            data: [],
            subject: 'First SES test',
            body: '<p>This email was sent via AWS SES from Symfony</p>'
        );
        try {
            $this->bus->dispatch($message);
        } catch (TransportExceptionInterface $e) {
            return new Response('Failed to send email: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response("Email sent to: $to");
    }
}