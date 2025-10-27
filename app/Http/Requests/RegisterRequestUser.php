<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequestUser extends FormRequest
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
    public function rules():array
    {
        return [
            'full_name'     => ['required','string','max:255','regex:/^[a-zA-Z0-9]+$/','not_in:password,name,email'],
            'email'         => 'required|email|unique:users,email',
            'notes'         => 'nullable|string|max:500',
            'address'       => 'required|string|max:255|regex:/^[a-zA-Z0-9\s,\.#-]+$/',
            'phone_number'  => ['required','regex:/^\+?[0-9]{8,15}$/'],
            'role_id'       => 'required|integer|exists:roles,id',
            'password'      => ['required', 'string', 'min:12', 'confirmed', 'regex:/[0-9]/', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[@$!%*#?&]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.regex'    => 'The name must only contain letters and spaces',
            'phone_number.regex' => 'The phone number must be valid and contain 8 to 15 digits',
            'password.regex'     => 'The password must contain at least one uppercase letter,one lowercase letter,one number,and one special character',
            'password.min'       => 'The password must contain at least 12 characters long',
            'password.confirmed' => 'The password confirmation does not match',
            'role_id.exists'     => 'The selected role is invalid. Please choose a valid role from the list.',
            'email.unique'       => 'This email is already registered in the system.',
            'address.regex'      => 'The address should only contain letters, numbers, and allowed special characters (e.g. commas, periods, and hashtags).'
        ];
    }
}
