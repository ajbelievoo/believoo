<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VirtualizorApiService;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use ReflectionClass;

/**
 * Bug Condition Exploration Test — Property 1
 *
 * Validates: Requirements 1.1, 1.2, 1.3
 *
 * This test encodes the EXPECTED (fixed) behavior:
 *   - Constructor must NOT throw TypeError when both DB setting and config return null
 *   - $apiKey, $apiPass, $baseUrl must each be assigned '' (empty string)
 *   - isConfigured() must return false when all are empty
 *
 * On UNFIXED code this test FAILS with:
 *   TypeError: Cannot assign null to property App\Services\VirtualizorApiService::$apiKey of type string
 *
 * That failure is the proof the bug exists. The test will PASS after the fix is applied.
 */
class VirtualizorApiServiceBugConditionTest extends TestCase
{
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
     * Helper: mock Setting::getValue() to return null for all virtualizor keys.
     */
    private function mockSettingGetValueReturnsNull(): void
    {
        $this->mock(Setting::class, function ($mock) {
            $mock->shouldReceive('getValue')
                ->andReturnUsing(function ($key, $default = null) {
                    // Return null for virtualizor keys (simulating absent DB rows)
                    if (str_contains($key, 'virtualizor')) {
                        return null;
                    }
                    return $default;
                });
        });
    }

    /**
     * Bug Condition 1: null api_key from DB + null api_key from config
     *
     * isBugCondition(X) = true for api_key:
     *   Setting::getValue('virtualizor_api_key') = null  (key absent from DB)
     *   config('server-management.virtualizor.api_key') = null  (env var unset, no default)
     *
     * Expected (fixed) behavior: constructor completes, $apiKey = ''
     * Unfixed behavior: TypeError thrown — CONFIRMS BUG EXISTS
     *
     * Validates: Requirements 1.1, 2.1
     */
    public function test_constructor_does_not_throw_when_api_key_is_null_in_db_and_config(): void
    {
        $this->mockSettingGetValueReturnsNull();

        Config::set('server-management.virtualizor.api_key', null);
        Config::set('server-management.virtualizor.api_pass', null);
        Config::set('server-management.virtualizor.base_url', null);

        // On unfixed code: TypeError thrown — CONFIRMS BUG EXISTS
        // On fixed code: constructor completes and $apiKey = ''
        $service = new VirtualizorApiService();

        $apiKey = $this->getProtectedProperty($service, 'apiKey');
        $this->assertSame('', $apiKey, 'apiKey must be empty string when DB and config both return null');
    }

    /**
     * Bug Condition 2: null api_pass from DB + null api_pass from config
     *
     * isBugCondition(X) = true for api_pass:
     *   Setting::getValue('virtualizor_api_pass') = null  (key absent from DB)
     *   config('server-management.virtualizor.api_pass') = null  (env var unset, no default)
     *
     * Expected (fixed) behavior: constructor completes, $apiPass = ''
     * Unfixed behavior: TypeError thrown — CONFIRMS BUG EXISTS
     *
     * Validates: Requirements 1.2, 2.2
     */
    public function test_constructor_does_not_throw_when_api_pass_is_null_in_db_and_config(): void
    {
        $this->mockSettingGetValueReturnsNull();

        Config::set('server-management.virtualizor.api_key', null);
        Config::set('server-management.virtualizor.api_pass', null);
        Config::set('server-management.virtualizor.base_url', null);

        // On unfixed code: TypeError thrown — CONFIRMS BUG EXISTS
        // On fixed code: constructor completes and $apiPass = ''
        $service = new VirtualizorApiService();

        $apiPass = $this->getProtectedProperty($service, 'apiPass');
        $this->assertSame('', $apiPass, 'apiPass must be empty string when DB and config both return null');
    }

    /**
     * Bug Condition 3: null base_url from DB + null base_url from config
     *
     * isBugCondition(X) = true for base_url:
     *   Setting::getValue('virtualizor_base_url') = null  (key absent from DB)
     *   config('server-management.virtualizor.base_url') = null  (env var unset, no default)
     *
     * Expected (fixed) behavior: constructor completes, $baseUrl = ''
     * Unfixed behavior: TypeError thrown — CONFIRMS BUG EXISTS
     *
     * Validates: Requirements 1.3, 2.3
     */
    public function test_constructor_does_not_throw_when_base_url_is_null_in_db_and_config(): void
    {
        $this->mockSettingGetValueReturnsNull();

        Config::set('server-management.virtualizor.api_key', null);
        Config::set('server-management.virtualizor.api_pass', null);
        Config::set('server-management.virtualizor.base_url', null);

        // On unfixed code: TypeError thrown — CONFIRMS BUG EXISTS
        // On fixed code: constructor completes and $baseUrl = ''
        $service = new VirtualizorApiService();

        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');
        $this->assertSame('', $baseUrl, 'baseUrl must be empty string when DB and config both return null');
    }

    /**
     * Bug Condition 4: ALL credentials null in DB + null in config
     *
     * isBugCondition(X) = true for all three keys simultaneously.
     * This is the most common real-world scenario: fresh install with no .env config.
     *
     * Expected (fixed) behavior:
     *   - Constructor completes without TypeError
     *   - $apiKey = '', $apiPass = '', $baseUrl = ''
     *   - isConfigured() returns false
     *
     * Unfixed behavior: TypeError thrown on first null assignment — CONFIRMS BUG EXISTS
     *
     * Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.2, 2.3
     */
    public function test_constructor_assigns_empty_strings_and_is_configured_returns_false_when_all_null(): void
    {
        $this->mockSettingGetValueReturnsNull();

        Config::set('server-management.virtualizor.api_key', null);
        Config::set('server-management.virtualizor.api_pass', null);
        Config::set('server-management.virtualizor.base_url', null);

        // On unfixed code: TypeError thrown — CONFIRMS BUG EXISTS
        // On fixed code: all three are '' and isConfigured() returns false
        $service = new VirtualizorApiService();

        $apiKey  = $this->getProtectedProperty($service, 'apiKey');
        $apiPass = $this->getProtectedProperty($service, 'apiPass');
        $baseUrl = $this->getProtectedProperty($service, 'baseUrl');

        $this->assertSame('', $apiKey,  'apiKey must be empty string when null');
        $this->assertSame('', $apiPass, 'apiPass must be empty string when null');
        $this->assertSame('', $baseUrl, 'baseUrl must be empty string when null');

        // isConfigured() is added as part of the fix (task 3.2)
        // This assertion will also fail on unfixed code since the method doesn't exist yet
        $this->assertFalse($service->isConfigured(), 'isConfigured() must return false when credentials are empty');
    }
}
