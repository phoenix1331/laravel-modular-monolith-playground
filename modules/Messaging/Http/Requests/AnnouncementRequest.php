<?php

declare(strict_types=1);

namespace Modules\Messaging\Http\Requests;

use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
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
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')->where('organisation_id', app(CurrentOrganisation::class)->id())],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
