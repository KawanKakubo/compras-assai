<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procurement_studies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_in_pca')->default(false);
            $table->string('pca_reference')->nullable();
            $table->text('pca_description')->nullable();
            $table->text('need_description');
            $table->text('motivation')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('correlated_contracts')->nullable();
            $table->text('solution_requirements')->nullable();
            $table->text('demand_estimate')->nullable();
            $table->text('environmental_analysis')->nullable();
            $table->text('solution_mapping')->nullable();
            $table->text('discarded_solutions')->nullable();
            $table->text('parceling_justification')->nullable();
            $table->text('chosen_solution')->nullable();
            $table->decimal('estimated_total_cost', 15, 2)->nullable();
            $table->text('expected_results')->nullable();
            $table->text('viability_analysis')->nullable();
            $table->boolean('municipal_policy_applies')->default(false);
            $table->text('municipal_policy_analysis')->nullable();
            $table->enum('viability_decision', ['viable', 'viable_with_restrictions', 'not_viable'])->default('viable');
            $table->text('viability_justification')->nullable();
            $table->json('team_signatures')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_studies');
    }
};