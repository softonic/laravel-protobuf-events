<?php

namespace Softonic\LaravelProtobufEvents;

use BadMethodCallException;
use Exception;
use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionException;
use ReflectionParameter;
use Softonic\LaravelProtobufEvents\Exceptions\InvalidMessageException;

class ExternalEvents
{
    public static ?LoggerInterface $logger;

    public static ?LogMessageFormatterInterface $formatter;

    private const CAMEL_CASE_LETTERS_DETECTION = '#(?!(?<=^)|(?<=\\\))[A-Z]#';

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function setFormatter(LogMessageFormatterInterface $formatter): void
    {
        self::$formatter = $formatter;
    }

    public static function publish(string $service, Message $class, array $headers = []): void
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

        $routingKey = $service . '.' . $routingKey;

        $message = [
            'client'  => config('protobuf-events.client'),
            'data'    => $class->serializeToJsonString(),
            'headers' => $headers,
        ];

        try {
            $startTimeMs = microtime(true);

            publish($routingKey, $message);

            $level = LogLevel::INFO;
        } catch (Exception $exception) {
            $level = LogLevel::ERROR;
        }

        if (isset(self::$formatter)) {
            $endTimeMs       = microtime(true);
            $executionTimeMs = ($endTimeMs - $startTimeMs) * 1000;

            $logMessage = self::$formatter->formatOutgoingMessage(
                $service,
                $routingKey,
                $message,
                $executionTimeMs,
                $exception ?? null
            );

            self::$logger->log($level, $logMessage->message, $logMessage->context);
        }

        if (isset($exception)) {
            throw $exception;
        }
    }

    public static function decorateListener(string $listenerClass): \Closure
    {
        return static function (string $event, array $message) use ($listenerClass) {
            try {
                $startTimeMs = microtime(true);

                $listener = resolve($listenerClass);

                if (!method_exists($listener, 'setClient')) {
                    throw new BadMethodCallException(
                        "$listenerClass must have a setClient method with a single parameter of type string"
                    );
                }
                $listener->setClient($message[0]['client']);

                if (!empty($message[0]['headers']) && method_exists($listener, 'setHeaders')) {
                    $listener->setHeaders($message[0]['headers']);
                }

                $eventParameter = new ReflectionParameter([$listenerClass, 'handle'], 0);
                $className      = $eventParameter->getType()->getName();

                $payload = ExternalEvents::decode($className, $message[0]['data']);

                $response = $listener->handle($payload);

                $level = LogLevel::INFO;
            } catch (ReflectionException $e) {
                throw new BadMethodCallException(
                    "$listenerClass must have a handle method with a single parameter of type object child of \Google\Protobuf\Internal\Message"
                );
            } catch (Exception $exception) {
                $level = LogLevel::ERROR;
            }

            if (isset(self::$formatter)) {
                $endTimeMs       = microtime(true);
                $executionTimeMs = ($endTimeMs - $startTimeMs) * 1000;

                $logMessage = self::$formatter->formatIncomingMessage(
                    $event,
                    $message[0],
                    $executionTimeMs
                );

                self::$logger->log($level, $logMessage->message, $logMessage->context);
            }

            return isset($exception) ? throw $exception : $response;
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
