<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Http\Requests;

use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChargeTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The amount arrives in pounds from the form and is stored in pence.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', Rule::exists('teams', 'id')->where('organisation_id', app(CurrentOrganisation::class)->id())],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'due_on' => ['required', 'date'],
        ];
    }

    public function amountPence(): int
    {
        return (int) round((float) $this->input('amount') * 100);
    }
}
