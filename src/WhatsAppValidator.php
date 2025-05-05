<?php

namespace Simbiosis\WhatsAppNumberValidator;

use InvalidArgumentException;

abstract class WhatsAppValidator implements WhatsAppValidatorInterface
{
    /**
     * Format the phone number to a standard format
     *
     * @param string $phoneNumber
     * @return string
     * @throws InvalidArgumentException
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters except plus sign
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If the number doesn't start with +, add it
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+' . $phoneNumber;
        }

        // Validate the number format
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
            throw new InvalidArgumentException('Invalid phone number format');
        }

        return $phoneNumber;
    }

    /**
     * Format multiple phone numbers
     *
     * @param array $phoneNumbers
     * @return array
     * @throws InvalidArgumentException
     */
    public function formatPhoneNumbers(array $phoneNumbers): array
    {
        $formattedNumbers = [];
        $errors = [];

        foreach ($phoneNumbers as $index => $number) {
            try {
                $formattedNumbers[$number] = $this->formatPhoneNumber($number);
            } catch (InvalidArgumentException $e) {
                $errors[] = "Invalid phone number at index {$index}: {$e->getMessage()}";
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }

        return $formattedNumbers;
    }

    /**
     * Validate if a phone number is a valid WhatsApp number
     *
     * @param string $phoneNumber The phone number to validate
     * @return bool Whether the number is a valid WhatsApp number
     * @throws InvalidArgumentException
     */
    public function validate(string $phoneNumber): bool
    {
        try {
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);
            return $this->performValidation($formattedNumber);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Validate multiple phone numbers
     *
     * @param array $phoneNumbers Array of phone numbers to validate
     * @return array Associative array with phone numbers as keys and validation results as values
     * @throws InvalidArgumentException
     */
    public function validateBulk(array $phoneNumbers): array
    {
        if (!$this->supportsBulkValidation()) {
            $results = [];
            foreach ($phoneNumbers as $number) {
                $results[$number] = $this->validate($number);
            }
            return $results;
        }

        try {
            $formattedNumbers = $this->formatPhoneNumbers($phoneNumbers);
            return $this->performBulkValidation($formattedNumbers);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check if the validator supports bulk validation
     *
     * @return bool
     */
    public function supportsBulkValidation(): bool
    {
        return false;
    }

    /**
     * Perform the actual validation of the phone number
     *
     * @param string $formattedNumber
     * @return bool
     */
    abstract protected function performValidation(string $formattedNumber): bool;

    /**
     * Perform bulk validation of phone numbers
     *
     * @param array $formattedNumbers
     * @return array
     */
    abstract protected function performBulkValidation(array $formattedNumbers): array;
}
