<?php

namespace Simbiosis\WhatsAppNumberValidator\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'Simbiosis\WhatsAppNumberValidator\WhatsAppNumberValidatorServiceProvider',
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('whatsapp.driver', 'rapidapi');
        $app['config']->set('whatsapp.rapidapi.key', 'test-key');
        $app['config']->set('whatsapp.rapidapi.host', 'test-host');
        $app['config']->set('whatsapp.rapidapi.endpoint', 'https://test-endpoint.com');
        $app['config']->set('whatsapp.rapidapi.bulk_endpoint', 'https://test-endpoint.com/bulk');
    }
}