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
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

$validator = app(RapidApiWhatsAppValidator::class);
$isValid = $validator->validate('+1234567890');

// Vanilla PHP
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

$validator = new RapidApiWhatsAppValidator([
    'endpoint' => 'your-endpoint',
    'bulk_endpoint' => 'your-bulk-endpoint',
    'key' => 'your-api-key',
    'host' => 'your-api-host'
]);
$isValid = $validator->validate('+1234567890');
```

### Bulk Validation

```php
// Laravel
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

$validator = app(RapidApiWhatsAppValidator::class);
$results = $validator->validateBulk([
    '+1234567890',
    '+44123456789'
]);

// Vanilla PHP
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

$validator = new RapidApiWhatsAppValidator([
    'endpoint' => 'your-endpoint',
    'bulk_endpoint' => 'your-bulk-endpoint',
    'key' => 'your-api-key',
    'host' => 'your-api-host'
]);
$results = $validator->validateBulk([
    '+1234567890',
    '+44123456789'
]);
```

### Error Handling

The package throws exceptions in the following cases:

- `InvalidArgumentException`: When the phone number format is invalid
- `Exception`: When there are API errors or network issues

Example error handling:

```php
try {
    $isValid = $validator->validate('+1234567890');
} catch (\InvalidArgumentException $e) {
    // Handle invalid phone number format
    echo "Invalid phone number: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle API or network errors
    echo "Validation failed: " . $e->getMessage();
}
```

## Features

- Real-time WhatsApp number validation using RapidAPI
- Bulk number validation support
- Configurable timeouts and retry attempts
- Error logging
- Laravel integration
- Vanilla PHP support
- Phone number format validation
- Comprehensive error handling

## Requirements

- PHP 8.1 or higher
- Laravel 10.2+ (optional, for Laravel integration)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.