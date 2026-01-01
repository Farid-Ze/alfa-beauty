<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Weight and Unit of Measure (UoM) to Products
 * 
 * Solves:
 * 1. Missing weight column for shipping calculation
 * 2. Missing unit of measure for B2B bulk ordering
 * 
 * Design Decisions:
 * - weight stored in grams (integer) for precision without floating point issues
 * - selling_unit defines the base unit (pcs, bottle, tube, sachet, etc.)
 * - units_per_case for bulk ordering (1 case = X units)
 * - min_order_qty for MOQ enforcement per product
 * - weight_per_unit allows accurate shipping calculation
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Weight & Dimensions for shipping calculation
            $table->unsignedInteger('weight_grams')->default(0)->after('stock')
                ->comment('Weight per unit in grams');
            $table->unsignedInteger('length_mm')->nullable()->after('weight_grams')
                ->comment('Length in millimeters for volumetric weight');
            $table->unsignedInteger('width_mm')->nullable()->after('length_mm')
                ->comment('Width in millimeters');
            $table->unsignedInteger('height_mm')->nullable()->after('width_mm')
                ->comment('Height in millimeters');
            
            // Unit of Measure (UoM) system
            $table->string('selling_unit', 20)->default('pcs')->after('height_mm')
                ->comment('Base selling unit: pcs, bottle, tube, sachet, jar, etc.');
            $table->unsignedSmallInteger('units_per_case')->default(12)->after('selling_unit')
                ->comment('How many units in one case/carton for bulk ordering');
            $table->unsignedSmallInteger('min_order_qty')->default(1)->after('units_per_case')
                ->comment('Minimum order quantity (MOQ) in selling units');
            $table->unsignedSmallInteger('order_increment')->default(1)->after('min_order_qty')
                ->comment('Order must be in multiples of this number');
            
            // Index for filtering products by MOQ
            $table->index(['min_order_qty', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['min_order_qty', 'is_active']);
            $table->dropColumn([
                'weight_grams',
                'length_mm',
                'width_mm',
                'height_mm',
                'selling_unit',
                'units_per_case',
                'min_order_qty',
                'order_increment',
            ]);
        });
    }
};
