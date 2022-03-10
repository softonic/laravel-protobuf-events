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
    assertSame(
        [
            'data'         => '{"content":":content:"}',
            'xRequestId'   => '7b15d663-8d55-4e2f-82cc-4473576a4a17',
            'xMsgPriority' => 5,
        ],
        $message
    );
}

class ExternalEventsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_REQUEST_ID'], $_SERVER['HTTP_X_MSG_PRIORITY']);

        parent::tearDown();
    }

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
        $_SERVER['HTTP_X_REQUEST_ID']   = '7b15d663-8d55-4e2f-82cc-4473576a4a17';
        $_SERVER['HTTP_X_MSG_PRIORITY'] = 5;

        $message = new FakeMessage();
        $message->setContent(':content:');

        ExternalEvents::publish($message);
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

        ExternalEvents::decorateListener($listener::class)(
            ':event:',
            [
                [
                    'data'         => $message->serializeToJsonString(),
                    'xRequestId'   => '7b15d663-8d55-4e2f-82cc-4473576a4a17',
                    'xMsgPriority' => 5,
                ],
            ]
        );

        self::assertSame('7b15d663-8d55-4e2f-82cc-4473576a4a17', $_SERVER['HTTP_X_REQUEST_ID']);
        self::assertSame(5, $_SERVER['HTTP_X_MSG_PRIORITY']);
    }
}
