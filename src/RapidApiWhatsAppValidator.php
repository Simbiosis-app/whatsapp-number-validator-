<?php

namespace Simbiosis\WhatsAppNumberValidator;

class RapidApiWhatsAppValidator extends BaseValidator
{
    /**
     * The RapidAPI endpoint
     *
     * @var string
     */
    protected string $apiEndpoint;

    /**
     * The RapidAPI bulk endpoint
     *
     * @var string
     */
    protected string $bulkApiEndpoint;

    /**
     * The RapidAPI key
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * The RapidAPI host
     *
     * @var string
     */
    protected string $apiHost;

    /**
     * The number of retry attempts
     *
     * @var int
     */
    protected int $retryAttempts;

    /**
     * The timeout in seconds
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Create a new validator instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->apiEndpoint = $config['endpoint'] ?? '';
        $this->bulkApiEndpoint = $config['bulk_endpoint'] ?? '';
        $this->apiKey = $config['key'] ?? '';
        $this->apiHost = $config['host'] ?? '';

        if (empty($this->apiEndpoint) || empty($this->apiKey) || empty($this->apiHost)) {
            throw new \InvalidArgumentException('Missing required RapidAPI configuration');
        }

        $this->retryAttempts = $config['retry_attempts'] ?? 3;
        $this->timeout = $config['timeout'] ?? 30;
    }

    /**
     * Validate a phone number
     *
     * @param string $phoneNumber
     * @return bool
     * @throws \Exception
     */
    public function validate(string $phoneNumber): bool
    {
        try {
            $formattedNumber = $this->formatNumber($phoneNumber);
            return $this->performValidation($formattedNumber);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Validation failed', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate multiple phone numbers
     *
     * @param array $phoneNumbers
     * @return array
     * @throws \Exception
     */
    public function validateBulk(array $phoneNumbers): array
    {
        try {
            $formattedNumbers = $this->formatNumbers($phoneNumbers);
            return $this->performBulkValidation($formattedNumbers);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Bulk validation failed', [
                'phone_numbers' => $phoneNumbers,
                'error' => $e->getMessage()
            ]);
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
        return !empty($this->bulkApiEndpoint);
    }

    /**
     * Perform the actual validation of the phone number
     *
     * @param string $formattedNumber
     * @return bool
     * @throws \Exception
     */
    protected function performValidation(string $formattedNumber): bool
    {
        $response = $this->makeRequest($this->apiEndpoint, [
            'phone_number' => $this->formatNumberForApi($formattedNumber)
        ], [
            'x-rapidapi-host: ' . $this->apiHost,
            'x-rapidapi-key: ' . $this->apiKey,
        ]);

        return $response['status'] === 'valid';
    }

    /**
     * Perform bulk validation of phone numbers
     *
     * @param array $formattedNumbers
     * @return array
     * @throws \Exception
     */
    protected function performBulkValidation(array $formattedNumbers): array
    {
        $response = $this->makeRequest($this->bulkApiEndpoint, [
            'phone_numbers' => array_map(
                fn($number) => $this->formatNumberForApi($number),
                array_values($formattedNumbers)
            )
        ], [
            'x-rapidapi-host: ' . $this->apiHost,
            'x-rapidapi-key: ' . $this->apiKey,
        ]);

        $validationMap = [];
        foreach ($response as $result) {
            $phoneNumber = $result['phone_number'];
            $validationMap[$phoneNumber] = $result['status'] === 'valid';
        }

        $finalResults = [];
        foreach ($formattedNumbers as $originalNumber => $formattedNumber) {
            $finalResults[$originalNumber] = $validationMap[$this->formatNumberForApi($formattedNumber)] ?? false;
        }

        return $finalResults;
    }

    /**
     * Format the phone number for the API
     *
     * @param string $number
     * @return string
     */
    protected function formatNumberForApi(string $number): string
    {
        return preg_replace('/[^0-9]/', '', $number);
    }

}
