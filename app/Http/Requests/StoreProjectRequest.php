<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'client_id' => 'required|exists:clients,id',
            'manager_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:planning,running,completed,cancelled,archived',
            'priority' => 'required|in:low,medium,high',
            'budget' => 'required|numeric|min:0',
            'estimated_profit' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'color_label' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'progress' => 'required|integer|between:0,100',
            'notes' => 'nullable|string',
        ];
    }
}
