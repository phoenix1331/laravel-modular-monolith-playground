<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('reference')->nullable();
            $table->date('issued_on');
            $table->date('expires_on');
            $table->timestamps();

            $table->index(['organisation_id', 'expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_certificates');
    }
};
