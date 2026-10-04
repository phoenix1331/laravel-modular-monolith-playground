<?php

declare(strict_types=1);

namespace Modules\Compliance\Policies;

use App\Models\User;
use Modules\Compliance\Models\Certificate;

/**
 * Admins run compliance for the club. Workers may see their own certificates
 * and nobody else's.
 */
class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin() && $user->organisation_id === $certificate->organisation_id;
    }
}
