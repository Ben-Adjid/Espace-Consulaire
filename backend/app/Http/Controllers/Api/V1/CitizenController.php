<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Mail\AssociationRoleMail;
use App\Mail\CitizenLinkMail;
use App\Mail\CitizenRegistered;
use App\Models\Association;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\CitizenReport;
use App\Models\Consulate;
use App\Services\ConsulateContext;
use App\Support\PhoneCountry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Compte citoyen : inscription (soumise à la validation de résidence du consulat), connexion, profil, rendez-vous.
 * Un compte n'est utilisable qu'auprès du consulat auquel il est rattaché.
 */
class CitizenController extends Controller
{
    /** Inscription : POST /v1/{consulate}/citizen/register */
    public function register(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160', Rule::unique('citizens', 'email')],
            'phone' => ['required', 'string', 'regex:/^[0-9+().\s-]{6,32}$/'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'residence_country' => ['required', 'string', 'max:80'],
            'nationality' => ['accepted'],
            'consent' => ['accepted'],
            'residence_proof' => ['nullable', 'string', 'max:300'],
        ]);

        $residence = trim($data['residence_country']);

        // Résidence : seul le consulat qui couvre ce pays peut valider le compte.
        if (! $consulate->covers($residence)) {
            $suggested = $this->suggestedConsulate($consulate, $residence);

            return response()->json([
                'message' => $suggested
                    ? __('Votre pays de résidence est rattaché au consulat :name. Créez votre compte là-bas.', ['name' => $suggested->name])
                    : __('Aucun consulat en ligne ne couvre encore votre pays de résidence.'),
                'errors' => ['residence_country' => [__('Pays de résidence non couvert par ce consulat.')]],
                'suggested_consulate' => $suggested ? ['slug' => $suggested->slug, 'name' => $suggested->name] : null,
            ], 422);
        }

