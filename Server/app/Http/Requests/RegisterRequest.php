<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required | string | max:110',
            'email' => 'required | email ',
            'password' => ['required', Password::min(7)],
            'contact_info' => 'required|string|max:220'
        ];
    }
}
