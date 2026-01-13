Laravel Protobuf Events
====================

[![Latest Version](https://img.shields.io/github/release/softonic/laravel-protobuf-events.svg?style=flat-square)](https://github.com/softonic/laravel-protobuf-events/releases)
[![Software License](https://img.shields.io/badge/license-Apache%202.0-blue.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/softonic/laravel-protobuf-events/tests.yml?branch=master&style=flat-square)](https://github.com/softonic/laravel-protobuf-events/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/softonic/laravel-protobuf-events.svg?style=flat-square)](https://packagist.org/packages/softonic/laravel-protobuf-events)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/softonic/laravel-protobuf-events.svg?style=flat-square)](http://isitmaintained.com/project/softonic/laravel-protobuf-events "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/softonic/laravel-protobuf-events.svg?style=flat-square)](http://isitmaintained.com/project/softonic/laravel-protobuf-events "Percentage of issues still open")

Helper to allow nuwber/rabbitevents to work with protobuf

## Requirements

- PHP >= 8.5
- Laravel 12.x

Main features
-------------

* Allow to publish/listen protobuf messages using nuwber/rabbit-events easily.

Installation
-------------

You can require the last version of the package using composer
```bash
composer require softonic/laravel-protobuf-events
```

### Configuration

First you need to configure the [nuwber/rabbit-events package](https://github.com/nuwber/rabbitevents) to be able
to use the package.

Then you must configure `config/protobuf-events.php` to set the client of the library. This client allows to isolate
different services, identifying the origin of the message.

#### Configuring a listener

In the `RabbitEventsServiceProvider::boot()` register the listeners that you want using the `ExternalEvents::decorateListener()` method.
```php
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        $this->listen = [
            'my.routing.key' => [
                ExternalEvents::decorateListener(MyListener::class),
            ],
        ];

        parent::boot();
    }
```

The listener needs a method called `handle()` that will receive the message and the routing key,
and a method called `setClient()` to identify the origin of the message.
```php
class MyListener
{
    public function setClient(string $client): void
    {
        // ...
    }
    public function handle(ProtobufExampleMessage $event): void
    {
        // ...
    }
}
```

#### Publishing messages

To publish a message, you need to use the `ExternalEvents::publish()` method.
```php
ExternalEvents::publish(
    ':service:',
    (new ProtobufExampleMessage)
        ->setName('My name')
        ->setAge(10)
);
```

#### Advanced usage

Sometimes you need to use the package in a different way than the default. For example, you can use the package to decode
a message from a string. In that case, you are able to decode the message using the `ExternalEvents::decode()` method.

```php
$message = ExternalEvents::decode(
    ProtobufExampleMessage::class,
    '\n My name\n 10\n' // The message is a string with the protobuf message.
);
```

### Logging protobuf messages

If you want to log the outgoing protobuf messages and the incoming ones, you can configure a logger and a formatter for the message to be logged.
For that purpose you have the methods `ExternalEvents::setLogger()` and `ExternalEvents::setFormatter()`.
The logger must implement the `Psr\Log\LoggerInterface` and the formatter, the `LogMessageFormatterInterface` interface.
```php
ExternalEvents::setLogger(Log:getFacadeRoot());
ExternalEvents::setFormatter(new ProtobufLogMessageFormatter());
```
The formatter will have two methods, `formatOutgoingMessage()` and `formatIncomingMessage()`, that will be called when a message is sent or received, respectively.
Both should return a `LogMessage` object, which contains the message to log and the context.

The log level can be changed by setting the `communications_log_level` key in `config/protobuf-events.php`.

Testing
-------

`softonic/laravel-protobuf-events` has a [PHPUnit](https://phpunit.de) test suite, and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).

To run the tests, run the following command from the project folder.

```bash
docker compose run --rm test
```

To run PHPUnit only:

```bash
docker compose run --rm phpunit
```

To check code style:

```bash
docker compose run --rm php composer run checkstyle
```

To fix code style issues:

```bash
docker compose run --rm fixcs
```

To run static analysis:

```bash
docker compose run --rm phpstan
```

To open a terminal in the dev environment:

```bash
docker compose run --rm php sh
```

License
-------

The Apache 2.0 license. Please see [LICENSE](LICENSE) for more information.
