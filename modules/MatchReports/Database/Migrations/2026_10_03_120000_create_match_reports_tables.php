<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * As in Availability, fixture_id is an opaque reference to another module's
 * record: no foreign key, resolved through FixtureLookup. Foreign keys inside
 * the module (performances to reports) and to Core (players, users) are fine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_reports_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('fixture_id')->unique();
            $table->unsignedTinyInteger('goals_for');
            $table->unsignedTinyInteger('goals_against');
            $table->text('summary')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('match_reports_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_id')->constrained('match_reports_reports')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('goals')->default(0);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_reports_performances');
        Schema::dropIfExists('match_reports_reports');
    }
};
