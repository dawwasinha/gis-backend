<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamSubmitRequest extends FormRequest
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
            'userId' => 'required|exists:users,id',
            'durationInMinutes' => 'required|integer|min:0',
            'totalViolations' => 'required|integer|min:0',
            'isAutoSubmit' => 'required|boolean'
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'userId.required' => 'User ID harus diisi',
            'userId.exists' => 'User tidak ditemukan',
            'durationInMinutes.required' => 'Durasi pengerjaan harus diisi',
            'durationInMinutes.integer' => 'Durasi harus berupa angka',
            'durationInMinutes.min' => 'Durasi tidak boleh negatif',
            'totalViolations.required' => 'Total pelanggaran harus diisi',
            'totalViolations.integer' => 'Total pelanggaran harus berupa angka',
            'totalViolations.min' => 'Total pelanggaran tidak boleh negatif',
            'isAutoSubmit.required' => 'Status auto submit harus diisi',
            'isAutoSubmit.boolean' => 'Status auto submit harus berupa boolean'
        ];
    }
}
