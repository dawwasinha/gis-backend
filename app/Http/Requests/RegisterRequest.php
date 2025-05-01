<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'nisn' => 'required|integer|unique:users',
            'nomor_wa' => 'required|string|max:15',
            'alamat' => 'required|string|max:255',
            'provinsi_id' => 'required|string|max:255',
            'kabupaten_id' => 'required|string|max:255',
            'jenjang' => 'required|string|max:255',
            'kelas' => 'required|string|max:255',
            'asal_sekolah' => 'required|string|max:255',
            'link_twibbon' => 'nullable|url|max:255',
            'link_bukti_pembayaran' => 'nullable|url|max:255',
        ];
    }
}
