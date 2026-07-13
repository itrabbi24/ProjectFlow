<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:191',
            'client_id' => 'sometimes|required|exists:clients,id',
            'manager_id' => 'sometimes|required|exists:users,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'sometimes|required|in:planning,running,completed,cancelled,archived',
            'priority' => 'sometimes|required|in:low,medium,high',
            'budget' => 'sometimes|required|numeric|min:0',
            'estimated_profit' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'color_label' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'progress' => 'sometimes|required|integer|between:0,100',
            'notes' => 'nullable|string',
        ];
    }
}
