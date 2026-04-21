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
        'title',
        'object_summary',
        'priority_level',
        'need_justification',
        'priority_justification',
        'planned_conclusion_at',
        'linked_request',
        'environmental_impacts',
        'reverse_logistics',
        'municipal_policy_applies',
        'municipal_policy_justification',
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
}