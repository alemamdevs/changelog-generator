<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $userId = (int) $this->user()?->id;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'github_repo' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\s\/]+\/[^\s\/]+$/',
                Rule::unique('projects', 'github_repo')->where(static fn ($query) => $query->where('user_id', $userId)),
            ],
            'default_branch' => ['required', 'string', 'max:128'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'github_repo.regex' => 'Repository must use the owner/name format.',
        ];
    }
}
