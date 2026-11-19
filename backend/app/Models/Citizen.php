<?php

namespace App\Models;

use App\Models\Concerns\BelongsToConsulate;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Mail\CitizenLinkMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Compte citoyen. Rattaché au consulat de son pays de résidence ; actif seulement après validation de ce consulat.
 */
class Citizen extends Authenticatable
{
    use BelongsToConsulate, HasApiTokens, Notifiable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'validated_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'phone_mismatch' => 'boolean',
        ];
    }

    /** Lien vers une page du frontend, dans le fragment (le jeton n'est ni journalisé ni envoyé au serveur). */
    public function frontendUrl(string $page, string $fragment): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/'.$this->consulate->slug.'/'.app()->getLocale().'/compte/'.$page.'#'.$fragment;
    }

    /** Génère un jeton de vérification d'email : seul son hachage est stocké. */
    public function issueEmailVerificationToken(): string
    {
        $token = Str::random(48);
        $this->forceFill(['email_verification_token' => hash('sha256', $token)])->save();

        return $token;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** Appelé par le broker de réinitialisation (Password::broker('citizens')). */
    public function sendPasswordResetNotification($token): void
    {
        $url = $this->frontendUrl('reinitialiser', 'token='.$token.'&email='.rawurlencode($this->email));

        Mail::to($this->email)->send(new CitizenLinkMail($this, 'password', $url));
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Personnes à charge (espace famille). */
    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class)->orderBy('full_name');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->withoutGlobalScopes();
    }

    /** Clé de comparaison du pays de résidence (sans accents, casse ignorée). */
    public static function countryKey(string $country): string
    {
        return Consulate::countryKey($country);
    }

    public function associationOfficers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssociationOfficer::class)->with('association')->latest('mandate_start');
    }

    public function associationMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssociationMember::class)->with('association');
    }

    /**
     * Rôle associatif du citoyen : mandats en cours (bureau), adhésion active, et anciens mandats.
     * Quand le mandat prend fin, le citoyen redevient un citoyen simple : rien ne reste « en cours ».
     *
     * @return array{current: list<array<string, mixed>>, past: list<array<string, mixed>>, member: ?string}
     */
    public function associationStatus(): array
    {
        $current = [];
        $past = [];

        foreach ($this->associationOfficers as $officer) {
            $row = [
                'association' => $officer->association?->name,
                'role' => Association::ROLES[$officer->role] ?? $officer->role,
                'mandate_start' => $officer->mandate_start?->toDateString(),
                'mandate_end' => $officer->mandate_end?->toDateString(),
            ];
            if ($officer->isCurrent()) {
                $current[] = $row;
            } else {
                $past[] = $row;
            }
        }

        $member = $this->associationMembers->first(fn ($m) => $m->status === 'active');

        return [
            'current' => $current,
            'past' => array_slice($past, 0, 5),
            'member' => $member ? ($member->association?->name) : null,
        ];
    }
}
