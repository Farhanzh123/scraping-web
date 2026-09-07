<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('username')->default('default_account');
            $table->longText('cookies_json')->nullable(); // Menampung array JSON Cookie
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_accounts');
    }
};