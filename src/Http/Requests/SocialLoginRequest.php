<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'access_token' => ['required', 'string'],
        ];
    }
}
