<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('post');
        $required = $isPost ? 'required' : 'sometimes|required';

        return [
            'purchase_date' => $required . '|date',
            'supplier_name' => $required . '|string|max:191',
            'invoice_no' => $required . '|string|max:100',
            'category' => $required . '|string|max:100',
            'amount' => $required . '|numeric|min:0.01',
            'payment_method' => $required . '|string|max:100',
            'project_id' => $required . '|exists:projects,id',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
