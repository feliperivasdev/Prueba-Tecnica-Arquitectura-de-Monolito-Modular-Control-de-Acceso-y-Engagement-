<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCheckInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid'],
            'credential_id' => ['required', 'uuid'],
            'gym_id' => ['required', 'uuid'],
        ];
    }
}