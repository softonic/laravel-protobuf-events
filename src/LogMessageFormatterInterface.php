<?php

namespace Softonic\LaravelProtobufEvents;

use Throwable;

interface LogMessageFormatterInterface
{
    public function formatOutgoingMessage(
        string     $service,
        string     $routingKey,
        array      $message,
        int        $executionTimeMs,
        ?Throwable $exception = null
    ): LogMessage;

    public function formatIncomingMessage(
        string     $routingKey,
        array      $message,
        int        $executionTimeMs,
        ?Throwable $exception = null
    ): LogMessage;
}
