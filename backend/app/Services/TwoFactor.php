<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Double authentification TOTP (codes à 6 chiffres d'une application d'authentification).
 * Le secret et les codes de secours sont chiffrés en base (cast « encrypted »).
 */
class TwoFactor
{
    public function __construct(private Google2FA $engine = new Google2FA())
    {
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    /** QR code SVG à scanner avec l'application d'authentification. */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $uri = $this->engine->getQRCodeUrl(config('app.name'), $user->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd()));

        return $writer->writeString($uri);
    }

    /** Vérifie un code à 6 chiffres (tolérance d'une période de 30 s avant/après). */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        return preg_match('/^\d{6}$/', $code) === 1 && (bool) $this->engine->verifyKey($secret, $code, 1);
    }

    /** @return array<int, string> */
    public function recoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))->all();
    }

    /** Utilise un code de secours (usage unique) ; renvoie vrai s'il était valide. */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $code = Str::upper(trim($code));
        if (! in_array($code, $codes, true)) {
            return false;
        }
        $user->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$code]))])->save();

        return true;
    }

    /** Le rôle de cet utilisateur impose-t-il la double authentification ? */
    public static function requiredFor(User $user): bool
    {
        // Comptes qui gèrent élections, bureau, commission et rôles : ils peuvent changer qui est élu ou qui a accès.
        if (config('security.two_factor_for_managers', true)
            && ($user->isSuperAdmin() || $user->can('manage_content') || $user->can('manage_users'))) {
            return true;
        }

        $roles = array_filter(array_map('trim', explode(',', (string) config('security.two_factor_required_roles'))));

        return $roles !== [] && $user->hasAnyRole($roles);
    }
}
