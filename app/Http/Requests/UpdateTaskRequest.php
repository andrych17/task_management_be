<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTaskRequest extends FormRequest
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
        $taskId = $this->route('id');

        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'unique:tasks,title,' . $taskId . ',id,user_id,' . Auth::id()
            ],
            'description' => ['nullable', 'string'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'], // Tag names as strings
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:todo,in-progress,done'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'title.unique' => 'You already have a task with this title',
            'title.max' => 'Task title cannot exceed 255 characters',
            'project_id.exists' => 'Selected project does not exist',
            'tags.*.max' => 'Tag name cannot exceed 50 characters',
            'status.in' => 'Status must be one of: todo, in-progress, done',
        ];
    }
}
