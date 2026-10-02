<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;

/**
 * Players follow their team: anyone who can view the team can view its
 * players, and only club admins change them.
 */
class PlayerPolicy
{
    public function __construct(private readonly TeamPolicy $teams) {}

    public function view(User $user, Player $player): bool
    {
        return $this->teams->view($user, $player->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->teams->update($user, $team);
    }

    public function update(User $user, Player $player): bool
    {
        return $this->teams->update($user, $player->team);
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->update($user, $player);
    }
}
