<?php

namespace Softonic\LaravelProtobufEvents;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServiceProvider extends LaravelServiceProvider
{
    protected string $packageName = 'protobuf-events';

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
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
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/' . $this->packageName . '.php', $this->packageName);

        if (!ExternalEvents::$logger instanceof LoggerInterface) {
            ExternalEvents::setLogger(new NullLogger());
        }
    }
}
