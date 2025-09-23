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
        Schema::create('user_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status_lolos', ['lolos', 'tidak_lolos']); // Status lolos atau tidak lolos
            $table->string('kategori_lomba')->nullable(); // Kategori lomba yang diikuti
            $table->integer('skor_akhir')->nullable(); // Skor akhir jika ada
            $table->integer('ranking')->nullable(); // Ranking jika ada
            $table->text('keterangan')->nullable(); // Keterangan tambahan
            $table->timestamp('tanggal_pengumuman'); // Tanggal pengumuman
            $table->string('diumumkan_oleh')->nullable(); // Admin yang mengumumkan
            $table->timestamps();
            
            // Index untuk performance
            $table->index('user_id');
            $table->index('status_lolos');
            $table->index('tanggal_pengumuman');
            $table->index(['status_lolos', 'kategori_lomba']);
            
            // Unique constraint agar satu user hanya punya satu pengumuman
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_announcements');
    }
};
