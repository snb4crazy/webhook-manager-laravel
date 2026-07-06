<?php

namespace WebhookManager\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use WebhookManager\Laravel\Contracts\WebhookManagerInterface;
use WebhookManager\Laravel\Services\WebhookSigner;

class WebhookManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/webhook-manager.php', 'webhook-manager');

        $this->app->singleton(WebhookSigner::class);

        $this->app->singleton(WebhookManagerInterface::class, function (Application $app) {
            return new WebhookManager(
                $app->make('Illuminate\\Http\\Client\\Factory'),
                $app->make(WebhookSigner::class),
            );
        });

        $this->app->alias(WebhookManagerInterface::class, WebhookManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webhook-manager.php' => config_path('webhook-manager.php'),
            ], 'webhook-manager-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'webhook-manager-migrations');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            WebhookManagerInterface::class,
            WebhookManager::class,
            WebhookSigner::class,
        ];
    }
}

