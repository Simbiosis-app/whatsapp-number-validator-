<?php

namespace Simbiosis\WhatsAppNumberValidator\Tests;

use Illuminate\Support\Facades\Http;
use Simbiosis\WhatsAppNumberValidator\RapidApiWhatsAppValidator;

class RapidApiWhatsAppValidatorTest extends TestCase
{
    private RapidApiWhatsAppValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->validator = $this->app->make(RapidApiWhatsAppValidator::class);
    }

    public function testValidateValidNumber(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'valid',
                'phone_number' => '1234567890'
            ], 200)
        ]);

        $result = $this->validator->validate('+1234567890');
        $this->assertTrue($result);
    }

    public function testValidateInvalidNumber(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'invalid',
                'phone_number' => '1234567890'
            ], 200)
        ]);

        $result = $this->validator->validate('+1234567890');
        $this->assertFalse($result);
    }

    public function testValidateBulkNumbers(): void
    {
        Http::fake([
            '*' => Http::response([
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

        $numbers = [
            '+1234567890',
            '+9876543210'
        ];

        $results = $this->validator->validateBulk($numbers);
        $this->assertTrue($results['+1234567890']);
        $this->assertFalse($results['+9876543210']);
    }

    public function testSupportsBulkValidation(): void
    {
        $this->assertTrue($this->validator->supportsBulkValidation());
    }

    public function testHandlesApiError(): void
    {
        Http::fake([
            '*' => Http::response(null, 500)
        ]);

        $this->expectException(\Exception::class);
        $this->validator->validate('+1234567890');
    }

    public function testHandlesNetworkError(): void
    {
        Http::fake([
            '*' => function() {
                throw new \Exception('Network error');
            }
        ]);

        $this->expectException(\Exception::class);
        $this->validator->validate('+1234567890');
    }
}