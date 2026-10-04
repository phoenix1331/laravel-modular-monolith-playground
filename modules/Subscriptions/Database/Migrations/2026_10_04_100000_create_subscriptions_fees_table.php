<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money is stored as whole pence in an integer column: no floating point,
 * no rounding surprises.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('amount_pence');
            $table->date('due_on');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->timestamps();

            $table->index(['organisation_id', 'paid_at', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions_fees');
    }
};
