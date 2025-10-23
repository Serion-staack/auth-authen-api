<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
    public function rules()
    {
        return [
            'current_password' => 'required',
            'new_password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/[0-9]/', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[@$!%*#?&]/'],
        ];
    }
    public function messages()
    {
        return [
            'current_password.required' => 'Current Password is required.',
            'new_password.regex'=>'The password must contain at least one uppercase letter,one lowercase letter,one number,and one special character',
            'new_password.min'=>'The password must contain at least 12 characters long',
        ];
    }
}
