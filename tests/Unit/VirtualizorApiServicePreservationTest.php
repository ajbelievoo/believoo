<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VirtualizorApiService;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use ReflectionClass;

/**
 * Preservation Property Tests — Property 2
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4
 *
 * These tests capture the BASELINE behavior of the UNFIXED VirtualizorApiService
 * constructor when credentials ARE configured (non-empty). They MUST PASS on
 * unfixed code (tests 1–4) to establish the behavior that the fix must preserve.
 *
 * Test 5 (isConfigured()) WILL FAIL on unfixed code because the method doesn't
 * exist yet — this is expected and documented.
 *
 * Preservation Pseudocode:
 *   FOR ALL X WHERE NOT isBugCondition(X) DO
 *     service_original ← VirtualizorApiService_original(X)
 *     service_fixed    ← VirtualizorApiService_fixed(X)
 *     ASSERT service_original.apiKey  = service_fixed.apiKey
 *     ASSERT service_original.apiPass = service_fixed.apiPass
 *     ASSERT service_original.baseUrl = service_fixed.baseUrl
 *   END FOR
 *
 * Strategy: Setting::getValue() is a static method that queries the DB.
 * We control its return value by inserting rows into the settings table
 * via Setting::updateOrCreate(), then cleaning up in tearDown.
 * Config values are overridden via Config::set().
 */
class VirtualizorApiServicePreservationTest extends TestCase
{
    /** @var array<string> Keys inserted during a test, cleaned up in tearDown */
    private array $insertedKeys = [];

    protected function tearDown(): void
    {
        // Clean up any Setting rows we inserted
        if (!empty($this->insertedKeys)) {
            Setting::whereIn('key', $this->insertedKeys)->delete();
            $this->insertedKeys = [];
        }
        parent::tearDown();
    }

    /**
     * Helper: read a protected/private property via reflection.
     */
    private function getProtectedProperty(object $object, string $property): mixed
    {
        $ref = new ReflectionClass($object);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    /**
     * Helper: insert a Setting row and track it for cleanup.
     */
    private function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->insertedKeys[] = $key;
    }

    /**
     * Helper: ensure a Setting key is absent from the DB.
     */
    private function deleteSetting(string $key): void
    {
        Setting::where('key', $key)->delete();
    }

    // -------------------------------------------------------------------------
    // Test 1: DB returns a non-empty api_key — it is assigned directly
    // -------------------------------------------------------------------------

    /**
     * Preservation 1: DB value assigned when Setting::getValue returns non-empty string.
     *
     * Observes: when Setting::getValue('virtualizor_api_key') returns 'testkey',
     * $apiKey is assigned 'testkey'.
     *
     * isBugCondition(X) = false because dbValue = 'testkey' (non-empty).
     *
     * Validates: Requirements 3.1
     */
    public function test_api_key_from_db_is_assigned_when_db_returns_non_empty_value(): void
    {
        $this->setSetting('virtualizor_api_key',  'testkey');
        $this->setSetting('virtualizor_api_pass', 'testpass');
        $this->setSetting('virtualizor_base_url', 'https://virt.example.com');

        Config::set('server-management.virtualizor.api_key',  'cfgkey');
        Config::set('server-management.virtualizor.api_pass', 'cfgpass');
        Config::set('server-management.virtualizor.base_url', 'https://cfg.example.com');

        $service = new VirtualizorApiService();

        $apiKey = $this->getProtectedProperty($service, 'apiKey');
        $this->assertSame('testkey', $apiKey, 'DB value must be assigned to $apiKey when DB returns non-empty string');
    }

    // -------------------------------------------------------------------------
    // Test 2: DB returns null, config returns non-empty — Elvis fallthrough
    // -------------------------------------------------------------------------

    /**
     * Preservation 2: Config fallback used when DB returns null.
     *
     * Observes: when Setting::getValue('virtualizor_api_key') returns null but
     * config('server-management.virtualizor.api_key') returns 'cfgkey',
     * $apiKey is assigned 'cfgkey' via the Elvis operator fallthrough.
     *
     * isBugCondition(X) = false because cfgValue = 'cfgkey' (non-empty).
     *
     * Validates: Requirements 3.1
     */
    public function test_api_key_falls_through_to_config_when_db_returns_null(): void
    {
        // Ensure no DB rows exist for these keys
        $this->deleteSetting('virtualizor_api_key');
        $this->deleteSetting('virtualizor_api_pass');
        $this->deleteSetting('virtualizor_base_url');

        Config::set('server-management.virtualizor.api_key',  'cfgkey');
        Config::set('server-management.virtualizor.api_pass', 'cfgpass');
        Config::set('server-management.virtualizor.base_url', 'https://cfg.example.com');

        $service = new VirtualizorApiService();

        $apiKey = $this->getProtectedProperty($service, 'apiKey');
        $this->assertSame('cfgkey', $apiKey, '$apiKey must fall through to config value when DB returns null');
    }

    // -------------------------------------------------------------------------
    // Test 3: DB takes precedence over config when both are non-empty
    // -------------------------------------------------------------------------

