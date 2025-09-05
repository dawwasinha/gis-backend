<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return User::where('jenis_lomba', 'science-writing')
                ->whereNotNull('jenis_lomba')
                ->where('jenis_lomba', '!=', '')
                ->get();
    }


    public function headings(): array
    {
        // Define the headings for the Excel file
        return [
            'Name',
            'Email',
            'NISN',
            'Nomor WA',
            'Alamat',
            'Jenjang',
            'Kelas',
            'Asal Sekolah',
            'Karya',
        ];
    }

    public function map($user): array
    {
        $karyaLinks = $user->karyas ? $user->karyas->map(function($karya) {
            return $karya->link_karya ? "https://gis-backend.karyavisual.com/gis-backend-v5/storage/app/public/" . $karya->link_karya : 'No Link Available';
        })->implode('; ') : 'Belum ada submit jurnal';
 

        return [
            $user->name,
            $user->email,
            $user->nisn,
            $user->nomor_wa,
            $user->alamat,
            $user->jenjang,
            $user->kelas,
            $user->asal_sekolah,
            $karyaLinks,
        ];
    }
}