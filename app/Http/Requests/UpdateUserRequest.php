<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user->id,
            'jenjang' => 'required|string|max:255',
            'password' => 'sometimes|nullable|string|min:8',
            'jenis_lomba' => 'required|string|max:255',
            'link_twibbon' => 'nullable|string',
            'link_bukti_pembayaran' => 'nullable|string',
        ];
    }
}
