<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required_without:phone', 'nullable', 'email:rfc', 'max:190'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{6,30}$/'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'preferred_contact' => ['nullable', Rule::in(['phone', 'whatsapp', 'email'])],
            'website' => ['nullable', 'string', 'max:200'], // honeypot, handled in the controller
        ];
    }
}
