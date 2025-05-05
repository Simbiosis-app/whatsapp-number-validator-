<?php

namespace Simbiosis\WhatsAppNumberValidator;

use Illuminate\Http\Client\Factory as LaravelHttpClientFactory;
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

        // Register the formatter
        $this->app->singleton(PhoneNumberFormatterInterface::class, function ($app) {
            return new PhoneNumberFormatter();
        });

        // Register the logger
        $this->app->singleton(Logger::class, function ($app) {
            return new Logger();
        });

        // Register the HTTP client
        $this->app->singleton(HttpClientInterface::class, function ($app) {
            $config = config('whatsapp.rapidapi');
            return new HttpClient($config, $app->make(LaravelHttpClientFactory::class));
        });

        // Register the validator
        $this->app->bind(WhatsAppValidatorInterface::class, function ($app) {
            $driver = config('whatsapp.driver', 'rapidapi');
            return match($driver) {
                'rapidapi' => $this->createRapidApiValidator($app),
                default => throw new \InvalidArgumentException("Unsupported WhatsApp validator driver: {$driver}")
            };
        });

        // Register concrete validators
        $this->app->bind(RapidApiWhatsAppValidator::class, function ($app) {
            return $this->createRapidApiValidator($app);
        });
    }

    /**
     * Create a RapidApiWhatsAppValidator instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return RapidApiWhatsAppValidator
     */
    protected function createRapidApiValidator($app): RapidApiWhatsAppValidator
    {
        $config = config('whatsapp.rapidapi');
        $httpClient = $app->make(HttpClientInterface::class);
        $formatter = $app->make(PhoneNumberFormatterInterface::class);
        $logger = $app->make(Logger::class);

        return new RapidApiWhatsAppValidator(
            $config,
            $httpClient,
            $formatter,
            $logger
        );
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