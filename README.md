# WhatsApp Number Validator

A PHP package for validating WhatsApp numbers. This package is compatible with both Laravel and vanilla PHP applications.

## Installation

You can install the package via composer:

```bash
composer require simbiosis/whatsapp-number-validator
```

### Laravel

The package will automatically register itself in Laravel applications. You can publish the configuration file:

```bash
php artisan vendor:publish --tag=whatsapp-config
```

### Vanilla PHP

For vanilla PHP applications, you can use the package directly:

```php
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

// Create the validator
$validator = new RapidApiWhatsAppValidator([
    'endpoint' => 'your-endpoint',
    'bulk_endpoint' => 'your-bulk-endpoint',
    'key' => 'your-api-key',
    'host' => 'your-api-host'
]);
```

## Configuration

The package provides several configuration options that you can customize. After publishing the config file, you can modify the settings in `config/whatsapp.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Validator Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default WhatsApp validator driver that will be used
    | to validate phone numbers. Currently only "rapidapi" is supported.
    |
    */
    'driver' => env('WHATSAPP_DRIVER', 'rapidapi'),

    /*
    |--------------------------------------------------------------------------
    | RapidAPI Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the RapidAPI WhatsApp validator settings.
    | Required:
    |   - key: Your RapidAPI key
    |   - host: The RapidAPI host
    |   - endpoint: The API endpoint for single number validation
    |   - bulk_endpoint: The API endpoint for bulk validation
    |
    | Optional:
    |   - timeout: Request timeout in seconds (default: 30)
    |   - retry_attempts: Number of retry attempts (default: 3)
    |
    */
    'rapidapi' => [
        'endpoint' => env('WHATSAPP_RAPIDAPI_ENDPOINT', 'https://whatsapp-number-validator3.p.rapidapi.com/WhatsappNumberHasItWithToken'),
        'bulk_endpoint' => env('WHATSAPP_RAPIDAPI_BULK_ENDPOINT', 'https://whatsapp-number-validator3.p.rapidapi.com/WhatsappNumberHasItBulkWithToken'),
        'key' => env('WHATSAPP_RAPIDAPI_KEY'),
        'host' => env('WHATSAPP_RAPIDAPI_HOST', 'whatsapp-number-validator3.p.rapidapi.com'),
        'timeout' => env('WHATSAPP_RAPIDAPI_TIMEOUT', 30),
        'retry_attempts' => env('WHATSAPP_RAPIDAPI_RETRY_ATTEMPTS', 3),
    ],
];
```

### Environment Variables

Add these variables to your `.env` file:

```env
# WhatsApp Validator Driver
WHATSAPP_DRIVER=rapidapi

# RapidAPI Configuration
WHATSAPP_RAPIDAPI_KEY=your-api-key
WHATSAPP_RAPIDAPI_HOST=whatsapp-number-validator3.p.rapidapi.com
WHATSAPP_RAPIDAPI_ENDPOINT=https://whatsapp-number-validator3.p.rapidapi.com/WhatsappNumberHasItWithToken
WHATSAPP_RAPIDAPI_BULK_ENDPOINT=https://whatsapp-number-validator3.p.rapidapi.com/WhatsappNumberHasItBulkWithToken
WHATSAPP_RAPIDAPI_TIMEOUT=30
WHATSAPP_RAPIDAPI_RETRY_ATTEMPTS=3
```

## Usage

### Phone Number Format

The package expects phone numbers to be in international format:
- Must start with a plus sign (+)
- Followed by the country code and number
- No spaces or special characters
- Example: `+1234567890`

### Basic Usage

```php
// Laravel
use Simbiosis\WhatsAppNumberValidator\WhatsAppValidatorInterface;

$validator = app(WhatsAppValidatorInterface::class);
$isValid = $validator->validate('+1234567890');

// Vanilla PHP
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

$config = [
    'endpoint' => 'your-endpoint',
    'bulk_endpoint' => 'your-bulk-endpoint',
    'key' => 'your-api-key',
    'host' => 'your-api-host'
];

$validator = new RapidApiWhatsAppValidator($config);
$isValid = $validator->validate('+1234567890');
```

### Bulk Validation

```php
// Laravel
use Simbiosis\WhatsAppNumberValidator\WhatsAppValidatorInterface;

$validator = app(WhatsAppValidatorInterface::class);
$results = $validator->validateBulk([
    '+1234567890',
    '+44123456789'
]);

// Vanilla PHP
// Create the validator as shown in the Basic Usage example
$results = $validator->validateBulk([
    '+1234567890',
    '+44123456789'
]);
```

### Error Handling

The package throws exceptions in the following cases:

- `InvalidArgumentException`: When the phone number format is invalid or configuration is missing
- `Exception`: When there are API errors, network issues, or invalid responses

Example error handling:

```php
try {
    $isValid = $validator->validate('+1234567890');
} catch (\InvalidArgumentException $e) {
    // Handle invalid phone number format or configuration issues
    echo "Invalid input: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle API errors, network issues, or invalid responses
    echo "Validation failed: " . $e->getMessage();
}
```

## Architecture

This package follows SOLID principles with a clean separation of responsibilities:

1. **WhatsAppValidatorInterface**: The main contract for WhatsApp number validation
2. **RapidApiWhatsAppValidator**: Implementation using RapidAPI service
3. **PhoneNumberFormatter**: Handles phone number formatting and normalization
4. **HttpClient**: A transport layer that adapts to the environment (Laravel or vanilla PHP)
5. **Logger**: Handles error logging

## Testing

The package is designed to be easily testable in Laravel environments using HTTP client fakes.

```php
use Illuminate\Http\Client\Factory as LaravelHttpClientFactory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Simbiosis\WhatsAppNumberValidator\HttpClient;
use Simbiosis\WhatsAppNumberValidator\Logger;
use Simbiosis\WhatsAppNumberValidator\PhoneNumberFormatter;
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

class WhatsAppValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup the test validator
        $httpClientFactory = new LaravelHttpClientFactory();
        $config = [
            'endpoint' => 'https://api.example.com/validate',
            'bulk_endpoint' => 'https://api.example.com/validate-bulk',
            'key' => 'test-api-key',
            'host' => 'example-api-host.com',
        ];

        $this->validator = new RapidApiWhatsAppValidator(
            $config,
            new HttpClient($config, $httpClientFactory),
            new PhoneNumberFormatter(),
            new Logger('Test')
        );
    }

    public function testValidateWithFakeResponse()
    {
        // Fake the HTTP client's response
        Http::fake([
            'https://api.example.com/validate' => Http::response([
                'status' => 'valid',
                'phone_number' => '1234567890'
            ], 200)
        ]);

        // Test the validation
        $result = $this->validator->validate('+1234567890');

        // Assert the result
        $this->assertTrue($result);
    }
}
```

## Features

- Real-time WhatsApp number validation using RapidAPI
- Bulk number validation support
- SOLID architecture with clean separation of responsibilities
- Environment-aware HTTP client (Laravel or vanilla PHP)
- Configurable timeouts and retry attempts
- Dedicated logging
- Laravel integration with dependency injection
- Comprehensive error handling
- Testable with Laravel's HTTP client fakes

## Requirements

- PHP 8.1 or higher
- Laravel 10.2+ (optional, for Laravel integration)
- cURL extension (for vanilla PHP implementations)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.