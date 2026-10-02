<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Tenancy\BelongsToOrganisation;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only tenant-owned model, so the scope can be proven before any real
 * tenant-owned model exists.
 */
class Note extends Model
{
    use BelongsToOrganisation;

    protected $guarded = [];
}
