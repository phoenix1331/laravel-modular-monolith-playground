<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * organisation_id is nullable only so the super admin can exist. The User
     * model enforces that every other role has one.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organisation_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->string('role')->default('worker')->after('organisation_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organisation_id');
            $table->dropColumn('role');
        });
    }
};
