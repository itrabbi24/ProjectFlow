<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('income_date')) {
            try {
                $this->merge([
                    'income_date' => \Carbon\Carbon::parse($this->input('income_date'))->format('Y-m-d')
                ]);
            } catch (\Throwable $e) {
            }
        }
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('post');
        $required = $isPost ? 'required' : 'sometimes|required';

        return [
            'income_date' => $required . '|date',
            'client_id' => $required . '|exists:clients,id',
            'project_id' => $required . '|exists:projects,id',
            'invoice_number' => $required . '|string|max:100',
            'category' => $required . '|string|max:100',
            'amount' => $required . '|numeric|min:0.01',
            'payment_method' => $required . '|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
