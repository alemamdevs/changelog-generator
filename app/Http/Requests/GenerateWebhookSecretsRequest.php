<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateWebhookSecretsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:github,gitlab,all'],
        ];
    }

    public function provider(): string
    {
        return (string) $this->validated('provider');
    }
}
