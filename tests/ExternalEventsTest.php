<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Orchestra\Testbench\TestCase;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;
use Softonic\LaravelProtobufEvents\Exceptions\InvalidMessageException;
use Softonic\LaravelProtobufEvents\FakeProto\FakeMessage;

function publish($routingKey, $message)
{
    assertSame(':service:.softonic.laravel_protobuf_events.fake_proto.fake_message', $routingKey);

    if (empty($message['headers'])) {
        $expectedMessage = [
            'client'    => ':client:',
            'data'    => '{"content":":content:"}',
            'headers' => [],
        ];
    } else {
        $expectedMessage = [
            'client'    => ':client:',
            'data'    => '{"content":":content:"}',
            'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
        ];
    }
    assertSame($expectedMessage, $message);
}

class ExternalEventsTest extends TestCase
{
    /**
     * @test
     */
    public function whenDecodeMessageItShouldReturnTheMessageObject(): void
    {
        $message = new FakeMessage();
        $message->setContent(':content:');
        $codedMessage = $message->serializeToJsonString();

        $decodedMessage = ExternalEvents::decode(FakeMessage::class, $codedMessage);

        self::assertSame(':content:', $decodedMessage->getContent());
    }

    /**
     * @test
     */
    public function whenDecodeAnInvalidMessageItShouldThrowAnException(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectErrorMessage('The message is not a valid ' . FakeMessage::class . ' message');

        $decodedMessage = ExternalEvents::decode(FakeMessage::class, ':invalid-message:');

        self::assertSame(':content:', $decodedMessage->getContent());
    }

    /**
     * @test
     */
    public function whenPublishMessageWithoutHeadersItShouldPublishIt(): void
    {
        $message = new FakeMessage();
        $message->setContent(':content:');
        $service = ':service:';
        $client = ':client:';

        ExternalEvents::publish($service, $message, $client);
    }

    /**
     * @test
     */
    public function whenPublishMessageWithHeadersItShouldPublishIt(): void
    {
        $headers = ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'];

        $message = new FakeMessage();
        $message->setContent(':content:');
        $service = ':service:';
        $client = ':client:';

        ExternalEvents::publish($service, $message, $client, $headers);
    }

    /**
     * @test
     */
    public function whenDecoratingANonValidListenerItShouldThrowAnException(): void
    {
        $invalidListener = new class() {
            public function process()
            {
            }
        };
        $class           = $invalidListener::class;

        $this->expectException(BadMethodCallException::class);
        $this->expectErrorMessage(
            "$class must have a handle method with a single parameter of type object child of \Google\Protobuf\Internal\Message"
        );

        ExternalEvents::decorateListener($invalidListener::class)(':event:', []);
    }

    /**
     * @test
     */
    public function whenDecoratingAListenerWithoutClientItShouldThrowAnException(): void
    {
        $listener = new class() {
            public function handle(FakeMessage $message)
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        $this->expectException(BadMethodCallException::class);

        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'data' => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function whenDecoratingAListenerWithoutHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function setClient(string $client)
            {
                assertSame(':client:', $client);
            }
            public function handle(FakeMessage $message)
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
                    'data' => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function whenDecoratingAListenerWithSetHeadersMethodButWithoutSendingHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function setClient(string $client)
            {
                assertSame(':client:', $client);
            }

            public function setHeaders(array $headers)
            {
                assertTrue(false, 'setHeaders() should not be executed if no headers are received');
            }

            public function handle(FakeMessage $message)
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
                    'data' => $message->serializeToJsonString(),
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function whenDecoratingAListenerWithHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function setClient(string $client)
            {
                assertSame(':client:', $client);
            }

            public function setHeaders(array $headers)
            {
                assertSame(['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'], $headers);
            }

            public function handle(FakeMessage $message)
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
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
            ]
        );
    }
}
