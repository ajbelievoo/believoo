<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domain_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "ResellerClub")
            $table->string('code')->unique(); // Provider code (e.g., "resellerclub")
            $table->string('class'); // Service class name
            $table->boolean('is_active')->default(false);
            $table->integer('priority')->default(100); // Lower = higher priority
            $table->json('supported_tlds')->nullable(); // Array of TLDs
            $table->json('default_nameservers')->nullable(); // Default NS
            $table->text('api_url')->nullable();
            $table->text('api_key')->nullable(); // Encrypted
            $table->text('api_secret')->nullable(); // Encrypted
            $table->text('username')->nullable(); // For basic auth
            $table->text('password')->nullable(); // Encrypted
            $table->string('test_mode')->default('0'); // '1' or '0'
            $table->json('metadata')->nullable(); // Provider-specific config
            $table->timestamp('last_checked_at')->nullable();
            $table->string('status')->default('pending'); // active, inactive, error
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
            $table->index('code');
        });

        // Insert default providers
        DB::table('domain_providers')->insert([
            [
                'name' => 'ResellerClub',
                'code' => 'resellerclub',
                'class' => 'App\Services\DomainRegistration\ResellerClubService',
                'is_active' => false,
                'priority' => 10,
                'supported_tlds' => json_encode(['in', 'co.in', 'net.in', 'org.in', 'gen.in', 'firm.in', 'ind.in']),
                'default_nameservers' => json_encode(['ns1.believoo.com', 'ns2.believoo.com']),
                'api_url' => 'https://httpapi.com/api/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Namecheap',
                'code' => 'namecheap',
                'class' => 'App\Services\DomainRegistration\NamecheapService',
                'is_active' => false,
                'priority' => 20,
                'supported_tlds' => json_encode(['com', 'net', 'org', 'io', 'co', 'biz', 'info', 'us', 'uk', 'eu', 'me']),
                'default_nameservers' => json_encode(['ns1.believoo.com', 'ns2.believoo.com']),
                'api_url' => 'https://api.namecheap.com/xml.response',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cloudflare',
                'code' => 'cloudflare',
                'class' => 'App\Services\DomainRegistration\CloudflareService',
                'is_active' => false,
                'priority' => 30,
                'supported_tlds' => json_encode(['com', 'net', 'org', 'io', 'co', 'dev', 'app', 'page', 'xyz', 'club']),
                'default_nameservers' => json_encode(['ns1.believoo.com', 'ns2.believoo.com']),
                'api_url' => 'https://api.cloudflare.com/client/v4/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_providers');
    }
};
