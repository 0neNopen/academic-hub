<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'notification_channel' => ['nullable', 'string', 'in:whatsapp,telegram,both'],
            'telegram_chat_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class, 'telegram_chat_id')->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telegram_chat_id.unique' => 'Telegram Chat ID ini sudah terhubung ke akun lain. Mohon periksa kembali Chat ID Anda.',
        ];
    }
}
