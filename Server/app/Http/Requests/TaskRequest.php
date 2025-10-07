<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:pending,in_progress,complete',
            'priority' => 'required|string|in:low,medium,high',
            'project_id' => 'required|integer|exists:projects,id',
            'creator_id' => 'integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'info' => 'nullable|string',
            'progress' => 'nullable|integer|min:0|max:100',
        ];
    }
}
