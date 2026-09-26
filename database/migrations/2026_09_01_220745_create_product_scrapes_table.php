<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_scrapes', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->index();
            $table->string('item_id'); // Menggunakan string untuk menghindari batas nilai integer
            $table->string('shop_id');
            $table->string('judul');
            $table->text('image_url')->nullable();
            $table->json('spesifikasi')->nullable();
            $table->decimal('harga', 15, 2)->default(0);
            $table->bigInteger('terjual')->default(0);
            $table->float('rating_produk')->default(0);
            $table->integer('total_ulasan_produk')->default(0);
            $table->string('toko')->nullable();
            $table->float('rating_toko')->default(0);
            $table->integer('total_ulasan_toko')->default(0);
            $table->text('deskripsi')->nullable();
            $table->text('url_asli');
            $table->text('url_afiliasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_scrapes');
    }
};