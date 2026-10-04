<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Policies;

use App\Models\User;
use Modules\Subscriptions\Models\Fee;

/**
 * Money is the treasurer's job: club admins only. Workers do not see fees.
 */
class FeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function pay(User $user, Fee $fee): bool
    {
        return $user->isAdmin() && $user->organisation_id === $fee->organisation_id && ! $fee->isPaid();
    }

    public function delete(User $user, Fee $fee): bool
    {
        return $this->pay($user, $fee);
    }
}
