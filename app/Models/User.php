<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

    #[Fillable(['name', 'email', 'password', 'role', 'secretaria_name', 'secretaria_acronym'])]
    #[Hidden(['password', 'remember_token'])]
    class User extends Authenticatable
    {
        public const ROLE_ADMIN = 'admin';
        public const ROLE_SECRETARIA = 'secretaria';
        public const ROLE_GABINETE = 'gabinete';
        public const ROLE_COMPRAS = 'compras';

        public function isAdmin(): bool
        {
            return $this->role === self::ROLE_ADMIN;
        }

        public function isSecretaria(): bool
        {
            return $this->role === self::ROLE_SECRETARIA;
        }

        public function isGabinete(): bool
        {
            return $this->role === self::ROLE_GABINETE;
        }

        public function isCompras(): bool
        {
            return $this->role === self::ROLE_COMPRAS;
        }

        public function procurementRequests()
        {
            return $this->hasMany(\App\Models\Planning\ProcurementRequest::class);
        }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
