<?php

declare(strict_types=1);

namespace App\Http\Requests\Teams;

use App\Enums\Role;
use App\Models\Team;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    /**
     * Authorisation happens in the controller via TeamPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Uniqueness and worker lookups are limited to the current club: validation
     * rules query the database directly, so they bypass OrganisationScope and
     * must filter by organisation themselves.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organisationId = app(CurrentOrganisation::class)->id();
        $team = $this->route('team');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('teams')
                    ->where('organisation_id', $organisationId)
                    ->ignore($team instanceof Team ? $team->id : null),
            ],
            'age_group' => ['nullable', 'string', 'max:20'],
            'worker_ids' => ['array'],
            'worker_ids.*' => [
                'integer',
                Rule::exists('users', 'id')
                    ->where('organisation_id', $organisationId)
                    ->where('role', Role::Worker->value),
            ],
        ];
    }
}
