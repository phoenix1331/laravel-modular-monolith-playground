<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The platform operator: one super admin with no organisation.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $user = new User([
            'name' => 'Platform Admin',
            'email' => 'super@platform.test',
            'password' => Hash::make('password'),
        ]);
        $user->role = Role::SuperAdmin;
        $user->organisation_id = null;
        $user->email_verified_at = now();
        $user->save();
    }
}
