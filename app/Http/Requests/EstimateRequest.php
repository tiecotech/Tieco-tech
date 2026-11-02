<?php

namespace App\Http\Requests;

use App\Services\Pricing\PricingInput;
use Illuminate\Foundation\Http\FormRequest;

class EstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id' => ['required', 'integer'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:500'],
            'options' => ['nullable', 'array', 'max:60'],
            'options.*' => ['integer', 'min:1', 'max:99'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'attachment_count' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function toInput(): PricingInput
    {
        return new PricingInput(
            projectTypeId: $this->integer('project_type_id'),
            pages: $this->filled('pages') ? $this->integer('pages') : null,
            options: (array) $this->input('options', []),
            phone: $this->input('phone'),
            whatsappOptIn: $this->boolean('whatsapp_opt_in'),
            attachmentCount: $this->integer('attachment_count'),
        );
    }
}
