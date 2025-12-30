<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Loyalty Tiers Table
        if (!Schema::hasTable('loyalty_tiers')) {
            Schema::create('loyalty_tiers', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Silver, Gold
                $table->decimal('min_spend', 15, 2)->default(0);
                $table->decimal('point_multiplier', 3, 2)->default(1.0);
                $table->timestamps();
            });

            // Insert Default Tiers
            DB::table('loyalty_tiers')->insert([
                [
                    'name' => 'Silver',
                    'min_spend' => 0,
                    'point_multiplier' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Gold',
                    'min_spend' => 50000000,
                    'point_multiplier' => 1.5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 2. Modify Users Table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'points')) {
                $table->integer('points')->default(0)->after('email');
            }
            if (!Schema::hasColumn('users', 'total_spend')) {
                $table->decimal('total_spend', 15, 2)->default(0)->after('points');
            }
            if (!Schema::hasColumn('users', 'loyalty_tier_id')) {
                // Default to Silver (ID 1)
                $table->foreignId('loyalty_tier_id')->default(1)->after('total_spend')->constrained('loyalty_tiers');
            }
        });

        // 3. Create Point Transactions Table
        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('amount'); // Can be positive (earn) or negative (redeem)
                $table->string('type'); // 'earn', 'redeem', 'adjust'
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'loyalty_tier_id')) {
                 $table->dropForeign(['loyalty_tier_id']);
                 $table->dropColumn('loyalty_tier_id');
            }
            if (Schema::hasColumn('users', 'points')) {
                $table->dropColumn('points');
            }
             if (Schema::hasColumn('users', 'total_spend')) {
                $table->dropColumn('total_spend');
            }
        });

        Schema::dropIfExists('loyalty_tiers');
    }
};
