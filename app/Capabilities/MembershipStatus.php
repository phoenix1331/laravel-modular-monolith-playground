<?php

declare(strict_types=1);

namespace App\Capabilities;

/**
 * Is a player a paid-up member of their club?
 *
 * A *capability*: Core defines the question, a module may answer it. This is
 * how soft dependencies work here.
 *
 * - Core binds NullMembershipStatus by default, so the question always has an
 *   answer, even with no module installed.
 * - Subscriptions rebinds it while installed, and answers for real only for
 *   clubs that have Subscriptions enabled.
 * - KitShop asks the question by type-hinting this interface. It never
 *   imports Subscriptions, so Deptrac sees no dependency between the two.
 *
 * The contract sits in Core rather than in Subscriptions' Contracts/ because
 * Core must bind the null default, and Core never imports from a module.
 */
interface MembershipStatus
{
    /**
     * Whether membership is tracked for the current club at all. When false,
     * callers should not restrict anything on membership grounds.
     */
    public function tracksMembership(): bool;

    public function isPaidUp(int $playerId): bool;
}
