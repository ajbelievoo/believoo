<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->string('gateway')->nullable();
            $table->string('netmask')->default('255.255.255.0');
            $table->string('node')->nullable()->comment('Proxmox node this IP belongs to');
            $table->enum('status', ['available', 'assigned', 'reserved', 'blocked'])->default('available');
            $table->unsignedBigInteger('proxmox_vm_id')->nullable();
            $table->unsignedBigInteger('user_hosting_id')->nullable();
            $table->integer('vmid')->nullable()->comment('Proxmox VM ID');
            $table->string('rdns')->nullable()->comment('Reverse DNS');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('node');
            $table->index('vmid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
