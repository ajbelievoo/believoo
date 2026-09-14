<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('settings')->insertOrIgnore([
            ['key' => 'vapid_public_key', 'value' => 'BFmLgVotkxQlMsisx4lJ4uS66Mr7WVitAhSNYlwa6nqaeTB9V-LxandTD6fFlEeXqX4mUsG5qsvLXwmJBrYXT_A'],
            ['key' => 'vapid_private_key', 'value' => '9m_0Ejwohh_SLca1TWYn0S-LggItE8SKtGNQGNaDmXo'],
        ]);
    }
    public function down(): void { DB::table('settings')->whereIn('key', ['vapid_public_key','vapid_private_key'])->delete(); }
};
