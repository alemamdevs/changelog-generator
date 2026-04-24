<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProjectRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'repository_full_name' => ['required', 'string', 'max:255', 'regex:/^[^\s\/]+\/[^\s\/]+$/', 'unique:projects,repository_full_name'],
            'default_branch' => ['required', 'string', 'max:128'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'repository_full_name.regex' => 'Repository must use the owner/name format.',
        ];
    }
}
