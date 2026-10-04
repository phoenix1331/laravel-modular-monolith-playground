<?php

declare(strict_types=1);

namespace Modules\Compliance\Http\Requests;

use App\Enums\Role;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Compliance\Models\Certificate;

class CertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')
                    ->where('organisation_id', app(CurrentOrganisation::class)->id())
                    ->where('role', Role::Worker->value),
            ],
            'type' => ['required', Rule::in(Certificate::TYPES)],
            'reference' => ['nullable', 'string', 'max:64'],
            'issued_on' => ['required', 'date', 'before_or_equal:today'],
            'expires_on' => ['required', 'date', 'after:issued_on'],
        ];
    }
}
