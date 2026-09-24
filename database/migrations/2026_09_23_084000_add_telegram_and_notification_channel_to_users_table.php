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
        Schema::table('users', function (Blueprint $table) {
            $table->string('notification_channel', 20)->default('whatsapp')->after('whatsapp_number');
            $table->string('telegram_chat_id', 50)->nullable()->after('notification_channel');
            $table->string('telegram_connect_token', 64)->nullable()->unique()->after('telegram_chat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_channel', 'telegram_chat_id', 'telegram_connect_token']);
        });
    }
};
