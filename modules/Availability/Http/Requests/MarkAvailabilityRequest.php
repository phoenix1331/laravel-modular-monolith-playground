<?php

declare(strict_types=1);

namespace Modules\Availability\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Availability\Models\AvailabilityStatus;

class MarkAvailabilityRequest extends FormRequest
{
    /**
     * Authorisation happens in the controller once the fixture is resolved.
     */
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
            'status' => ['required', Rule::enum(AvailabilityStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
