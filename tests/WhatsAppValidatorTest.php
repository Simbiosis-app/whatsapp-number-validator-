<?php

namespace Tests\Unit;

use Illuminate\Http\Client\Factory as LaravelHttpClientFactory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Simbiosis\WhatsAppNumberValidator\HttpClient;
use Simbiosis\WhatsAppNumberValidator\Logger;
use Simbiosis\WhatsAppNumberValidator\PhoneNumberFormatter;
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

class WhatsAppValidatorTest extends TestCase
{
    protected LaravelHttpClientFactory $httpClientFactory;
    protected RapidApiWhatsAppValidator $validator;
    protected array $config = [
        'endpoint' => 'https://api.example.com/validate',
        'bulk_endpoint' => 'https://api.example.com/validate-bulk',
        'key' => 'test-api-key',
        'host' => 'example-api-host.com',
    ];
    protected LaravelHttpClientFactory $laravelHttpClientFactory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a new HTTP client factory for each test
        $this->httpClientFactory = new LaravelHttpClientFactory();
        $this->httpClientFactory->preventStrayRequests();
        // Create our custom HTTP client with the factory
        $httpClient = new HttpClient($this->config, $this->httpClientFactory);
        // Create the validator with our test dependencies
        $this->validator = new RapidApiWhatsAppValidator(
            $this->config,
            $httpClient,
            new PhoneNumberFormatter(),
            new Logger('Test')
        );
    }

    public function testValidateWithValidNumber()
    {
        // Fake the HTTP client's response
        $this->httpClientFactory->fake([
            'https://api.example.com/validate' => $this->httpClientFactory->response([
                'status' => 'valid',
                'phone_number' => '1234567890'
            ], 200)
        ]);

        // Test the validation
        $result = $this->validator->validate('+1234567890');

        // Assert the validation result
        $this->assertTrue($result);

        // Assert that the request was made with the expected parameters
        $this->httpClientFactory->assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/validate' &&
                   $request->hasHeader('x-rapidapi-key', 'test-api-key') &&
                   $request->hasHeader('x-rapidapi-host', 'example-api-host.com') &&
                   isset($request['phone_number']) &&
                   $request['phone_number'] === '1234567890';
        });
    }

    public function testValidateWithInvalidNumber()
    {
        // Fake the HTTP client's response for an invalid number
        $this->httpClientFactory->fake([
            'https://api.example.com/validate' => $this->httpClientFactory->response([
                'status' => 'invalid',
                'phone_number' => '1234567890'
            ], 200)
        ]);

        // Test the validation
        $result = $this->validator->validate('+1234567890');

        // Assert the validation result should be false
        $this->assertFalse($result);
    }

    public function testValidateWithInvalidResponse()
    {
        // Fake the HTTP client's response with missing status field
        $this->httpClientFactory->fake([
            'https://api.example.com/validate' => $this->httpClientFactory->response([
                'phone_number' => '1234567890'
                // Missing status field
            ], 200)
        ]);

        // Expect an exception about invalid response
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid API response: missing status field');

        // This should throw an exception
        $this->validator->validate('+1234567890');
    }

    public function testValidateBulkWithValidAndInvalidNumbers()
    {
        // Fake the HTTP client's response for bulk validation
        $this->httpClientFactory->fake([
            'https://api.example.com/validate-bulk' => $this->httpClientFactory->response([
                [
                    'status' => 'valid',
                    'phone_number' => '1234567890'
                ],
                [
                    'status' => 'invalid',
                    'phone_number' => '9876543210'
                ]
            ], 200)
        ]);

        // Test bulk validation
        $results = $this->validator->validateBulk([
            '+1234567890',
            '+9876543210'
        ]);

        // Assert the validation results
        $this->assertCount(2, $results);
        $this->assertTrue($results['+1234567890']);
        $this->assertFalse($results['+9876543210']);

        // Assert that the bulk request was made with the expected parameters
        $this->httpClientFactory->assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/validate-bulk' &&
                   $request->hasHeader('x-rapidapi-key', 'test-api-key') &&
                   $request->hasHeader('x-rapidapi-host', 'example-api-host.com') &&
                   isset($request['phone_numbers']) &&
                   is_array($request['phone_numbers']) &&
                   count($request['phone_numbers']) === 2 &&
                   in_array('1234567890', $request['phone_numbers']) &&
                   in_array('9876543210', $request['phone_numbers']);
        });
    }

    public function testValidateBulkWithEmptyResponse()
    {
        // Fake an empty API response for bulk validation
        $this->httpClientFactory->fake([
            'https://api.example.com/validate-bulk' => $this->httpClientFactory->response([], 200)
        ]);

        // Expect an exception about invalid response
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid API response: expected array of results');

        // This should throw an exception
        $this->validator->validateBulk(['+1234567890', '+9876543210']);
    }

    public function testValidateBulkWithIncompleteResults()
    {
        // Fake a response with one incomplete result
        $this->httpClientFactory->fake([
            'https://api.example.com/validate-bulk' => $this->httpClientFactory->response([
                [
                    'status' => 'valid',
                    'phone_number' => '1234567890'
                ],
                [
                    // Missing status field
                    'phone_number' => '9876543210'
                ]
            ], 200)
        ]);

        // The validator should skip the invalid result but continue processing
        $results = $this->validator->validateBulk([
            '+1234567890',
            '+9876543210'
        ]);

        // Should still have results for both numbers
        $this->assertCount(2, $results);
        $this->assertTrue($results['+1234567890']);
        $this->assertFalse($results['+9876543210']); // Should default to false
    }

    public function testHttpErrorHandling()
    {
        // Simulate HTTP client throwing an exception
        $this->httpClientFactory->fake(function () {
            throw new \Exception('Network error');
        });

        // Expect the exception to be propagated
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network error');

        // This should throw an exception
        $this->validator->validate('+1234567890');
    }
}