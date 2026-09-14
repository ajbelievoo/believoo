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
        Schema::create('admin_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin user
            
            // Alert Channels
            $table->boolean('telegram_enabled')->default(false);
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_bot_token')->nullable()->comment('Encrypted');
            
            $table->boolean('email_enabled')->default(true);
            $table->string('email_address')->nullable();
            
            // Alert Thresholds
            $table->integer('cpu_threshold')->default(85); // Alert when CPU > 85%
            $table->integer('ram_threshold')->default(90); // Alert when RAM > 90%
            $table->integer('disk_threshold')->default(85); // Alert when Disk > 85%
            $table->integer('vm_down_threshold')->default(5); // Alert after 5 min down
            
            // Alert Types
            $table->boolean('alert_vm_down')->default(true);
            $table->boolean('alert_high_resource')->default(true);
            $table->boolean('alert_license_expiring')->default(true);
            $table->boolean('alert_ticket_priority_high')->default(true);
            $table->boolean('alert_payment_received')->default(true);
            
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_alert_settings');
    }
};
