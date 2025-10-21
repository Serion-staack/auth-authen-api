<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequestUser extends FormRequest
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
                 'email' => 'required|email|unique:users,email',
                 'password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[@$!%*#?&]/'],
               ];
    }

    /*public function messages(): array
    {
        return [
            'password.regex'=>'The password must contain at least one uppercase letter,one lowercase letter,one number,and one special character',
            'password.min'=>'The password must contain at least 8 characters long',
        ];
    }*/
}
