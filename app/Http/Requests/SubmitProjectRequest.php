<?php

namespace App\Http\Requests;

use App\Services\Pricing\PricingCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/** Customer-supplied data only. Prices are never accepted from the browser. */
class SubmitProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $up = config('tieco.uploads');

        return [
            'project_type_id' => ['required', 'integer', 'exists:project_types,id'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:500'],
            'options' => ['nullable', 'array', 'max:60'],
            'options.*' => ['integer', 'min:1', 'max:99'],

            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'email' => ['required_without:phone', 'nullable', 'email:rfc', 'max:190'],
            'country' => ['nullable', 'string', 'max:80'],
            'timezone' => ['nullable', 'string', 'max:60'],
            'preferred_contact' => ['nullable', Rule::in(['whatsapp', 'phone', 'email'])],
            'whatsapp_opt_in' => ['nullable', 'boolean'],

            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'goal' => ['nullable', 'string', 'max:2000'],
            'audience' => ['nullable', 'string', 'max:2000'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:today'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000000'],

            'attachments' => ['nullable', 'array', 'max:'.$up['max_files']],
            'attachments.*' => ['file', File::types($up['mimes'])->max($up['max_kb'])],

            'website' => ['nullable', 'max:0'], // honeypot: real people leave it empty
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->boolean('whatsapp_opt_in') && PricingCalculator::normalizePhone($this->input('phone')) === null) {
                $v->errors()->add('phone', 'Use the international format, for example +963...');
            }
        });
    }
}