        $citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $consulate->id,
            'full_name' => trim($data['full_name']),
            'email' => mb_strtolower(trim($data['email'])),
            'locale' => app()->getLocale(), // langue du site au moment de l'inscription : celle de ses emails
            'phone' => trim($data['phone']),
            'password' => $data['password'],
            'nationality' => $consulate->represented_country ? $consulate->getTranslation('represented_country', 'fr') : '',
            'residence_country' => $residence,
            'residence_proof' => trim((string) ($data['residence_proof'] ?? '')) ?: null,
            // Signalement seulement : le numéro indique un autre pays. Le consulat décide à la validation.
            'phone_mismatch' => PhoneCountry::mismatchesResidence($data['phone'], $residence),
            'status' => Citizen::STATUS_PENDING,
        ]);

        // Confirmation de l'adresse email : lien dans le fragment ; seul le hachage du jeton est stocké.
        $verifyToken = $citizen->issueEmailVerificationToken();
        $this->sendMail($citizen, new CitizenLinkMail($citizen, 'verify', $citizen->frontendUrl('verifier', 'token='.$verifyToken.'&email='.rawurlencode($citizen->email))));
        $this->sendMail($citizen, new CitizenRegistered($citizen));

        return response()->json([
            'message' => __('Votre compte a été créé. Il sera activé après vérification de votre résidence par le consulat.'),
            'status' => $citizen->status,
        ], 201);
    }

    /** Connexion : POST /v1/{consulate}/citizen/login → jeton Sanctum (uniquement si le compte est actif). */
    public function login(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $throttleKey = 'citizen-login:'.$consulate->id.':'.$email;

        // Verrouillage après 5 échecs : 15 minutes, sans révéler si le compte existe.
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => __('Trop de tentatives. Réessayez dans quelques minutes ou réinitialisez votre mot de passe.'),
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        $citizen = Citizen::withoutGlobalScopes()->where('email', $email)->first();

        if (! $citizen || ! Hash::check($data['password'], $citizen->password)) {
            RateLimiter::hit($throttleKey, 900);

            return response()->json(['message' => __('Adresse email ou mot de passe incorrect.')], 422);
        }

        RateLimiter::clear($throttleKey);

        // La langue utilisée pour se connecter devient celle de ses prochains emails.
        if ($citizen->locale !== app()->getLocale()) {
            $citizen->forceFill(['locale' => app()->getLocale()])->save();
        }

        if ($citizen->consulate_id !== $consulate->id) {
            return response()->json(['message' => __('Ce compte est rattaché à un autre consulat. Connectez-vous depuis le site de ce consulat.')], 403);
        }

        if (! $citizen->hasVerifiedEmail()) {
            return response()->json(['message' => __('Confirmez votre adresse email (lien reçu par email) pour vous connecter.'), 'status' => 'unverified'], 403);
        }

        if ($citizen->status === Citizen::STATUS_PENDING) {
            return response()->json(['message' => __('Votre compte est en attente de validation par le consulat.'), 'status' => 'pending'], 403);
        }

        if ($citizen->status === Citizen::STATUS_REJECTED) {
            return response()->json([
                'message' => __('Votre demande de compte n’a pas été acceptée.'),
                'status' => 'rejected',
                'reason' => $citizen->rejection_reason,
            ], 403);
        }

        $token = $citizen->createToken('citizen-web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'data' => $this->profile($citizen),
        ]);
    }

    /** Confirmation de l'email : POST /v1/{consulate}/citizen/verify-email (token, email) */
    public function verifyEmail(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();
        $data = $request->validate(['token' => ['required', 'string', 'size:48'], 'email' => ['required', 'email']]);

        $citizen = Citizen::withoutGlobalScopes()->where('email', mb_strtolower(trim($data['email'])))->first();
        $valid = $citizen
            && $citizen->consulate_id === $consulate->id
            && $citizen->email_verification_token
            && hash_equals($citizen->email_verification_token, hash('sha256', $data['token']));

        if (! $valid) {
            return response()->json(['message' => __('Ce lien de confirmation est invalide ou a déjà été utilisé.')], 422);
        }

        $citizen->forceFill(['email_verified_at' => now(), 'email_verification_token' => null])->save();

        // Membre du bureau saisi avec cet email : le mandat s'affiche sur le compte dès la confirmation.
        $linked = AssociationOfficer::with('association')
            ->whereNull('citizen_id')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($citizen->email)])
            ->whereIn('association_id', Association::withoutGlobalScopes()->where('consulate_id', $consulate->id)->select('id'))
            ->get();
        AssociationOfficer::whereIn('id', $linked->pluck('id'))->update(['citizen_id' => $citizen->id]);

        // Nomination arrivée avant la création du compte : l'email part maintenant, une seule fois, si le mandat est en cours.
        foreach ($linked as $officer) {
            if (! $officer->isCurrent() || ! $officer->association) {
                continue;
            }
            try {
                Mail::to($citizen->email)->send(new AssociationRoleMail(
                    $citizen,
                    'appointed',
                    $officer->association->name,
                    Association::ROLES[$officer->role] ?? $officer->role,
                ));
            } catch (\Throwable $e) {
                Log::warning('Email de nomination non envoyé', ['officer' => $officer->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => __('Votre adresse email est confirmée.')]);
    }

    /** Renvoi du lien de confirmation : même réponse que le compte existe ou non. */
    public function resendVerification(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();
        $data = $request->validate(['email' => ['required', 'email']]);

        $citizen = Citizen::withoutGlobalScopes()->where('email', mb_strtolower(trim($data['email'])))->first();
        if ($citizen && $citizen->consulate_id === $consulate->id && ! $citizen->hasVerifiedEmail()) {
            $token = $citizen->issueEmailVerificationToken();
            $this->sendMail($citizen, new CitizenLinkMail($citizen, 'verify', $citizen->frontendUrl('verifier', 'token='.$token.'&email='.rawurlencode($citizen->email))));
        }

        return response()->json(['message' => __('Si un compte non confirmé existe pour cette adresse, un nouveau lien vient d’être envoyé.')]);
    }

    /** Mot de passe oublié : même réponse dans tous les cas (pas d'énumération des comptes). */
    public function forgotPassword(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();
        $data = $request->validate(['email' => ['required', 'email']]);

        $citizen = Citizen::withoutGlobalScopes()->where('email', mb_strtolower(trim($data['email'])))->first();
        if ($citizen && $citizen->consulate_id === $consulate->id) {
            Password::broker('citizens')->sendResetLink(['email' => $citizen->email]);
        }

        return response()->json(['message' => __('Si un compte existe pour cette adresse, un lien de réinitialisation vient d’être envoyé.')]);
    }

    /** Nouveau mot de passe : POST /v1/{consulate}/citizen/reset-password (token, email, password, password_confirmation) */
    public function resetPassword(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $citizen = Citizen::withoutGlobalScopes()->where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $citizen || $citizen->consulate_id !== $consulate->id) {
            return response()->json(['message' => __('Ce lien de réinitialisation est invalide ou expiré.')], 422);
        }

        $status = Password::broker('citizens')->reset(
            [
                'email' => $citizen->email,
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (Citizen $c, string $password) {
                // Le cast du modèle hache le mot de passe ; toutes les sessions sont fermées.
                $c->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $c->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __('Ce lien de réinitialisation est invalide ou expiré.')], 422);
        }

        return response()->json(['message' => __('Votre mot de passe a été modifié. Vous pouvez vous connecter.')]);
    }

    /** Signalements du citoyen : GET /v1/{consulate}/citizen/reports */
    public function reports(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $items = CitizenReport::withoutGlobalScopes()->where('citizen_id', $citizen->id)->latest()->get()
            ->map(fn (CitizenReport $r) => [
                'id' => $r->id,
                'category' => $r->category,
                'category_label' => __(CitizenReport::CATEGORIES[$r->category] ?? $r->category),
                'subject' => $r->subject,
                'status' => $r->status,
                'status_label' => __(CitizenReport::STATUSES[$r->status] ?? $r->status),
                'response' => $r->response,
                'created_at' => $r->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }

    /** Nouveau signalement : POST /v1/{consulate}/citizen/reports */
    public function storeReport(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(CitizenReport::CATEGORIES))],
            'subject' => ['required', 'string', 'min:4', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        CitizenReport::withoutGlobalScopes()->create([
            'consulate_id' => $citizen->consulate_id,
            'citizen_id' => $citizen->id,
            'category' => $data['category'],
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
            'status' => 'new',
        ]);

        return response()->json([
            'message' => $data['category'] === CitizenReport::CATEGORY_INTEGRITY
                ? __('Votre signalement a été transmis directement à l’administration centrale de la plateforme, pas au consulat.')
                : __('Votre signalement a été transmis au consulat. Vous suivrez la réponse dans votre espace.'),
        ], 201);
    }

    /** Déconnexion : POST /v1/{consulate}/citizen/logout */
    public function logout(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $citizen->currentAccessToken()?->delete();

        return response()->json(['message' => __('Vous êtes déconnecté.')]);
    }

    /** Profil : GET /v1/{consulate}/citizen/me */
    public function me(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        return response()->json(['data' => $this->profile($citizen)]);
    }

    /**
     * Modification du téléphone (immédiate) et de l'email (confirmée par lien envoyé à la nouvelle adresse).
     * Le changement d'email exige le mot de passe actuel, et prévient l'ancienne adresse.
     * PATCH /v1/{consulate}/citizen/profile
     */
    public function updateProfile(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $request->validate([
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:160'],
            'current_password' => ['required_with:email', 'string'],
        ]);

        if (array_key_exists('phone', $data)) {
            $citizen->forceFill(['phone' => $data['phone']])->save();
        }

        $newEmail = isset($data['email']) ? mb_strtolower(trim($data['email'])) : null;
        $message = __('Vos coordonnées sont enregistrées.');

        if ($newEmail !== null && $newEmail !== $citizen->email) {
            if (! Hash::check($data['current_password'], $citizen->password)) {
                return response()->json(['message' => __('Mot de passe incorrect.')], 422);
            }

            $exists = Citizen::withoutGlobalScopes()->where('email', $newEmail)->exists();
            if ($exists) {
                return response()->json(['message' => __('Cette adresse email est déjà utilisée.')], 422);
            }

            $token = Str::random(48);
            $citizen->forceFill([
                'pending_email' => $newEmail,
                'email_verification_token' => hash('sha256', $token),
            ])->save();

            $link = $citizen->frontendUrl('profil', 'change-email='.$token);
            Mail::to($newEmail)->send(new CitizenLinkMail($citizen, 'verify', $link));
            Mail::to($citizen->email)->send(new \App\Mail\EmailChangeNoticeMail($citizen, $newEmail));

            $message = __('Un lien de confirmation vient d’être envoyé à la nouvelle adresse. Votre email ne change qu’après confirmation.');
        }

        return response()->json(['message' => $message, 'data' => $this->profile($citizen->refresh())]);
    }

    /** Confirmation du nouvel email : POST /v1/{consulate}/citizen/email/confirm */
    public function confirmEmailChange(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $request->validate(['token' => ['required', 'string', 'size:48']]);

        $valid = $citizen->pending_email
            && $citizen->email_verification_token
            && hash_equals($citizen->email_verification_token, hash('sha256', $data['token']));

        if (! $valid) {
            return response()->json(['message' => __('Ce lien de confirmation est invalide ou a déjà été utilisé.')], 422);
        }

        $citizen->forceFill([
            'email' => $citizen->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();

        return response()->json(['message' => __('Votre nouvelle adresse email est confirmée.'), 'data' => $this->profile($citizen->refresh())]);
    }

    /** Rendez-vous du citoyen : GET /v1/{consulate}/citizen/appointments */
    public function appointments(Request $request, ConsulateContext $context): JsonResponse
    {
        $citizen = $this->citizenFor($request, $context, requireActive: true);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $items = $citizen->appointments()->with('service')->orderByDesc('appointment_date')->get();

        return response()->json(['data' => AppointmentResource::collection($items)]);
    }

    /**
     * Le compte authentifié par jeton, rattaché au consulat de la route.
     * Retourne soit le citoyen, soit une réponse d'erreur.
     */
    public function citizenFor(Request $request, ConsulateContext $context, bool $requireActive = false): Citizen|JsonResponse
    {
        $consulate = $context->require();
        $citizen = $request->user('sanctum');

        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }

        if ($citizen->consulate_id !== $consulate->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }

        if ($requireActive && ! $citizen->isActive()) {
            return response()->json(['message' => __('Votre compte n’est pas encore actif.')], 403);
        }

        return $citizen;
    }

    private function profile(Citizen $citizen): array
    {
        return [
            'full_name' => $citizen->full_name,
            'email' => $citizen->email,
            'phone' => $citizen->phone,
            'pending_email' => $citizen->pending_email,
            'association' => $citizen->associationStatus(),
            'residence_country' => $citizen->residence_country,
            'nationality' => $citizen->nationality,
            'status' => $citizen->status,
            'status_label' => match ($citizen->status) {
                Citizen::STATUS_ACTIVE => __('Actif'),
                Citizen::STATUS_REJECTED => __('Refusé'),
                default => __('En attente de validation'),
            },
            'rejection_reason' => $citizen->rejection_reason,
            'validated_at' => $citizen->validated_at?->toIso8601String(),
        ];
    }

    private function suggestedConsulate(Consulate $current, string $residence): ?Consulate
    {
        return Consulate::query()
            ->where('is_active', true)
            ->where('id', '!=', $current->id)
            ->get()
            ->first(fn (Consulate $c) => $c->representedKey() === $current->representedKey() && $c->covers($residence));
    }

    private function sendMail(Citizen $citizen, object $mailable): void
    {
        try {
            Mail::to($citizen->email)->send($mailable);
        } catch (\Throwable $e) {
            // L'échec d'envoi ne doit jamais bloquer l'inscription.
            Log::warning('Email de compte non envoyé', ['citizen' => $citizen->id, 'error' => $e->getMessage()]);
        }
    }
}
