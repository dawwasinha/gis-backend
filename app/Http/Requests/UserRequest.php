<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user?->id,
            'password' => $this->isMethod('post') ? 'required|string|min:8' : 'sometimes|string|min:8',
            'nisn' => 'required|integer',
            'nomor_wa' => 'required|string|max:15',
            'alamat' => 'required|string|max:255',
            'provinsi_id' => 'required|string|max:255',
            'kabupaten_id' => 'required|string|max:255',
            'jenjang' => 'required|string|max:255',
            'kelas' => 'required|string|max:255',
            'asal_sekolah' => 'required|string|max:255',
            'link_twibbon' => 'nullable|url|max:255',
            // 'link_bukti_pembayaran' => 'nullable|url|max:255',
        ];
    }
}
