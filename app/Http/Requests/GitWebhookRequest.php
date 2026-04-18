<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate incoming GitHub / GitLab push webhook payloads.
 */
final class GitWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by webhook secrets; allow validation to proceed.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ref' => ['required', 'string', 'max:255'],
            'repository' => ['nullable', 'array'],
            'repository.full_name' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'array'],
            'project.path_with_namespace' => ['nullable', 'string', 'max:255'],
            'commits' => ['required', 'array'],
            'commits.*.id' => ['required', 'string', 'max:64'],
            'commits.*.message' => ['required', 'string'],
            'commits.*.timestamp' => ['nullable', 'date'],
            'commits.*.author' => ['nullable', 'array'],
            'commits.*.author.name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
