Laravel Protobuf Events
====================

[![Latest Version](https://img.shields.io/github/release/softonic/laravel-protobuf-events.svg?style=flat-square)](https://github.com/softonic/laravel-protobuf-events/releases)
[![Software License](https://img.shields.io/badge/license-Apache%202.0-blue.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://github.com/softonic/laravel-protobuf-events/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/softonic/laravel-protobuf-events/actions/workflows/php.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/softonic/laravel-protobuf-events.svg?style=flat-square)](https://packagist.org/packages/softonic/laravel-protobuf-events)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/softonic/laravel-protobuf-events.svg?style=flat-square)](http://isitmaintained.com/project/softonic/laravel-protobuf-events "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/softonic/laravel-protobuf-events.svg?style=flat-square)](http://isitmaintained.com/project/softonic/laravel-protobuf-events "Percentage of issues still open")

Helper to allow nuwber/rabbitevents to work with protobuf

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

#### Configuring a listener

In the RabbitEventsServiceProvider::boot register the listeners that you want using the ExternalEvents::decorateListener method.
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

The listener needs a method called handle that will receive the message and the routing key.
```php
class MyListener
{
    public function handle(ProtobufExampleMessage $event): void
    {
        // ...
    }
}
```

#### Publishing messages

To publish a message, you need to use the ExternalEvents::publish method.
```php
ExternalEvents::publish(
    (new ProtobufExampleMessage)
        ->setName('My name')
        ->setAge(10)
);
```

#### Advanced usage

Sometimes you need to use the package in a different way than the default. For example, you can use the package to decode
a message from a string. In that case, you are able to decode the message using the ExternalEvents::decode method.

```php
$message = ExternalEvents::decode(
    ProtobufExampleMessage::class,
    '\n My name\n 10\n' // The message is a string with the protobuf message
);
```

Testing
-------

`softonic/laravel-protobuf-events` has a [PHPUnit](https://phpunit.de) test suite, and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).

To run the tests, run the following command from the project folder.

``` bash
$ make tests
```

To open a terminal in the dev environment:
``` bash
$ make debug
```

License
-------

The Apache 2.0 license. Please see [LICENSE](LICENSE) for more information.
