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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Kolom untuk user_id
            $table->string('kode_bayar')->unique(); // Kolom untuk kode bayar
            $table->string('status')->nullable(); // Kolom untuk status
            $table->integer('total_pembayaran')->nullable(); // Kolom untuk total pembayaran
            $table->string('upload_bukti')->nullable(); // Kolom untuk upload bukti (opsional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
