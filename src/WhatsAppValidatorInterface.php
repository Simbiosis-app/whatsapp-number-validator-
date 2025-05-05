<?php

namespace Simbiosis\WhatsAppNumberValidator;

interface WhatsAppValidatorInterface
{
    /**
     * Validate if a phone number is a valid WhatsApp number
     *
     * @param string $phoneNumber The phone number to validate
     * @return bool Whether the number is a valid WhatsApp number
     * @throws \InvalidArgumentException For invalid phone number format
     * @throws \Exception For API or connection errors
     */
    public function validate(string $phoneNumber): bool;

    /**
     * Validate multiple phone numbers
     *
     * @param array $phoneNumbers Array of phone numbers to validate
     * @return array Associative array with phone numbers as keys and validation results as values
     * @throws \InvalidArgumentException For invalid phone number format
     * @throws \Exception For API or connection errors
     */
    public function validateBulk(array $phoneNumbers): array;

    /**
     * Check if the validator supports bulk validation
     *
     * @return bool
     */
    public function supportsBulkValidation(): bool;
}
