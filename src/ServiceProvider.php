<?php

namespace Softonic\LaravelProtobufEvents;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Psr\Log\NullLogger;

class ServiceProvider extends LaravelServiceProvider
{
    protected string $packageName = 'protobuf-events';

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes(
            [
                __DIR__ . '/../config/' . $this->packageName . '.php' => config_path($this->packageName . '.php'),
            ],
            'config'
        );
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/' . $this->packageName . '.php', $this->packageName);

        if (ExternalEvents::$logger === null) {
            ExternalEvents::setLogger(new NullLogger());
        }
    }
}
