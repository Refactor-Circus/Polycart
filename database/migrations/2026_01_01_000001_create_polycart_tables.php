<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Polycart\Support\Tables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('carts'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type');
            $table->string('status')->nullable();
            $table->string('source')->nullable()->index();
            $table->string('owner_type')->nullable();
            $table->string('owner_id')->nullable();
            $table->string('session_key')->nullable()->index();
            $table->string('scope_type')->nullable();
            $table->string('scope_id')->nullable();
            $table->string('boundary_type')->nullable();
            $table->string('boundary_id')->nullable();
            $table->string('visibility')->default('private');
            $table->foreignUlid('parent_id')->nullable()->constrained(Tables::name('carts'))->cascadeOnDelete();
            $table->ulid('root_id')->nullable()->index();
            $table->string('label')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id', 'type']);
            $table->index(['type', 'status']);
            $table->index(['scope_type', 'scope_id']);
            $table->index(['boundary_type', 'boundary_id']);
        });

        // Every level of the tree a cart was created in, from its own scope
        // (depth 0) up to the boundary. Written once and never changed.
        Schema::create(Tables::name('cart_paths'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('cart_id')->constrained(Tables::name('carts'))->cascadeOnDelete();
            $table->string('scope_type');
            $table->string('scope_id');
            $table->unsignedSmallInteger('depth');

            $table->unique(['cart_id', 'depth']);
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create(Tables::name('cart_members'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('cart_id')->constrained(Tables::name('carts'))->cascadeOnDelete();
            $table->string('member_type');
            $table->string('member_id');
            $table->string('role');
            $table->timestamps();

            $table->unique(['cart_id', 'member_type', 'member_id']);
            $table->index(['member_type', 'member_id']);
        });

        Schema::create(Tables::name('cart_lines'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('cart_id')->constrained(Tables::name('carts'))->cascadeOnDelete();
            $table->string('purchasable_type')->nullable();
            $table->string('purchasable_id')->nullable();
            $table->string('fingerprint', 64);
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price')->nullable();
            $table->json('options');
            $table->json('meta');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['cart_id', 'fingerprint']);
            $table->index(['purchasable_type', 'purchasable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('cart_members'));
        Schema::dropIfExists(Tables::name('cart_paths'));
        Schema::dropIfExists(Tables::name('cart_lines'));
        Schema::dropIfExists(Tables::name('carts'));
    }
};
