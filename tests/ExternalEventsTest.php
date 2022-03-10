<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Orchestra\Testbench\TestCase;
use function PHPUnit\Framework\assertSame;
use Softonic\LaravelProtobufEvents\Exceptions\InvalidMessageException;
use Softonic\LaravelProtobufEvents\FakeProto\FakeMessage;

function publish($routingKey, $message)
{
    assertSame('softonic.laravel_protobuf_events.fake_proto.fake_message', $routingKey);

    if (array_key_exists('headers', $message)) {
        $expectedMessage = [
            'data'    => '{"content":":content:"}',
            'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
        ];
    } else {
        $expectedMessage = ['data' => '{"content":":content:"}'];
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
    public function whenPublishMessageItShouldPublishIt(): void
    {
        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::publish($message);
    }

    /**
     * @test
     */
    public function whenPublishMessageWithHeadersItShouldPublishIt(): void
    {
        $headers = ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'];

        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::publish($message, $headers);
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
    public function whenDecoratingAListenerItShouldExecuteIt(): void
    {
        $listener = new class() {
            public function handle(FakeMessage $message)
            {
                assertSame(':content:', $message->getContent());
            }
        };

        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::decorateListener($listener::class)(':event:', [['data' => $message->serializeToJsonString()]]);
    }

    /**
     * @test
     */
    public function whenDecoratingAListenerWithHeadersItShouldExecuteIt(): void
    {
        $listener = new class() {
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
                    'data'    => $message->serializeToJsonString(),
                    'headers' => ['xRequestId' => '7b15d663-8d55-4e2f-82cc-4473576a4a17'],
                ],
            ]
        );
    }
}
