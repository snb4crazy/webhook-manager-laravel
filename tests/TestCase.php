<?php

namespace WebhookManager\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use WebhookManager\Laravel\Facades\Webhook;
use WebhookManager\Laravel\WebhookManagerServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            WebhookManagerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Webhook' => Webhook::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('webhook-manager.queue', true);
        $app['config']->set('webhook-manager.max_attempts', 3);
        $app['config']->set('webhook-manager.default_secret', 'package-test-secret');
    }
}

