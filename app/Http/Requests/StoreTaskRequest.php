<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTaskRequest extends FormRequest
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
            'title' => [
                'required',
                'string',
                'max:255',
                'unique:tasks,title,NULL,id,user_id,' . Auth::id()
            ],
            'description' => ['nullable', 'string'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'], // Tag names as strings
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', 'in:todo,in-progress,done'],
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
            'due_date.after_or_equal' => 'Due date must be today or a future date',
            'status.in' => 'Status must be one of: todo, in-progress, done',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }
}
