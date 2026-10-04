<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('price_pence');
            $table->timestamps();

            $table->unique(['organisation_id', 'name']);
        });

        Schema::create('kit_shop_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('kit_shop_products')->cascadeOnDelete();
            $table->string('size', 8);
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'size']);
        });

        Schema::create('kit_shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('kit_shop_products')->restrictOnDelete();
            $table->string('size', 8);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('total_pence');
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_shop_orders');
        Schema::dropIfExists('kit_shop_stock');
        Schema::dropIfExists('kit_shop_products');
    }
};
