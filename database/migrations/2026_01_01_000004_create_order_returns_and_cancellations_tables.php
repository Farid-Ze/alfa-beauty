<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Order Returns and Cancellations Tables
 * 
 * Solves: Missing cancel/refund flow for B2B transactions
 * 
 * Design Decisions:
 * - Separate tables for returns (product back) vs cancellations (order void)
 * - return_items tracks individual products returned
 * - Supports partial returns (return some items from order)
 * - Links to batch_inventory for FEFO stock restoration
 * - refund_amount separate from return value (restocking fees, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Order Cancellations - for voiding entire orders
        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason_code', 50)->comment('cancelled_by_customer, out_of_stock, payment_failed, etc.');
            $table->text('reason_notes')->nullable();
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->string('refund_status', 30)->default('pending')
                ->comment('pending, processing, completed, declined');
            $table->string('refund_method', 30)->nullable()
                ->comment('original_payment, bank_transfer, credit, etc.');
            $table->timestamp('refund_completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'refund_status']);
            $table->index('reason_code');
        });

        // Order Returns - for product returns (can be partial)
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('requested')
                ->comment('requested, approved, received, inspected, completed, rejected');
            $table->string('return_type', 30)->default('refund')
                ->comment('refund, exchange, credit');
            $table->string('reason_code', 50)->comment('defective, wrong_item, expired, damaged_shipping, etc.');
            $table->text('reason_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->decimal('return_value', 15, 2)->default(0)->comment('Original value of returned items');
            $table->decimal('restocking_fee', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0)->comment('return_value - restocking_fee');
            $table->string('refund_status', 30)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['order_id', 'status']);
            $table->index('return_number');
            $table->index('reason_code');
        });

        // Return Items - individual products in a return
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_inventory_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Original batch for stock restoration');
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->unsignedInteger('quantity_approved')->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->string('condition', 30)->nullable()
                ->comment('unopened, opened, damaged, expired');
            $table->text('inspection_notes')->nullable();
            $table->boolean('restock')->default(false)->comment('Whether to return to inventory');
            $table->timestamps();
            
            $table->index(['order_return_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('order_cancellations');
    }
};
