<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Tax/PPN Columns to Orders and OrderItems
 * 
 * Solves: Missing tax tracking for Indonesian B2B e-Faktur compliance
 * 
 * Design Decisions:
 * - tax_rate stored as decimal (11.00 for 11% PPN)
 * - tax_amount calculated and stored for audit trail
 * - subtotal_before_tax for clear breakdown
 * - tax stored at both order and item level for flexibility
 * - e_faktur_number for DJP integration readiness
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add tax columns to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_before_tax', 15, 2)->default(0)->after('subtotal')
                ->comment('Sum of all items before tax');
            $table->decimal('tax_rate', 5, 2)->default(11.00)->after('subtotal_before_tax')
                ->comment('Tax rate percentage (11% PPN standard)');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate')
                ->comment('Calculated tax amount');
            $table->boolean('is_tax_inclusive')->default(false)->after('tax_amount')
                ->comment('True if prices already include tax');
            $table->string('e_faktur_number', 50)->nullable()->after('is_tax_inclusive')
                ->comment('e-Faktur number from DJP for tax compliance');
            $table->timestamp('e_faktur_date')->nullable()->after('e_faktur_number')
                ->comment('Date e-Faktur was issued');
            
            $table->index('e_faktur_number');
        });

        // Add tax columns to order_items for item-level tax tracking
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price_before_tax', 15, 2)->default(0)->after('unit_price')
                ->comment('Unit price before tax');
            $table->decimal('tax_rate', 5, 2)->default(11.00)->after('unit_price_before_tax')
                ->comment('Tax rate for this item');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate')
                ->comment('Tax amount for this line item');
            $table->decimal('subtotal_before_tax', 15, 2)->default(0)->after('tax_amount')
                ->comment('Line subtotal before tax');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['e_faktur_number']);
            $table->dropColumn([
                'subtotal_before_tax',
                'tax_rate',
                'tax_amount',
                'is_tax_inclusive',
                'e_faktur_number',
                'e_faktur_date',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_price_before_tax',
                'tax_rate',
                'tax_amount',
                'subtotal_before_tax',
            ]);
        });
    }
};
