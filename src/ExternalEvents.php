<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Exception;
use Google\Protobuf\Internal\Message;
use ReflectionException;
use ReflectionParameter;
use Softonic\LaravelProtobufEvents\Exceptions\InvalidMessageException;

class ExternalEvents
{
    private const CAMEL_CASE_LETTERS_DETECTION = '#(?!(?<=^)|(?<=\\\))[A-Z]#';

    public static function publish(Message $class, array $headers = []): void
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

        $message = [
            'data'    => $class->serializeToJsonString(),
            'headers' => $headers,
        ];

        publish($routingKey, $message);
    }

    public static function decorateListener(string $listenerClass): \Closure
    {
        return static function (string $event, array $message) use ($listenerClass) {
            try {
                $listener = resolve($listenerClass);

                if (!empty($message[0]['headers']) && method_exists($listener, 'setHeaders')) {
                    $listener->setHeaders($message[0]['headers']);
                }

                $eventParameter = new ReflectionParameter([$listenerClass, 'handle'], 0);
                $className      = $eventParameter->getType()->getName();

                $payload = ExternalEvents::decode($className, $message[0]['data']);

                return $listener->handle($payload);
            } catch (ReflectionException $e) {
                throw new BadMethodCallException(
                    "$listenerClass must have a handle method with a single parameter of type object child of \Google\Protobuf\Internal\Message"
                );
            }
        };
    }

    /**
     * @throws InvalidMessageException
     */
    public static function decode(string $expectedEvent, string $message): mixed
    {
        try {
            $event = new $expectedEvent();
            $event->mergeFromJsonString($message);

            return $event;
        } catch (Exception $e) {
            throw new InvalidMessageException("The message is not a valid {$expectedEvent} message", 0, $e);
        }
    }
}
