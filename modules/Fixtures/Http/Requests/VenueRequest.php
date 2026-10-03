<?php

declare(strict_types=1);

namespace Modules\Fixtures\Http\Requests;

use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VenueRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('fixtures_venues')->where('organisation_id', app(CurrentOrganisation::class)->id()),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:10'],
        ];
    }
}
