<?php

namespace Simbiosis\WhatsAppNumberValidator;

interface PhoneNumberFormatterInterface
{
    /**
     * Format a phone number to a standard format
     *
     * @param string $phoneNumber
     * @return string
     * @throws \InvalidArgumentException
     */
    public function formatPhoneNumber(string $phoneNumber): string;

    /**
     * Format multiple phone numbers
     *
     * @param array $phoneNumbers
     * @return array
     * @throws \InvalidArgumentException
     */
    public function formatPhoneNumbers(array $phoneNumbers): array;

    /**
     * Format a phone number for API submission
     * Removes all non-numeric characters
     *
     * @param string $number
     * @return string
     */
    public function formatNumberForApi(string $number): string;
}