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
        Schema::create('user_hostings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('hosting_type'); // web, vps, dedicated
            $table->string('plan_name'); // Basic, Pro, Enterprise
            $table->string('status')->default('active'); // active, suspended, cancelled, expired
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle'); // monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('expiry_date');
            // Server details
            $table->string('server_ip')->nullable();
            $table->string('control_panel_url')->nullable();
            $table->string('control_panel_username')->nullable();
            $table->string('control_panel_password')->nullable();
            // Domain & SSL
            $table->string('primary_domain')->nullable();
            $table->boolean('ssl_enabled')->default(true);
            $table->date('ssl_expiry')->nullable();
            // Resource limits
            $table->string('disk_space')->nullable(); // e.g., "10 GB"
            $table->string('bandwidth')->nullable(); // e.g., "100 GB"
            $table->integer('email_accounts')->nullable();
            $table->integer('ftp_accounts')->nullable();
            $table->integer('databases')->nullable();
            // Admin notes
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_hostings');
    }
};
