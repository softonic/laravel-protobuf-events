<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Exception;
use Mockery;
use Orchestra\Testbench\TestCase;
use Override;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Softonic\LaravelProtobufEvents\Exceptions\InvalidMessageException;
use Softonic\LaravelProtobufEvents\FakeProto\FakeMessage;

function publish($routingKey, $message): void
{
    assertSame(':service:.softonic.laravel_protobuf_events.fake_proto.fake_message', $routingKey);

    if (empty($message['headers'])) {
        $expectedMessage = [
            'client'  => ':client:',
            'data'    => '{"content":":content:"}',
            'headers' => [],
        ];
    } else {
        $expectedMessage = [
            'client'  => ':client:',
            'data'    => '{"content":":content:"}',
            'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
        ];
    }

    assertSame($expectedMessage, $message);
}

class ExternalEventsTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('protobuf-events.client', ':client:');
        config()->set('protobuf-events.communications_log_level', LogLevel::INFO);
    }

    #[Override]
    protected function tearDown(): void
    {
        ExternalEvents::$logger = null;
        ExternalEvents::$formatter = null;

        parent::tearDown();
    }

    #[Test]
    public function whenDecodeMessageItShouldReturnTheMessageObject(): void
    {
        $message = new FakeMessage();
        $message->setContent(':content:');

        $codedMessage = $message->serializeToJsonString();

        $decodedMessage = ExternalEvents::decode(FakeMessage::class, $codedMessage);

        self::assertSame(':content:', $decodedMessage->getContent());
    }

    #[Test]
    public function whenDecodeAnInvalidMessageItShouldThrowAnException(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message is not a valid ' . FakeMessage::class . ' message');

        $decodedMessage = ExternalEvents::decode(FakeMessage::class, ':invalid-message:');

        self::assertSame(':content:', $decodedMessage->getContent());
    }

    #[Test]
    public function whenPublishMessageWithoutHeadersItShouldPublishIt(): void
    {
        $message = new FakeMessage();
        $message->setContent(':content:');

        $service = ':service:';

        ExternalEvents::publish($service, $message);
    }

    #[Test]
    public function whenPublishMessageWithHeadersItShouldPublishIt(): void
    {
        $headers = ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'];

        $message = new FakeMessage();
        $message->setContent(':content:');

        $service = ':service:';

        ExternalEvents::publish($service, $message, $headers);
    }

    #[Test]
    public function whenPublishMessageWithLoggerAndFormatterItShouldPublishAndLogIt(): void
    {
        $headers = ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'];

        $message = new FakeMessage();
        $message->setContent(':content:');

        $service = ':service:';

        $formatter = Mockery::mock(LogMessageFormatterInterface::class);
        $formatter->shouldReceive('formatOutgoingMessage')
            ->once()
            ->with(
                ':service:.softonic.laravel_protobuf_events.fake_proto.fake_message',
                [
                    'client'  => ':client:',
                    'data'    => '{"content":":content:"}',
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
                $this->isType('int'),
                null
            )
            ->andReturn(new LogMessage(':message:', ['context' => ':context:']));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('log')
            ->once()
            ->with(
                LogLevel::INFO,
                ':message:',
                ['context' => ':context:']
            );

        ExternalEvents::setLogger($logger);
        ExternalEvents::setFormatter($formatter);
        ExternalEvents::publish($service, $message, $headers);
    }

    #[Test]
    public function whenDecoratingAListenerWithoutClientItShouldThrowAnException(): void
    {
        $listener = new class() {
            public function handle(FakeMessage $message): void
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        $class = $listener::class;
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            "{$class} must have a setClient method with a single parameter of type string"
        );

        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'data' => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    #[Test]
    public function whenDecoratingANonValidListenerItShouldThrowAnException(): void
    {
        $invalidListener = new class() {
            public function setClient(string $client): void
            {
                assertSame(':client:', $client);
            }

            public function process(): void
            {
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        $class = $invalidListener::class;
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            "{$class} must have a handle method with a single parameter of type object child of \Google\Protobuf\Internal\Message"
        );

        ExternalEvents::decorateListener($invalidListener::class)(
            ':event:',
            [
                [
                    'client' => ':client:',
                    'data'   => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    #[Test]
    public function whenDecoratingAListenerWithoutHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function setClient(string $client): void
            {
                assertSame(':client:', $client);
            }

            public function handle(FakeMessage $message): void
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'client' => ':client:',
                    'data'   => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    #[Test]
    public function whenDecoratingAListenerWithSetHeadersMethodButWithoutSendingHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function setClient(string $client): void
            {
                assertSame(':client:', $client);
            }

            public function setHeaders(array $headers): void
            {
                assertTrue(false, 'setHeaders() should not be executed if no headers are received');
            }

            public function handle(FakeMessage $message): void
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'client' => ':client:',
                    'data'   => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    #[Test]
    public function whenDecoratingAListenerWithHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function setClient(string $client): void
            {
                assertSame(':client:', $client);
            }

            public function setHeaders(array $headers): void
            {
                assertSame(['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'], $headers);
            }

            public function handle(FakeMessage $message): void
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'client'  => ':client:',
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
            ]
        );
    }

    #[Test]
    public function whenDecoratingAListenerWithLoggerAndFormatterItShouldExecuteAndLogIt(): void
    {
        $listener = new class() {
            public function setClient(string $client): void
            {
                assertSame(':client:', $client);
            }

            public function setHeaders(array $headers): void
            {
                assertSame(['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'], $headers);
            }

            public function handle(FakeMessage $message): void
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        $formatter = Mockery::mock(LogMessageFormatterInterface::class);
        $formatter->shouldReceive('formatIncomingMessage')
            ->once()
            ->with(
                ':event:',
                [
                    'client'  => ':client:',
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
                $this->isType('int'),
                null
            )
            ->andReturn(new LogMessage(':message:', ['context' => ':context:']));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('log')
            ->once()
            ->with(
                LogLevel::INFO,
                ':message:',
                ['context' => ':context:']
            );

        ExternalEvents::setLogger($logger);
        ExternalEvents::setFormatter($formatter);
        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'client'  => ':client:',
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
            ]
        );
    }

    #[Test]
    public function whenDecoratingAListenerWithLoggerAndFormatterButListenerThrowsAnExceptionItShouldLogItAndThrowTheException(): void
    {
        $listener = new class() {
            public function setClient(string $client): void
            {
                assertSame(':client:', $client);
            }

            public function setHeaders(array $headers): void
            {
                assertSame(['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'], $headers);
            }

            public function handle(FakeMessage $message): never
            {
                throw new Exception(':error:');
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        $formatter = Mockery::mock(LogMessageFormatterInterface::class);
        $formatter->shouldReceive('formatIncomingMessage')
            ->once()
            ->with(
                ':event:',
                [
                    'client'  => ':client:',
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],

                ],
                $this->isType('int'),
                $this->isInstanceOf(Exception::class)
            )
            ->andReturn(new LogMessage(':message:', ['context' => ':context:']));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('log')
            ->once()
            ->with(
                LogLevel::ERROR,
                ':message:',
                ['context' => ':context:']
            );

        self::expectException(Exception::class);
        self::expectExceptionMessage(':error:');

        ExternalEvents::setLogger($logger);
        ExternalEvents::setFormatter($formatter);
        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'client'  => ':client:',
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
            ]
        );
    }
}
