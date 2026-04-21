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
        Schema::create('procurement_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code')->nullable()->unique();
            $table->string('title');
            $table->string('object_summary', 200);
            $table->enum('priority_level', ['low', 'medium', 'high'])->default('medium');
            $table->text('need_justification');
            $table->text('priority_justification')->nullable();
            $table->date('planned_conclusion_at')->nullable();
            $table->text('linked_request')->nullable();
            $table->text('environmental_impacts')->nullable();
            $table->text('reverse_logistics')->nullable();
            $table->boolean('municipal_policy_applies')->default(false);
            $table->text('municipal_policy_justification')->nullable();
            $table->string('requisition_unit')->nullable();
            $table->string('requester_name')->nullable();
            $table->string('requester_cpf', 14)->nullable();
            $table->string('requester_role')->nullable();
            $table->string('responsible_name')->nullable();
            $table->string('responsible_cpf', 14)->nullable();
            $table->string('responsible_role')->nullable();
            $table->string('status')->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_requests');
    }
};