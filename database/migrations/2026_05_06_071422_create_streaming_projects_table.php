<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('streaming_subscription_id')->constrained('streaming_subscriptions');
            $table->string('name', 100);
            $table->string('app_id', 32)->unique();
            $table->text('app_certificate'); // encrypted
            $table->text('rest_api_key'); // encrypted
            $table->string('region', 20)->default('global');
            $table->string('rtmp_url', 255);
            $table->string('webrtc_url', 255);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('user_id', 'idx_sp_user_id');
            $table->index('app_id', 'idx_sp_app_id');
            $table->index('status', 'idx_sp_status');
            $table->index('deleted_at', 'idx_sp_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_projects');
    }
};
