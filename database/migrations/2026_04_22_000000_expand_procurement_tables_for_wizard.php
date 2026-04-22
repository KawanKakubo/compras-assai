<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand procurement tables for the intelligent wizard.
     * Adds fields for:
     * - Secretaria & environmental flags on requests
     * - Granular viability + municipal program analysis on studies
     * - Price research data + catalog hierarchy on items
     */
    public function up(): void
    {
        // ── procurement_requests ──────────────────────────────
        Schema::table('procurement_requests', function (Blueprint $table): void {
            $table->string('secretaria')->nullable()->after('reference_code');
            $table->boolean('has_environmental_impact')->default(false)->after('reverse_logistics');
            $table->boolean('has_reverse_logistics')->default(false)->after('has_environmental_impact');
            $table->text('demand_memory_calculation')->nullable()->after('has_reverse_logistics');
            $table->json('municipal_policy_details')->nullable()->after('municipal_policy_justification');
        });

        // ── procurement_studies ────────────────────────────────
        Schema::table('procurement_studies', function (Blueprint $table): void {
            // Granular viability fields
            $table->text('viability_technical')->nullable()->after('viability_analysis');
            $table->text('viability_operational')->nullable()->after('viability_technical');
            $table->text('viability_economic')->nullable()->after('viability_operational');
            $table->text('viability_budgetary')->nullable()->after('viability_economic');
            $table->text('viability_legal')->nullable()->after('viability_budgetary');

            // Municipal program analysis
            $table->boolean('municipal_program_eligible')->default(false)->after('municipal_policy_analysis');
            $table->string('municipal_program_segment')->nullable()->after('municipal_program_eligible');
            $table->boolean('municipal_program_me_epp_compatible')->default(false)->after('municipal_program_segment');
            $table->boolean('municipal_program_within_limits')->default(false)->after('municipal_program_me_epp_compatible');
            $table->json('municipal_program_local_suppliers')->nullable()->after('municipal_program_within_limits');
            $table->boolean('municipal_program_competitive')->default(false)->after('municipal_program_local_suppliers');
            $table->string('municipal_program_advantageous')->nullable()->after('municipal_program_competitive');
            $table->string('municipal_program_recommendation')->nullable()->after('municipal_program_advantageous');
            $table->text('municipal_program_justification')->nullable()->after('municipal_program_recommendation');

            // Planning team
            $table->string('planning_team_portaria')->nullable()->after('team_signatures');
        });

        // ── procurement_items ─────────────────────────────────
        Schema::table('procurement_items', function (Blueprint $table): void {
            $table->text('memory_calculation')->nullable()->after('notes');
            $table->string('catmat_group')->nullable()->after('catalog_code');
            $table->string('catmat_class')->nullable()->after('catmat_group');
            $table->string('catmat_pdm')->nullable()->after('catmat_class');
            $table->decimal('price_median', 15, 2)->nullable()->after('total_value');
            $table->decimal('price_min', 15, 2)->nullable()->after('price_median');
            $table->decimal('price_max', 15, 2)->nullable()->after('price_min');
            $table->integer('price_sample_count')->nullable()->after('price_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'secretaria',
                'has_environmental_impact',
                'has_reverse_logistics',
                'demand_memory_calculation',
                'municipal_policy_details',
            ]);
        });

        Schema::table('procurement_studies', function (Blueprint $table): void {
            $table->dropColumn([
                'viability_technical',
                'viability_operational',
                'viability_economic',
                'viability_budgetary',
                'viability_legal',
                'municipal_program_eligible',
                'municipal_program_segment',
                'municipal_program_me_epp_compatible',
                'municipal_program_within_limits',
                'municipal_program_local_suppliers',
                'municipal_program_competitive',
                'municipal_program_advantageous',
                'municipal_program_recommendation',
                'municipal_program_justification',
                'planning_team_portaria',
            ]);
        });

        Schema::table('procurement_items', function (Blueprint $table): void {
            $table->dropColumn([
                'memory_calculation',
                'catmat_group',
                'catmat_class',
                'catmat_pdm',
                'price_median',
                'price_min',
                'price_max',
                'price_sample_count',
            ]);
        });
    }
};
