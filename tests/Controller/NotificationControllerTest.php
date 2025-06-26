<?php declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\NotificationController;
use App\Messenger\NotificationMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;

class NotificationControllerTest extends TestCase
{
    private MessageBusInterface $bus;
    private LoggerInterface $logger;
    private NotificationController $controller;

    protected function setUp(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new NotificationController($this->bus, $this->logger);
    }

    public function testSendNotificationWithEmailChannel(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email',
            'toEmail' => 'user@example.com',
            'subject' => 'subj',
            'body' => 'body',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($msg) {
                return $msg instanceof NotificationMessage
                    && $msg->getChannels() === ['email']
                    && $msg->getTo() === ['email' => 'user@example.com']
                    && $msg->getSubject() === 'subj'
                    && $msg->getBody() === 'body';
            }))
            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                ['channels' => ['email'], 'to' => ['email' => 'user@example.com']]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Notification sent via: email', $response->getContent());
    }

    public function testSendNotificationWithSmsChannel(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'sms',
            'toSms' => '+441234567890',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($msg) {
                return $msg instanceof NotificationMessage
                    && $msg->getChannels() === ['sms']
                    && $msg->getTo() === ['sms' => '+441234567890'];
            }))

            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                ['channels' => ['sms'], 'to' => ['sms' => '+441234567890']]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Notification sent via: sms', $response->getContent());
    }


    public function testSendNotificationWithBothChannels(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email, sms',
            'toEmail' => 'foo@bar.com',
            'toSms' => '+441234567890',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($msg) {
                return $msg instanceof NotificationMessage
                    && $msg->getChannels() === ['email', 'sms']
                    && $msg->getTo() === [
                        'email' => 'foo@bar.com',
                        'sms' => '+441234567890',
                    ];
            }))
            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                [
                    'channels' => ['email', 'sms'],
                    'to' => ['email' => 'foo@bar.com', 'sms' => '+441234567890'],
                ]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Notification sent via: email, sms', $response->getContent());
    }

    public function testInvalidChannelReturnsBadRequest(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'push,carrier-pigeon',
        ]);

        $this->bus->expects($this->never())->method('dispatch');
        $this->logger->expects($this->never())->method('info');

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString(
            'Invalid channel(s) specified. Allowed channels: email, sms',
            $response->getContent()
        );
    }

    public function testMissingEmailAddressReturnsBadRequest(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email',
        ]);

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid or missing email address.', $response->getContent());
    }

    public function testInvalidSmsNumberReturnsBadRequest(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'sms',
            'toSms' => 'invalid-number',
        ]);

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid or missing SMS number.', $response->getContent());
    }

    public function testBusDispatchExceptionReturnsServerError(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email',
            'toEmail' => 'test@domain.com',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('boom'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Notification dispatch failed',
                $this->arrayHasKey('exception')
            );

        $response = $this->controller->sendNotification($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Failed to send notification.', $response->getContent());
    }
}
