<?php

declare(strict_types=1);

namespace Modules\Fixtures\Http\Requests;

use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FixtureRequest extends FormRequest
{
    /**
     * Authorisation happens in the controller via FixturePolicy, once the
     * team is known.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `exists` rules query the database directly and skip the tenancy scope,
     * so they filter by organisation themselves. The team is fixed once a
     * fixture exists, so it is only accepted when scheduling.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organisationId = app(CurrentOrganisation::class)->id();

        return [
            'team_id' => [
                $this->isMethod('post') ? 'required' : 'prohibited',
                'integer',
                Rule::exists('teams', 'id')->where('organisation_id', $organisationId),
            ],
            'venue_id' => [
                'nullable',
                'integer',
                Rule::exists('fixtures_venues', 'id')->where('organisation_id', $organisationId),
            ],
            'opponent' => ['required', 'string', 'max:255'],
            'kick_off_at' => ['required', 'date'],
            'is_home' => ['required', 'boolean'],
            'competition' => ['nullable', 'string', 'max:50'],
        ];
    }
}
