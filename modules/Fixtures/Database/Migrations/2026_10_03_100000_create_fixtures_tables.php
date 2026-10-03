<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixtures' own tables. They reference Core (organisations, teams) but Core
 * never references them, so they can be dropped with the module.
 *
 * Disabling the module for a club leaves these rows in place, which is why
 * switching it back on restores everything.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixtures_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postcode', 10)->nullable();
            $table->timestamps();

            $table->unique(['organisation_id', 'name']);
        });

        Schema::create('fixtures_fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('fixtures_venues')->nullOnDelete();
            $table->string('opponent');
            $table->dateTime('kick_off_at');
            $table->boolean('is_home')->default(true);
            $table->string('competition')->nullable();
            $table->timestamps();

            $table->index(['organisation_id', 'kick_off_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures_fixtures');
        Schema::dropIfExists('fixtures_venues');
    }
};
