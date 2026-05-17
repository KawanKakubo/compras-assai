<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequest extends Model
{
    use HasFactory;

    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_ASSINADO = 'assinado';
    public const STATUS_EM_ANALISE = 'em_analise';
    public const STATUS_APROVADO_COMPRAS = 'aprovado_compras';
    public const STATUS_REJEITADO = 'rejeitado';
    public const STATUS_DEVOLVIDO = 'devolvido';
    public const STATUS_FINALIZADO = 'finalizado';
    public const STATUS_INATIVO = 'inativo';

    public const STEP_ELABORADOR = 'elaborador';
    public const STEP_SECRETARIO = 'secretario';
    public const STEP_GABINETE = 'gabinete';
    public const STEP_COMPRAS = 'compras';

    public function canBeEditedBy($user): bool
    {
        if (!$user) return false;
        
        // Elaborador can edit if it's draft or rejected back to them
        if ($user->isElaborador() && in_array($this->status, [self::STATUS_RASCUNHO, self::STATUS_REJEITADO, self::STATUS_DEVOLVIDO])) {
            return true;
        }

        return false;
    }

    public function canBeApprovedBy($user): bool
    {
        if (!$user) return false;

        return match ($this->current_step) {
            self::STEP_ELABORADOR => $user->isElaborador() && $this->status === self::STATUS_RASCUNHO,
            self::STEP_SECRETARIO => $user->isSecretario() && ($this->status === self::STATUS_ASSINADO || $this->status === self::STATUS_RASCUNHO),
            self::STEP_GABINETE => $user->isGabinete() && $this->status === self::STATUS_EM_ANALISE,
            default => false,
        };
    }

    public function canBeSigned(): bool
    {
        $user = auth()->user();
        return $this->canBeApprovedBy($user);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_ASSINADO;
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected $fillable = [
        'reference_code',
        'user_id',
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
        'rejection_reason',
        'current_step',
        'metadata',
        'signed_at',
        'signature_hash',
        'signed_file_path',
        'libresign_uuid',
        'libresign_sign_request_uuid',
        'assinatura_status',
        'pdf_assinado_url',
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
    public static function generateReferenceCode(?string $acronym = null): string
    {
        $year = now()->year;
        $prefix = $acronym ? "SD-{$acronym}-{$year}-" : "SD-{$year}-";
        
        // Find the last code with this specific prefix
        $lastCode = static::where('reference_code', 'like', "{$prefix}%")
            ->orderBy('id', 'DESC')
            ->value('reference_code');

        $nextSeq = 1;
        if ($lastCode) {
            $parts = explode('-', $lastCode);
            $nextSeq = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }
}