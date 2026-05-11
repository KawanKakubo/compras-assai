<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

    #[Fillable(['name', 'email', 'password', 'role', 'secretaria_name', 'secretaria_acronym', 'secretaria_id', 'cpf', 'libresign_username', 'libresign_signer_account', 'libresign_password'])]
    #[Hidden(['password', 'remember_token', 'libresign_password'])]
    class User extends Authenticatable
    {
        public function getDecryptedLibresignPasswordAttribute(): ?string
        {
            if (empty($this->libresign_password)) {
                return null;
            }
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($this->libresign_password);
            } catch (\Exception $e) {
                return $this->libresign_password;
            }
        }

        public const ROLE_ADMIN = 'admin';
        public const ROLE_ELABORADOR = 'elaborador';
        public const ROLE_SECRETARIO = 'secretario';
        public const ROLE_GABINETE = 'gabinete';
        public const ROLE_COMPRAS = 'compras';

        public function isAdmin(): bool
        {
            return $this->role === self::ROLE_ADMIN;
        }

        public function isElaborador(): bool
        {
            return $this->role === self::ROLE_ELABORADOR;
        }

        public function isSecretario(): bool
        {
            return $this->role === self::ROLE_SECRETARIO;
        }

        public function isSecretaria(): bool
        {
            return in_array($this->role, [self::ROLE_ELABORADOR, self::ROLE_SECRETARIO]);
        }

        public function isGabinete(): bool
        {
            return $this->role === self::ROLE_GABINETE;
        }

        public function isCompras(): bool
        {
            return $this->role === self::ROLE_COMPRAS;
        }

        public function secretaria()
        {
            return $this->belongsTo(Secretaria::class);
        }

        public function getSecretariaNameAttribute()
        {
            return $this->secretaria ? $this->secretaria->name : ($this->attributes['secretaria_name'] ?? null);
        }

        public function getSecretariaAcronymAttribute()
        {
            return $this->secretaria ? $this->secretaria->acronym : ($this->attributes['secretaria_acronym'] ?? null);
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
