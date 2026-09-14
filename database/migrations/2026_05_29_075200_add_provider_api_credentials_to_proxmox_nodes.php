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
        Schema::table('proxmox_nodes', function (Blueprint $table) {
            // Provider API credentials for JIT IP acquisition
            $table->text('provider_api_key_encrypted')->nullable()->after('provider_metadata');
            $table->text('provider_api_secret_encrypted')->nullable()->after('provider_api_key_encrypted');
            $table->string('provider_api_endpoint')->nullable()->after('provider_api_secret_encrypted');
            $table->string('provider_account_id')->nullable()->after('provider_api_endpoint');
            
            // IP acquisition settings
            $table->boolean('jit_ip_enabled')->default(false)->after('provider_account_id');
            $table->string('jit_ip_type')->nullable()->after('jit_ip_enabled'); // e.g. "failover", "addon", "floating"
            $table->decimal('jit_ip_cost', 10, 2)->nullable()->after('jit_ip_type'); // Track per-IP cost
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxmox_nodes', function (Blueprint $table) {
            $table->dropColumn([
                'provider_api_key_encrypted',
                'provider_api_secret_encrypted',
                'provider_api_endpoint',
                'provider_account_id',
                'jit_ip_enabled',
                'jit_ip_type',
                'jit_ip_cost',
            ]);
        });
    }
};
