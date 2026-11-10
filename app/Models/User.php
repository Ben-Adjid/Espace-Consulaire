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

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
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

    /** Accès au panneau : compte actif, avec un rôle ; consulat actif sauf pour le super_admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active || $this->roles->isEmpty()) {
            return false;
        }

        return $this->isSuperAdmin() || ($this->consulate?->is_active ?? false);
    }
}
