<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Google\Protobuf\Internal\Message;
use ReflectionException;
use ReflectionParameter;

use function publish;

class ExternalEvents
{
    public static function publish(Message $class): void
    {
        $routingKey = str_replace(
            '\\',
            '.',
            strtolower(
                preg_replace(
                    '#(?!(?<=^)|(?<=\\\))[A-Z]#',
                    '_$0',
                    $class::class
                )
            )
        );
        $message    = [
            'data' => $class->serializeToString(),
        ];

        publish($routingKey, $message);
    }

    public static function decodeMessage(string $expectedEvent, string $message): mixed
    {
        $event = new $expectedEvent();
        $event->mergeFromString($message);

        return $event;
    }

    public static function decorateListener(string $class): \Closure
    {
        return static function (array $message) use ($class) {
            try {
                $eventParameter = new ReflectionParameter([$class, 'handle'], 0);
                $className      = $eventParameter->getType()->getName();

                $event = ExternalEvents::decodeMessage($className, $message['data']);
                (new $class())->handle($event);
            } catch (ReflectionException $e) {
                throw new BadMethodCallException(
                    "$class must have a handle method with a single parameter of type object child of \Google\Protobuf\Internal\Message"
                );
            }
        };
    }
}
