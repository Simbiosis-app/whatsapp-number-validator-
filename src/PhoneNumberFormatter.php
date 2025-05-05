<?php

namespace Simbiosis\WhatsAppNumberValidator;

use InvalidArgumentException;

class PhoneNumberFormatter implements PhoneNumberFormatterInterface
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
     * Format a phone number for API submission
     * Removes all non-numeric characters
     *
     * @param string $number
     * @return string
     */
    public function formatNumberForApi(string $number): string
    {
        return preg_replace('/[^0-9]/', '', $number);
    }
}