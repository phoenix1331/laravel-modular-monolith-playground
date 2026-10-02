<?php

declare(strict_types=1);

namespace App\Audit;

/**
 * Opt a model into the audit log.
 *
 * Implement this interface and add `#[ObservedBy(AuditObserver::class)]` to
 * the model. Nothing else: no audit code in controllers, and no registration
 * in Core. That is what lets a module make its own models auditable while
 * Core stays unaware the module exists.
 *
 *     #[ObservedBy(AuditObserver::class)]
 *     class Fixture extends Model implements Auditable
 *     {
 *         public function auditType(): string
 *         {
 *             return 'fixtures.fixture';
 *         }
 *
 *         public function auditActions(): array
 *         {
 *             return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
 *         }
 *     }
 */
interface Auditable
{
    /**
     * A stable name for this kind of record, e.g. `team` or `fixtures.fixture`.
     * Modules prefix theirs with the module key so names never collide.
     */
    public function auditType(): string;

    /**
     * Which model events to record. Return a subset to opt out, e.g. skip
     * updates for a model whose edits are noise.
     *
     * @return list<AuditAction>
     */
    public function auditActions(): array;
}
