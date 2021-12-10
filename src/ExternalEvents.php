<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Exception;
use Google\Protobuf\Internal\Message;
use Softonic\LaravelProtobufEvents\Exceptions\InvalidMessageException;
use ReflectionException;
use ReflectionParameter;

class ExternalEvents
{
    private const CAMEL_CASE_LETTERS_DETECTION = '#(?!(?<=^)|(?<=\\\))[A-Z]#';

    public static function publish(Message $class): void
    {
        $routingKey = str_replace(
            '\\',
            '.',
            strtolower(
                preg_replace(
                    self::CAMEL_CASE_LETTERS_DETECTION,
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

    /**
     * @throws InvalidMessageException
     */
    public static function decodeMessage(string $expectedEvent, string $message): mixed
    {
        try {
            $event = new $expectedEvent();
            $event->mergeFromString($message);

            return $event;
        } catch (Exception) {
            throw new InvalidMessageException(
                "The message is not a valid {$expectedEvent} message"
            );
        }
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
