<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->default('');
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('stock')->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
        });

        // Postgres ignores "unsigned"; enforce stock >= 0 in the DB. SQLite (tests) cannot ADD CONSTRAINT.
        if ('pgsql' === DB::getDriverName()) {
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_non_negative CHECK (stock >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
