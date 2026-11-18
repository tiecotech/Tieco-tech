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

    /** Accept Arabic/Persian digits in the phone number and trim stray spaces. */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        if (is_string($phone)) {
            $phone = strtr(trim($phone), [
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            ]);
            $this->merge(['phone' => $phone]);
        }
        if (is_string($this->input('email'))) {
            $this->merge(['email' => trim($this->input('email'))]);
        }
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

    /** Friendly, translated messages instead of Laravel's English defaults. */
    public function messages(): array
    {
        $e = fn (string $k) => __('site.contact.err.'.$k);

        return [
            'name.required' => $e('name'), 'name.max' => $e('too_long'),
            'phone.required_without' => $e('need_one'), 'email.required_without' => $e('need_one'),
            'phone.regex' => $e('phone'), 'phone.max' => $e('phone'),
            'email.email' => $e('email'), 'email.max' => $e('too_long'),
            'subject.required' => $e('subject'), 'subject.max' => $e('too_long'),
            'message.required' => $e('message'), 'message.min' => $e('message_min'), 'message.max' => $e('too_long'),
            'preferred_contact.in' => $e('preferred'),
        ];
    }
}
