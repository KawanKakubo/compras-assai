<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'procurement_study_id',
        'item_type',
        'catalog_code',
        'catmat_group',
        'catmat_class',
        'catmat_pdm',
        'description',
        'unit',
        'quantity',
        'unit_value',
        'total_value',
        'price_median',
        'price_min',
        'price_max',
        'price_sample_count',
        'source_system',
        'source_reference',
        'is_sustainable',
        'notes',
        'memory_calculation',
        'metadata',
        'catmat_description',
        'detailed_description',
        'specification_justification',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'price_median' => 'decimal:2',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'price_sample_count' => 'integer',
            'is_sustainable' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function study(): BelongsTo
    {
        return $this->belongsTo(ProcurementStudy::class, 'procurement_study_id');
    }
}