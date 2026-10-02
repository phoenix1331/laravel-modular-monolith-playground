<?php

declare(strict_types=1);

namespace App\Audit;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns Eloquent created/updated/deleted events into audit entries for any
 * model that implements Auditable and is marked
 * `#[ObservedBy(AuditObserver::class)]`.
 *
 * - created: a snapshot of the new record's attributes
 * - updated: a from/to pair for each changed field
 * - deleted: a snapshot of the record as it was
 *
 * Hidden attributes (passwords, tokens) never reach the log, and an update
 * that only touched timestamps or hidden fields is not recorded at all.
 *
 * Model events do not fire for query-builder bulk updates and deletes
 * (`Team::query()->update([...])`), so those are not audited. Change records
 * one model at a time when the change matters.
 */
class AuditObserver
{
    private const array IGNORED = ['created_at', 'updated_at'];

    public function __construct(private readonly AuditLogger $logger) {}

    public function created(Model&Auditable $model): void
    {
        if (! $this->records($model, AuditAction::Create)) {
            return;
        }

        $this->log($model, AuditAction::Create, ['attributes' => $this->snapshot($model)]);
    }

    public function updated(Model&Auditable $model): void
    {
        if (! $this->records($model, AuditAction::Update)) {
            return;
        }

        $changes = $this->visible($model, $model->getChanges());

        if ($changes === []) {
            return;
        }

        $changeSet = [];

        foreach ($changes as $field => $to) {
            $changeSet[$field] = [
                'from' => $this->format($model->getOriginal($field)),
                'to' => $this->format($to),
            ];
        }

        $this->log($model, AuditAction::Update, ['changes' => $changeSet]);
    }

    public function deleted(Model&Auditable $model): void
    {
        if (! $this->records($model, AuditAction::Delete)) {
            return;
        }

        $this->log($model, AuditAction::Delete, ['attributes' => $this->snapshot($model)]);
    }

    private function records(Auditable $model, AuditAction $action): bool
    {
        return in_array($action, $model->auditActions(), true);
    }

    /**
     * @param  array<string, mixed>  $eventData
     */
    private function log(Model&Auditable $model, AuditAction $action, array $eventData): void
    {
        // Prefer the record's own club so a super admin editing club data lands in that club's trail
        $organisationId = $model->getAttribute('organisation_id');

        $this->logger->log(
            $action,
            $model->auditType(),
            $model->getKey(),
            $eventData,
            is_int($organisationId) ? $organisationId : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Model $model): array
    {
        return array_map($this->format(...), $this->visible($model, $model->getAttributes()));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function visible(Model $model, array $attributes): array
    {
        return array_diff_key($attributes, array_flip([...self::IGNORED, ...$model->getHidden()]));
    }

    private function format(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
