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
        Schema::table('user_statuses', function (Blueprint $table) {
            $table->enum('status_lolos', ['belum_diumumkan', 'lolos', 'tidak_lolos'])
                  ->default('belum_diumumkan')
                  ->after('status');
            $table->timestamp('tanggal_pengumuman')->nullable()->after('status_lolos');
            $table->text('keterangan_lolos')->nullable()->after('tanggal_pengumuman');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_statuses', function (Blueprint $table) {
            $table->dropColumn(['status_lolos', 'tanggal_pengumuman', 'keterangan_lolos']);
        });
    }
};
