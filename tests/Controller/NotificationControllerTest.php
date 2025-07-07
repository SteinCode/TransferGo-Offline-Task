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
            'toEmail'  => 'user@example.com',
            'subject'  => 'subj',
            'body'     => 'body',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn($msg) =>
                $msg instanceof NotificationMessage
                && $msg->getChannels() === ['email']
                && $msg->getTo() === ['email' => 'user@example.com']
                && $msg->getSubject() === 'subj'
                && $msg->getBody() === 'body'
            ))
            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                ['sent' => ['email'], 'skipped' => ['sms']]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Sent via: email', $response->getContent());
    }

    public function testSendNotificationWithSmsChannel(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'sms',
            'toSms'    => '+441234567890',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn($msg) =>
                $msg instanceof NotificationMessage
                && $msg->getChannels() === ['sms']
                && $msg->getTo() === ['sms' => '+441234567890']
            ))
            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                ['sent' => ['sms'], 'skipped' => ['email']]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Sent via: sms', $response->getContent());
    }

    public function testSendNotificationWithBothChannelsSuccess(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email,sms',
            'toEmail'  => 'foo@bar.com',
            'toSms'    => '+441234567890',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn($msg) =>
                $msg instanceof NotificationMessage
                && $msg->getChannels() === ['email', 'sms']
                && $msg->getTo() === [
                    'email' => 'foo@bar.com',
                    'sms'   => '+441234567890',
                ]
            ))
            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                ['sent' => ['email', 'sms'], 'skipped' => []]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Sent via: email, sms', $response->getContent());
    }

    public function testInvalidChannelReturnsBadRequest(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'push,carrier-pigeon',
        ]);

        $this->bus->expects($this->never())->method('dispatch');
        $this->logger->expects($this->never())->method('info');

        $response = $this->controller->sendNotification($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString(
            'Invalid channel(s) specified. Allowed channels:',
            $response->getContent()
        );
    }

    public function testMissingAllRecipientsReturnsBadRequest(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email,sms',
        ]);

        $this->bus->expects($this->never())->method('dispatch');
        $this->logger->expects($this->never())->method('info');

        $response = $this->controller->sendNotification($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Email invalid or missing.', $response->getContent());
        $this->assertStringContainsString('SMS invalid or missing', $response->getContent());
    }

    public function testPartialFailureContinuesWithSmsOnly(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'email,sms',
            'toEmail'  => 'invalid-email',
            'toSms'    => '+441234567890',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn($msg) =>
                $msg instanceof NotificationMessage
                && $msg->getChannels() === ['sms']
                && $msg->getTo() === ['sms' => '+441234567890']
            ))
            ->willReturnCallback(fn(NotificationMessage $msg) => new Envelope($msg));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Notification dispatched',
                ['sent' => ['sms'], 'skipped' => ['email']]
            );

        $response = $this->controller->sendNotification($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            'Sent via: sms. Errors: Email invalid or missing.',
            $response->getContent()
        );
    }

    public function testDispatchExceptionReturnsServerError(): void
    {
        $request = Request::create('/send-notification', 'GET', [
            'channels' => 'sms',
            'toSms'    => '+441234567890',
        ]);

        $this->bus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('boom'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Dispatch failed',
                $this->arrayHasKey('exception')
            );

        $this->expectException(\RuntimeException::class);

        $this->controller->sendNotification($request);
    }
}
