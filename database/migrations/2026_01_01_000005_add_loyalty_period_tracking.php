<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Loyalty Period Tracking
 * 
 * Solves: Loyalty tier min_spend has no reset mechanism
 * 
 * Design Decisions:
 * - Add period_type to loyalty_tiers (lifetime, yearly, quarterly)
 * - Create user_loyalty_periods to track spending per period
 * - tier_evaluated_at tracks when user's tier was last calculated
 * - tier_valid_until allows tier expiration
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add period configuration to loyalty_tiers
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->string('period_type', 20)->default('yearly')->after('discount_percent')
                ->comment('lifetime, yearly, quarterly, monthly');
            $table->unsignedSmallInteger('tier_validity_months')->default(12)->after('period_type')
                ->comment('How long tier status is valid after qualification');
            $table->boolean('auto_downgrade')->default(true)->after('tier_validity_months')
                ->comment('Whether to downgrade if spend threshold not met');
        });

        // Track user spending per period for tier evaluation
        Schema::create('user_loyalty_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_tier_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_quarter')->nullable()
                ->comment('1-4 for quarterly, null for yearly');
            $table->decimal('period_spend', 15, 2)->default(0);
            $table->unsignedInteger('period_orders')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('tier_qualified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'period_year', 'period_quarter'], 'user_loyalty_period_unique');
            $table->index(['period_year', 'period_quarter']);
        });

        // Add tier tracking to users
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tier_evaluated_at')->nullable()->after('loyalty_tier_id')
                ->comment('When tier was last calculated');
            $table->date('tier_valid_until')->nullable()->after('tier_evaluated_at')
                ->comment('When current tier expires');
            $table->decimal('current_period_spend', 15, 2)->default(0)->after('tier_valid_until')
                ->comment('Cached spend for current period');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tier_evaluated_at', 'tier_valid_until', 'current_period_spend']);
        });

        Schema::dropIfExists('user_loyalty_periods');

        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->dropColumn(['period_type', 'tier_validity_months', 'auto_downgrade']);
        });
    }
};
