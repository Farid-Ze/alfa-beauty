<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Fix Batch Inventory Unique Constraint
 * 
 * Solves: batch_number from manufacturer can collide across suppliers
 * 
 * Design Decisions:
 * - Add supplier_id to batch_inventory
 * - Change unique constraint to (product_id, batch_number, supplier_id)
 * - Supplier null means direct from manufacturer/internal
 */
return new class extends Migration
{
    public function up(): void
    {
        // First create suppliers table if not exists
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 20)->nullable();
                $table->text('address')->nullable();
                $table->string('npwp', 30)->nullable()->comment('Tax ID for e-Faktur');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('batch_inventories', function (Blueprint $table) {
            // Add supplier reference
            $table->foreignId('supplier_id')->nullable()->after('product_id')
                ->constrained()->nullOnDelete();
            
            // Add receiving info
            $table->decimal('purchase_price', 15, 2)->nullable()->after('received_at')
                ->comment('Cost price for margin calculation');
            $table->string('purchase_order_number', 50)->nullable()->after('purchase_price');
        });

        // Drop old unique constraint and add new one
        // Note: SQLite doesn't support dropping constraints directly,
        // so we handle this differently based on driver
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver !== 'sqlite') {
            Schema::table('batch_inventories', function (Blueprint $table) {
                $table->dropUnique(['product_id', 'batch_number']);
                $table->unique(['product_id', 'batch_number', 'supplier_id'], 'batch_product_supplier_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver !== 'sqlite') {
            Schema::table('batch_inventories', function (Blueprint $table) {
                $table->dropUnique('batch_product_supplier_unique');
                $table->unique(['product_id', 'batch_number']);
            });
        }

        Schema::table('batch_inventories', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn([
                'supplier_id',
                'purchase_price',
                'purchase_order_number',
            ]);
        });

        Schema::dropIfExists('suppliers');
    }
};
