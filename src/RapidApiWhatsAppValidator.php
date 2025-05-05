<?php

namespace Simbiosis\WhatsAppNumberValidator;

class RapidApiWhatsAppValidator implements WhatsAppValidatorInterface
{
    /**
     * The HTTP client.
     *
     * @var HttpClientInterface
     */
    protected HttpClientInterface $httpClient;

    /**
     * The phone number formatter.
     *
     * @var PhoneNumberFormatterInterface
     */
    protected PhoneNumberFormatterInterface $formatter;

    /**
     * The logger.
     *
     * @var Logger
     */
    protected Logger $logger;

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
     * @param HttpClientInterface|null $httpClient
     * @param PhoneNumberFormatterInterface|null $formatter
     * @param Logger|null $logger
     */
    public function __construct(
        array $config = [],
        ?HttpClientInterface $httpClient = null,
        ?PhoneNumberFormatterInterface $formatter = null,
        ?Logger $logger = null
    ) {
        $this->httpClient = $httpClient ?? new HttpClient($config);
        $this->formatter = $formatter ?? new PhoneNumberFormatter();
        $this->logger = $logger ?? new Logger();

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
     * Validate if a phone number is a valid WhatsApp number
     *
     * @param string $phoneNumber The phone number to validate
     * @return bool Whether the number is a valid WhatsApp number
     * @throws \InvalidArgumentException For invalid phone number format
     * @throws \Exception For API or connection errors
     */
    public function validate(string $phoneNumber): bool
    {
        try {
            $formattedNumber = $this->formatter->formatPhoneNumber($phoneNumber);
            return $this->performValidation($formattedNumber);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Validation failed', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate multiple phone numbers
     *
     * @param array $phoneNumbers Array of phone numbers to validate
     * @return array Associative array with phone numbers as keys and validation results as values
     * @throws \InvalidArgumentException For invalid phone number format
     * @throws \Exception For API or connection errors
     */
    public function validateBulk(array $phoneNumbers): array
    {
        try {
            $formattedNumbers = $this->formatter->formatPhoneNumbers($phoneNumbers);

            if (!$this->supportsBulkValidation()) {
                return $this->performSingleValidations($formattedNumbers);
            }

            return $this->performBulkValidation($formattedNumbers);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Bulk validation failed', [
                'phone_numbers' => $phoneNumbers,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Perform single validations for each number when bulk validation is not supported
     *
     * @param array $formattedNumbers
     * @return array
     */
    protected function performSingleValidations(array $formattedNumbers): array
    {
        $results = [];
        foreach ($formattedNumbers as $originalNumber => $formattedNumber) {
            try {
                $results[$originalNumber] = $this->performValidation($formattedNumber);
            } catch (\Exception $e) {
                $this->logger->error('Individual validation failed', [
                    'phone_number' => $originalNumber,
                    'error' => $e->getMessage()
                ]);
                $results[$originalNumber] = false;
            }
        }
        return $results;
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
        $response = $this->httpClient->makeRequest($this->apiEndpoint, [
            'phone_number' => $this->formatter->formatNumberForApi($formattedNumber)
        ], [
            'x-rapidapi-host: ' . $this->apiHost,
            'x-rapidapi-key: ' . $this->apiKey,
        ]);

        if (!isset($response['status'])) {
            throw new \Exception('Invalid API response: missing status field');
        }

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
        $response = $this->httpClient->makeRequest($this->bulkApiEndpoint, [
            'phone_numbers' => array_map(
                fn($number) => $this->formatter->formatNumberForApi($number),
                array_values($formattedNumbers)
            )
        ], [
            'x-rapidapi-host: ' . $this->apiHost,
            'x-rapidapi-key: ' . $this->apiKey,
        ]);

        if (!is_array($response) || empty($response)) {
            throw new \Exception('Invalid API response: expected array of results');
        }

        $validationMap = [];
        foreach ($response as $result) {
            if (!isset($result['phone_number']) || !isset($result['status'])) {
                $this->logger->error('Invalid result format in bulk validation response', [
                    'result' => $result
                ]);
                continue;
            }

            $phoneNumber = $result['phone_number'];
            $validationMap[$phoneNumber] = $result['status'] === 'valid';
        }

        $finalResults = [];
        foreach ($formattedNumbers as $originalNumber => $formattedNumber) {
            $apiFormat = $this->formatter->formatNumberForApi($formattedNumber);
            $finalResults[$originalNumber] = $validationMap[$apiFormat] ?? false;
        }

        return $finalResults;
    }
}
