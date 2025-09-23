<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAnnouncement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserAnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil beberapa user untuk contoh
        $users = User::where('role', 'participant')->take(10)->get();

        $kategoriLomba = ['Science Competition', 'Karya Tulis Ilmiah', 'Poster Competition'];

        foreach ($users as $index => $user) {
            UserAnnouncement::create([
                'user_id' => $user->id,
                'status_lolos' => $index % 3 === 0 ? 'lolos' : 'tidak_lolos',
                'kategori_lomba' => $kategoriLomba[$index % 3],
                'skor_akhir' => rand(60, 100),
                'ranking' => $index % 3 === 0 ? $index + 1 : null,
                'keterangan' => $index % 3 === 0 
                    ? 'Selamat! Anda berhasil lolos ke tahap selanjutnya.' 
                    : 'Mohon maaf, Anda belum berhasil lolos pada tahap ini. Tetap semangat!',
                'tanggal_pengumuman' => now(),
                'diumumkan_oleh' => 'Admin System',
            ]);
        }
    }
}
