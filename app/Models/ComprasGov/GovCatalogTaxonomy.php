<?php

namespace App\Models\ComprasGov;

use Illuminate\Database\Eloquent\Model;

class GovCatalogTaxonomy extends Model
{
    protected $fillable = [
        'catalog_type',
        'level_name',
        'parent_code',
        'code',
        'description',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    /**
     * Scope for a specific catalog and level.
     */
    public function scopeLevel($query, string $type, string $level, ?string $parentCode = null)
    {
        return $query->where('catalog_type', $type)
                     ->where('level_name', $level)
                     ->where('parent_code', $parentCode);
    }
}
