<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bconnect_saved_devices', function (Blueprint $t) {
            $t->id();
            // owner_key: 'dev:{device_id}' for agent installs, 'user:{id}' for members
            $t->string('owner_key', 80)->index();
            $t->string('code', 12)->index();
            $t->string('label', 120)->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamps();
            $t->unique(['owner_key', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_saved_devices');
    }
};
