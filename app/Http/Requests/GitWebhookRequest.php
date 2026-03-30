<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate incoming Git webhook payloads (GitHub/GitLab).
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
     *
     * We require an array of commits (both GitHub & GitLab push events include this).
     */
    public function rules(): array
    {
        return [
            'commits' => ['required', 'array'],
        ];
    }
}
