<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_CONSULATE_ADMIN = 'consulate_admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_SECRETARIAT = 'secretariat';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            // Double authentification : secret et codes de secours chiffrés en base.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** Changement de mot de passe : tous les appareils de confiance sont oubliés. */
    protected static function booted(): void
    {
        static::updated(function (self $user) {
            if ($user->wasChanged('password')) {
                \App\Services\TrustedDevices::forgetAll($user);
            }
        });
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function consulate(): BelongsTo
    {
        // Pas de scope ici : on doit toujours pouvoir lire son propre consulat.
        return $this->belongsTo(Consulate::class)->withoutGlobalScopes();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    /** Domaine réservé aux comptes de démonstration : jamais utilisables en production. */
    public const DEMO_EMAIL_DOMAIN = '@demo.test';

    public function isDemoAccount(): bool
    {
        return str_ends_with(mb_strtolower((string) $this->email), self::DEMO_EMAIL_DOMAIN);
    }

    /** Accès au panneau : compte actif, avec un rôle ; consulat actif sauf pour le super_admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active || $this->roles->isEmpty()) {
            return false;
        }

        // En production, un compte de démonstration (mot de passe public) n'ouvre jamais l'administration.
        if (app()->isProduction() && $this->isDemoAccount()) {
            return false;
        }

        return $this->isSuperAdmin() || ($this->consulate?->is_active ?? false);
    }
}
