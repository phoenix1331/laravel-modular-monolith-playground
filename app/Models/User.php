<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Enums\Role;
use App\Exceptions\InvalidUserOrganisation;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property int|null $organisation_id
 * @property Role $role
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organisation|null $organisation
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[ObservedBy(AuditObserver::class)]
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mirrors the column default so a new, unsaved user already has a role.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'worker',
    ];

    /**
     * role and organisation_id are deliberately not fillable: they decide
     * what a user can see, so they are set explicitly, never mass assigned
     * from a request.
     *
     * The saving hook guards the tenancy invariant at the model layer, so no
     * code path can store a worker without a club or a super admin with one.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->role->requiresOrganisation() && $user->organisation_id === null) {
                throw InvalidUserOrganisation::missing($user->role);
            }

            if (! $user->role->requiresOrganisation() && $user->organisation_id !== null) {
                throw InvalidUserOrganisation::notAllowed();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Teams a worker is assigned to coach or manage.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    /**
     * Where this user lands after logging in, or when visiting a guest-only
     * page while signed in.
     */
    public function homeUrl(): string
    {
        return $this->isSuperAdmin() ? route('platform.dashboard') : route('dashboard');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isWorker(): bool
    {
        return $this->role === Role::Worker;
    }

    public function auditType(): string
    {
        return 'user';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
