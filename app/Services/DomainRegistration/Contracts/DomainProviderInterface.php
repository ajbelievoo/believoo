<?php

namespace App\Services\DomainRegistration\Contracts;

interface DomainProviderInterface
{
    /**
     * Get provider name
     */
    public function getName(): string;

    /**
     * Check if provider is active/enabled
     */
    public function isActive(): bool;

    /**
     * Get provider priority (lower = higher priority)
     */
    public function getPriority(): int;

    /**
     * Check domain availability
     *
     * @param string $domain Domain name (e.g., example.com)
     * @return array ['available' => bool, 'price' => float, 'currency' => string, 'message' => string|null]
     */
    public function checkAvailability(string $domain): array;

    /**
     * Register a new domain
     *
     * @param string $domain Domain name
     * @param array $params Registration parameters (years, contacts, nameservers, etc.)
     * @return array ['success' => bool, 'order_id' => string|null, 'message' => string]
     */
    public function registerDomain(string $domain, array $params): array;

    /**
     * Renew an existing domain
     *
     * @param string $domain Domain name
     * @param int $years Number of years to renew
     * @return array ['success' => bool, 'order_id' => string|null, 'message' => string]
     */
    public function renewDomain(string $domain, int $years): array;

    /**
     * Get domain details/info
     *
     * @param string $domain Domain name
     * @return array|null Domain details or null if not found
     */
    public function getDomainInfo(string $domain): ?array;

    /**
     * Update domain nameservers
     *
     * @param string $domain Domain name
     * @param array $nameservers Array of nameserver names
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateNameservers(string $domain, array $nameservers): array;

    /**
     * Update DNS records
     *
     * @param string $domain Domain name
     * @param array $records DNS records array
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateDnsRecords(string $domain, array $records): array;

    /**
     * Get DNS records for domain
     *
     * @param string $domain Domain name
     * @return array List of DNS records
     */
    public function getDnsRecords(string $domain): array;

    /**
     * Get domain price
     *
     * @param string $tld Top-level domain (e.g., com, in, net)
     * @param int $years Registration period
     * @return array ['price' => float, 'currency' => string, 'available' => bool]
     */
    public function getDomainPrice(string $tld, int $years = 1): array;

    /**
     * Check if provider supports a specific TLD
     *
     * @param string $tld Top-level domain
     * @return bool
     */
    public function supportsTld(string $tld): bool;

    /**
     * Get supported TLDs
     *
     * @return array List of supported TLDs
     */
    public function getSupportedTlds(): array;

    /**
     * Transfer domain from another provider
     *
     * @param string $domain Domain name
     * @param string $authCode EPP/Auth code
     * @param array $params Transfer parameters
     * @return array ['success' => bool, 'order_id' => string|null, 'message' => string]
     */
    public function transferDomain(string $domain, string $authCode, array $params): array;

    /**
     * Get account balance (if applicable)
     *
     * @return array ['balance' => float, 'currency' => string]
     */
    public function getBalance(): array;

    /**
     * Test API connection
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array;
}
