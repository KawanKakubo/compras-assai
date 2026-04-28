<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    protected $fillable = [
        'item_code',
        'description',
        'pdm_code',
        'pdm_name',
        'class_code',
        'class_name',
        'group_code',
        'group_name',
        'is_sustainable',
        'is_active',
        'search_aliases',
    ];
}