    /**
     * Preservation 3: DB value takes precedence over config value.
     *
     * Observes: when Setting::getValue('virtualizor_api_key') returns 'dbkey'
     * and config returns 'cfgkey', $apiKey is assigned 'dbkey' (DB wins).
     *
     * isBugCondition(X) = false because dbValue = 'dbkey' (non-empty).
     *
     * Validates: Requirements 3.1
     */
    public function test_db_value_takes_precedence_over_config_value(): void
    {
        $this->setSetting('virtualizor_api_key',  'dbkey');
        $this->setSetting('virtualizor_api_pass', 'dbpass');
        $this->setSetting('virtualizor_base_url', 'https://db.example.com');

        Config::set('server-management.virtualizor.api_key',  'cfgkey');
        Config::set('server-management.virtualizor.api_pass', 'cfgpass');
        Config::set('server-management.virtualizor.base_url', 'https://cfg.example.com');

        $service = new VirtualizorApiService();

        $apiKey  = $this->getProtectedProperty($service, 'apiKey');
        $apiPass = $this->getProtectedProperty($service, 'apiPass');
        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');

        $this->assertSame('dbkey',  $apiKey,  'DB value must take precedence over config for $apiKey');
        $this->assertSame('dbpass', $apiPass, 'DB value must take precedence over config for $apiPass');
        // baseUrl has rtrim applied, but no trailing slash here so value is unchanged
        $this->assertSame('https://db.example.com', $baseUrl, 'DB value must take precedence over config for $baseUrl');
    }

    // -------------------------------------------------------------------------
    // Test 4: rtrim('/', ...) is applied to baseUrl for all non-empty values
    // -------------------------------------------------------------------------

    /**
     * Preservation 4a: Trailing slash is stripped from baseUrl (DB source).
     *
     * Observes: rtrim(..., '/') is applied to $baseUrl regardless of source.
     * A URL with a trailing slash must have it removed.
     *
     * Validates: Requirements 3.1
     */
    public function test_trailing_slash_stripped_from_base_url_from_db(): void
    {
        $this->setSetting('virtualizor_api_key',  'key');
        $this->setSetting('virtualizor_api_pass', 'pass');
        $this->setSetting('virtualizor_base_url', 'https://virt.example.com/');

        Config::set('server-management.virtualizor.api_key',  '');
        Config::set('server-management.virtualizor.api_pass', '');
        Config::set('server-management.virtualizor.base_url', '');

        $service = new VirtualizorApiService();

        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');
        $this->assertSame('https://virt.example.com', $baseUrl, 'Trailing slash must be stripped from baseUrl');
    }

    /**
     * Preservation 4b: Trailing slash is stripped from baseUrl (config source).
     *
     * Validates: Requirements 3.1
     */
    public function test_trailing_slash_stripped_from_base_url_from_config(): void
    {
        $this->setSetting('virtualizor_api_key',  'key');
        $this->setSetting('virtualizor_api_pass', 'pass');
        $this->deleteSetting('virtualizor_base_url');

        Config::set('server-management.virtualizor.api_key',  'key');
        Config::set('server-management.virtualizor.api_pass', 'pass');
        Config::set('server-management.virtualizor.base_url', 'https://cfg.example.com/');

        $service = new VirtualizorApiService();

        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');
        $this->assertSame('https://cfg.example.com', $baseUrl, 'Trailing slash must be stripped from config baseUrl');
    }

    /**
     * Preservation 4c: Multiple trailing slashes are all stripped.
     *
     * Validates: Requirements 3.1
     */
    public function test_multiple_trailing_slashes_stripped_from_base_url(): void
    {
        $this->setSetting('virtualizor_api_key',  'key');
        $this->setSetting('virtualizor_api_pass', 'pass');
        $this->setSetting('virtualizor_base_url', 'https://virt.example.com///');

        Config::set('server-management.virtualizor.api_key',  '');
        Config::set('server-management.virtualizor.api_pass', '');
        Config::set('server-management.virtualizor.base_url', '');

        $service = new VirtualizorApiService();

        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');
        $this->assertSame('https://virt.example.com', $baseUrl, 'All trailing slashes must be stripped from baseUrl');
    }

    /**
     * Preservation 4d: baseUrl without trailing slash is unchanged.
     *
     * Validates: Requirements 3.1
     */
    public function test_base_url_without_trailing_slash_is_unchanged(): void
    {
        $this->setSetting('virtualizor_api_key',  'key');
        $this->setSetting('virtualizor_api_pass', 'pass');
        $this->setSetting('virtualizor_base_url', 'https://virt.example.com');

        Config::set('server-management.virtualizor.api_key',  '');
        Config::set('server-management.virtualizor.api_pass', '');
        Config::set('server-management.virtualizor.base_url', '');

        $service = new VirtualizorApiService();

        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');
        $this->assertSame('https://virt.example.com', $baseUrl, 'baseUrl without trailing slash must remain unchanged');
    }

    // -------------------------------------------------------------------------
    // Test 5: isConfigured() returns true when both apiKey and apiPass are set
    //         NOTE: This test WILL FAIL on unfixed code — isConfigured() doesn't exist yet.
    //         That is expected and documented here. It will pass after task 3.2.
    // -------------------------------------------------------------------------

    /**
     * Preservation 5: isConfigured() returns true when both apiKey and apiPass are non-empty.
     *
     * NOTE: This test WILL FAIL on unfixed code because isConfigured() does not
     * exist yet (it is added in task 3.2). This is expected and intentional.
     * The test documents the required behavior so it can be verified after the fix.
     *
     * Validates: Requirements 3.1
     */
    public function test_is_configured_returns_true_when_both_api_key_and_api_pass_are_non_empty(): void
    {
        $this->setSetting('virtualizor_api_key',  'mykey');
        $this->setSetting('virtualizor_api_pass', 'mypass');
        $this->setSetting('virtualizor_base_url', 'https://virt.example.com');

        Config::set('server-management.virtualizor.api_key',  '');
        Config::set('server-management.virtualizor.api_pass', '');
        Config::set('server-management.virtualizor.base_url', '');

        $service = new VirtualizorApiService();

        // isConfigured() is added in task 3.2 — this assertion FAILS on unfixed code.
        // That is expected: the method doesn't exist yet.
        // After the fix, this must return true when both key and pass are non-empty.
        $this->assertTrue($service->isConfigured(), 'isConfigured() must return true when both apiKey and apiPass are non-empty');
    }
}
