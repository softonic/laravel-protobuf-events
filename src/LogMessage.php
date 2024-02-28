<?php

namespace Softonic\LaravelProtobufEvents;

class LogMessage
{
    public function __construct(public readonly string $message, public readonly array $context)
    {
    }
}
