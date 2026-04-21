<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementStudy extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'is_in_pca',
        'pca_reference',
        'pca_description',
        'need_description',
        'motivation',
        'prerequisites',
        'correlated_contracts',
        'solution_requirements',
        'demand_estimate',
        'environmental_analysis',
        'solution_mapping',
        'discarded_solutions',
        'parceling_justification',
        'chosen_solution',
        'estimated_total_cost',
        'expected_results',
        'viability_analysis',
        'municipal_policy_applies',
        'municipal_policy_analysis',
        'viability_decision',
        'viability_justification',
        'team_signatures',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_in_pca' => 'boolean',
            'estimated_total_cost' => 'decimal:2',
            'municipal_policy_applies' => 'boolean',
            'team_signatures' => 'array',
            'metadata' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementItem::class);
    }
}