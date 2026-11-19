<?php

namespace App\Services;

use App\Models\Consulate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Appareils de confiance pour la double authentification.
 * Après un code à 6 chiffres réussi, le navigateur n'en redemande plus jusqu'à la fin de la confiance.
 * Le mot de passe reste toujours demandé.
 *
 * - Compte d'un consulat : confiance jusqu'à la fin de la période d'abonnement de CE consulat.
 *   Tout paiement enregistré oublie les appareils du consulat.
 * - Super admin : aucun renouvellement automatique. La confiance dure jusqu'à révocation manuelle
 *   (« Oublier tous les appareils ») ou changement de mot de passe.
 *
 * Dans les deux cas, le changement de mot de passe oublie tous les appareils.
 */
class TrustedDevices
{
    public const COOKIE = 'two_factor_device';

    /** Cookie d'une durée de 5 ans quand il n'y a pas d'échéance (super admin). */
    private const OPEN_ENDED_MINUTES = 60 * 24 * 365 * 5;

    /**
     * Fin de la confiance accordée à un navigateur, ou null s'il n'y a pas d'échéance (super admin).
     * Un consulat sans abonnement valide n'obtient aucune confiance (renvoie maintenant).
     */
    public static function expiresAt(?User $user = null, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        $now ??= CarbonImmutable::now();

        if ($user?->consulate_id) {
            $until = Consulate::withoutGlobalScopes()->find($user->consulate_id)?->subscription_until;
            if ($until && CarbonImmutable::parse($until)->endOfDay()->gt($now)) {
                return CarbonImmutable::parse($until)->endOfDay();
            }

            return $now;
        }

        return null;
    }

    /** Oublie les appareils de tous les utilisateurs d'un consulat (paiement enregistré). */
    public static function forgetForConsulate(int $consulateId): int
    {
        $userIds = User::query()->where('consulate_id', $consulateId)->pluck('id');

        return DB::table('two_factor_trusted_devices')->whereIn('user_id', $userIds)->delete();
    }

    /** Mémorise le navigateur courant ; renvoie le cookie à poser sur la réponse. */
    public static function remember(User $user, Request $request): Cookie
    {
        $token = Str::random(64);
        $expires = self::expiresAt($user);

        DB::table('two_factor_trusted_devices')->insert([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'expires_at' => $expires,
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $minutes = $expires ? max(1, (int) $expires->diffInMinutes(now())) : self::OPEN_ENDED_MINUTES;

        return cookie(self::COOKIE, $token, $minutes, null, null, true, true, false, 'lax');
    }

    /** Ce navigateur est-il un appareil de confiance valide pour cet utilisateur ? */
    public static function isTrusted(User $user, Request $request): bool
    {
        $token = (string) $request->cookie(self::COOKIE, '');
        if ($token === '') {
            return false;
        }

        return DB::table('two_factor_trusted_devices')
            ->where('user_id', $user->getKey())
            ->where('token_hash', hash('sha256', $token))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    /** Oublie tous les appareils de l'utilisateur (révocation, changement de mot de passe, paiement). */
    public static function forgetAll(User $user): int
    {
        return DB::table('two_factor_trusted_devices')->where('user_id', $user->getKey())->delete();
    }

    /** Supprime les appareils dont la durée est écoulée (tâche quotidienne). Sans échéance, rien n'est supprimé. */
    public static function purgeExpired(): int
    {
        return DB::table('two_factor_trusted_devices')->whereNotNull('expires_at')->where('expires_at', '<=', now())->delete();
    }
}
