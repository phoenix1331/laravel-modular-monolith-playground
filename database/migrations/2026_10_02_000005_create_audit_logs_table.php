<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only history. organisation_id is nullable because some entries
     * belong to the platform (the super admin logging in), not a club.
     * No foreign keys: the log must outlive the records it describes.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 32);
            $table->string('subject_type', 64);
            $table->string('subject_id', 64)->nullable();
            $table->json('event_data');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_route')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organisation_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
