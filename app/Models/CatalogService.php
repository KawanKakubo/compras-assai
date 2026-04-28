<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogService extends Model
{
    protected $fillable = [
        'service_code',
        'description',
        'group_code',
        'group_name',
        'is_active',
        'search_aliases',
    ];
}
