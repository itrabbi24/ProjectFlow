<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('expense_date')) {
            try {
                $this->merge([
                    'expense_date' => \Carbon\Carbon::parse($this->input('expense_date'))->format('Y-m-d')
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
            'expense_date' => $required . '|date',
            'category' => $required . '|string|max:100',
            'amount' => $required . '|numeric|min:0.01',
            'project_id' => $required . '|exists:projects,id',
            'paid_by' => $required . '|exists:users,id',
            'payment_method' => $required . '|string|max:100',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'status' => 'nullable|in:approved,pending,rejected',
        ];
    }
}
