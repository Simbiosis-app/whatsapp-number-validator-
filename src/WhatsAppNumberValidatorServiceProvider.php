<?php

namespace Simbiosis\WhatsAppNumberValidator;

use Illuminate\Support\ServiceProvider;

class WhatsAppNumberValidatorServiceProvider extends ServiceProvider
{
 /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/whatsapp.php', 'whatsapp'
        );

        $this->app->bind(WhatsAppValidatorInterface::class, function ($app) {
            $driver = config('whatsapp.driver', 'rapidapi');
            $config = config('whatsapp.rapidapi');
            return match($driver) {
                'rapidapi' => new RapidApiWhatsAppValidator($config),
                default => throw new \InvalidArgumentException("Unsupported WhatsApp validator driver: {$driver}")
            };
        });

        $this->app->bind(RapidApiWhatsAppValidator::class, function ($app) {
            $config = config('whatsapp.rapidapi');
            return new RapidApiWhatsAppValidator($config);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/whatsapp.php' => config_path('whatsapp.php'),
        ], 'whatsapp-config');
    }
}