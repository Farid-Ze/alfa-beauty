<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Batch Inventory Table - Required for BPOM Traceability
     * Tracks product batches with expiry dates for 6 years after last production (per BPOM PIF requirements).
     */
    public function up(): void
    {
        Schema::create('batch_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Batch Identification (BPOM Requirement)
            $table->string('batch_number')->index(); // LOT/Batch number from manufacturer
            $table->string('lot_number')->nullable(); // Alternative lot tracking
            
            // Quantity Management
            $table->integer('quantity_received')->default(0); // Initial stock received
            $table->integer('quantity_available')->default(0); // Current available stock
            $table->integer('quantity_sold')->default(0); // Total sold from this batch
            $table->integer('quantity_damaged')->default(0); // Damaged/disposed units
            
            // Date Tracking (Critical for BPOM)
            $table->date('manufactured_at')->nullable(); // Manufacturing date
            $table->date('expires_at'); // Expiry date (required)
            $table->date('received_at')->nullable(); // Date received in warehouse
            
            // Pricing (for Near-Expiry Pricing strategy)
            $table->decimal('cost_price', 15, 2)->nullable(); // Purchase cost
            $table->decimal('near_expiry_discount_percent', 5, 2)->default(0); // Auto-discount when near expiry
            
            // Status Flags
            $table->boolean('is_active')->default(true); // Can be sold
            $table->boolean('is_near_expiry')->default(false); // Computed flag
            $table->boolean('is_expired')->default(false); // Computed flag
            
            // Warehouse (for future multi-warehouse support)
            $table->unsignedBigInteger('warehouse_id')->nullable();
            
            // Supplier/Origin Tracking
            $table->string('supplier_name')->nullable();
            $table->string('country_of_origin')->nullable()->default('IT'); // Italy for Alfaparf
            
            // Notes & Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit Fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['product_id', 'expires_at']);
            $table->index(['batch_number', 'expires_at']);
            $table->index('is_near_expiry');
            $table->unique(['product_id', 'batch_number']); // One batch per product
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_inventories');
    }
};
