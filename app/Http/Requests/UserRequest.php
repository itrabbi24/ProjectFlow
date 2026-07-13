<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isPost = $this->isMethod('post');
        $userId = $this->route('user');

        return [
            'name' => ($isPost ? 'required' : 'sometimes|required') . '|string|max:191',
            'username' => ($isPost ? 'required' : 'sometimes|required') . '|string|max:50|unique:users,username,' . $userId,
            'email' => ($isPost ? 'required' : 'sometimes|required') . '|email|max:191|unique:users,email,' . $userId,
            'password' => ($isPost ? 'required' : 'nullable') . '|string|min:6',
            'role_id' => ($isPost ? 'required' : 'sometimes|required') . '|exists:roles,id',
            'status' => ($isPost ? 'required' : 'sometimes|required') . '|in:active,inactive',
        ];
    }
}
