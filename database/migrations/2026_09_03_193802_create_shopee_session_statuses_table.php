<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_session_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('session_state')->default('idle'); // idle, ready, need_login, disconnected, error
            $table->text('status_message')->nullable();
            $table->boolean('chrome_connected')->default(false);
            $table->boolean('affiliate_logged_in')->default(false);
            $table->boolean('auto_check_enabled')->default(false);
            $table->integer('interval_days')->default(1);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_session_statuses');
    }
};