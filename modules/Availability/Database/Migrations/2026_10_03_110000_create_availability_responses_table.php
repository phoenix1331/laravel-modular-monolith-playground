<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fixture_id has no foreign key on purpose. The fixture lives in another
 * module's table, and a database constraint would couple the two schemas:
 * Fixtures could no longer change its storage without breaking this module.
 * Availability resolves fixture ids through the public FixtureLookup instead.
 *
 * Players are Core, so a foreign key to them is fine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('fixture_id');
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16);
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['fixture_id', 'player_id']);
            $table->index(['organisation_id', 'fixture_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_responses');
    }
};
