<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only model that audits creation but opts out of updates and deletes.
 */
#[ObservedBy(AuditObserver::class)]
class CreateOnlyNote extends Model implements Auditable
{
    protected $table = 'notes';

    protected $guarded = [];

    public function auditType(): string
    {
        return 'note';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create];
    }
}
