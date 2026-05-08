<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Secretaria extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'acronym',
    ];

    /**
     * Get all users linked to this secretariat.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the single active secretary of this secretariat.
     */
    public function secretary(): HasOne
    {
        return $this->hasOne(User::class)->where('role', User::ROLE_SECRETARIO);
    }

    /**
     * Get the elaborador members of this secretariat.
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_ELABORADOR);
    }
}
