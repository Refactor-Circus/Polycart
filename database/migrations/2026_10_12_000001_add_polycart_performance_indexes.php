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
        $carts = Tables::name('carts');
        $paths = Tables::name('cart_paths');

        Schema::table($carts, function (Blueprint $table) use ($carts): void {
            // Pruning deleted carts, the newest-first listing and its cursor,
            // the dashboard's recently updated carts, and a cart's children.
            $table->index('deleted_at', $carts.'_deleted_at_index');
            $table->index(['created_at', 'id'], $carts.'_created_at_id_index');
            $table->index('updated_at', $carts.'_updated_at_index');

            // MySQL and MariaDB already index the parent_id foreign key.
            if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->index('parent_id', $carts.'_parent_id_index');
            }
        });

        // Carts anywhere under a scope are read from this index alone.
        Schema::table($paths, function (Blueprint $table) use ($paths): void {
            $table->dropIndex(['scope_type', 'scope_id']);
            $table->index(['scope_type', 'scope_id', 'cart_id'], $paths.'_scope_type_scope_id_cart_id_index');
        });
    }

    public function down(): void
    {
        $carts = Tables::name('carts');
        $paths = Tables::name('cart_paths');

        if (! Schema::hasTable($carts) || ! Schema::hasTable($paths)) {
            return;
        }

        Schema::table($paths, function (Blueprint $table) use ($paths): void {
            $table->dropIndex($paths.'_scope_type_scope_id_cart_id_index');
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::table($carts, function (Blueprint $table) use ($carts): void {
            if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->dropIndex($carts.'_parent_id_index');
            }

            $table->dropIndex($carts.'_updated_at_index');
            $table->dropIndex($carts.'_created_at_id_index');
            $table->dropIndex($carts.'_deleted_at_index');
        });
    }
};
