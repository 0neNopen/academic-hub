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
            'schedule_reminder_time' => ['nullable', 'string', 'regex:/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/'],
            'task_reminder_early' => ['nullable', 'string', 'in:48,24,12,0'],
            'task_reminder_late' => ['nullable', 'string', 'in:5,3,1,0'],
            'campus_name' => ['nullable', 'string', 'max:100'],
            'campus_portal_url' => ['nullable', 'url', 'max:500'],
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
