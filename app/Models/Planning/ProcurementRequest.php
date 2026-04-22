<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_code',
        'secretaria',
        'title',
        'object_summary',
        'priority_level',
        'need_justification',
        'priority_justification',
        'planned_conclusion_at',
        'linked_request',
        'environmental_impacts',
        'reverse_logistics',
        'has_environmental_impact',
        'has_reverse_logistics',
        'demand_memory_calculation',
        'municipal_policy_applies',
        'municipal_policy_justification',
        'municipal_policy_details',
        'requisition_unit',
        'requester_name',
        'requester_cpf',
        'requester_role',
        'responsible_name',
        'responsible_cpf',
        'responsible_role',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'planned_conclusion_at' => 'date',
            'municipal_policy_applies' => 'boolean',
            'has_environmental_impact' => 'boolean',
            'has_reverse_logistics' => 'boolean',
            'municipal_policy_details' => 'array',
            'metadata' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementItem::class);
    }

    public function studies(): HasMany
    {
        return $this->hasMany(ProcurementStudy::class);
    }

    /**
     * Generate the next sequential reference code: SD-{year}-{seq}
     */
    public static function generateReferenceCode(): string
    {
        $year = now()->year;
        $prefix = "SD-{$year}-";
        $lastCode = static::where('reference_code', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(reference_code FROM ?) AS INTEGER) DESC', [strlen($prefix) + 1])
            ->value('reference_code');

        $nextSeq = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $nextSeq = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }
}