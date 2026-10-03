<?php

declare(strict_types=1);

namespace Modules\MatchReports\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FileReportRequest extends FormRequest
{
    /**
     * Authorisation happens in the controller once the fixture is resolved.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Whether each player belongs to the fixture's team is checked in the
     * controller, which knows the team; this request only checks shape.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'goals_for' => ['required', 'integer', 'between:0,99'],
            'goals_against' => ['required', 'integer', 'between:0,99'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'performances' => ['array'],
            'performances.*.player_id' => ['required', 'integer', 'distinct'],
            'performances.*.goals' => ['integer', 'between:0,20'],
            'performances.*.rating' => ['nullable', 'integer', 'between:1,10'],
        ];
    }

    /**
     * Players cannot score more than the team did. Fewer is fine: own goals.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var array<int, array{goals?: int|string|null}> $performances */
            $performances = (array) $this->input('performances', []);
            $scored = array_sum(array_map(fn (array $row) => (int) ($row['goals'] ?? 0), $performances));

            if ($scored > (int) $this->input('goals_for')) {
                $validator->errors()->add('performances', "Players are credited with {$scored} goals but the team scored {$this->input('goals_for')}.");
            }
        }];
    }
}
