<?php

namespace App\Actions;

use App\Mail\ConversationMessageMail;
use App\Models\Citizen;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\PreRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Messagerie citoyen ↔ consulat.
 * - start() : nouvelle conversation, ouverte par le citoyen ou par le consulat (au sujet d'une pré-demande) ;
 * - reply() : ajout d'un message. Le citoyen ne peut pas écrire dans une conversation clôturée ;
 *   une réponse du consulat la rouvre.
 * Le citoyen est prévenu par email (dans sa langue) de chaque message du consulat.
 */
class PostConversationMessage
{
    public const MAX_BODY = 2000;

    public function start(Citizen $citizen, string $subject, string $body, ?PreRequest $preRequest = null, ?User $staff = null): Conversation
    {
        $subject = trim($subject);
        if ($subject === '' || mb_strlen($subject) > 150) {
            throw ValidationException::withMessages(['subject' => __('Indiquez un sujet (150 caractères au maximum).')]);
        }
        if ($preRequest && (int) $preRequest->citizen_id !== (int) $citizen->id) {
            throw ValidationException::withMessages(['pre_request_id' => __('Dossier introuvable.')]);
        }
        if (! $staff) {
            $open = Conversation::withoutGlobalScopes()->where('citizen_id', $citizen->id)->where('status', 'open')->count();
            if ($open >= Conversation::MAX_OPEN_PER_CITIZEN) {
                throw ValidationException::withMessages(['subject' => __('Vous avez déjà :count conversations ouvertes. Poursuivez l’une d’elles.', ['count' => $open])]);
            }
        }

        $conversation = DB::transaction(function () use ($citizen, $subject, $preRequest) {
            return Conversation::withoutGlobalScopes()->create([
                'consulate_id' => $citizen->consulate_id,
                'citizen_id' => $citizen->id,
                'pre_request_id' => $preRequest?->id,
                'subject' => $subject,
                'status' => 'open',
            ]);
        });

        $this->reply($conversation, $body, $staff);

        return $conversation->refresh();
    }

    public function reply(Conversation $conversation, string $body, ?User $staff = null): ConversationMessage
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > self::MAX_BODY) {
            throw ValidationException::withMessages(['body' => __('Écrivez votre message (:max caractères au maximum).', ['max' => self::MAX_BODY])]);
        }
        if (! $staff && ! $conversation->isOpen()) {
            throw ValidationException::withMessages(['body' => __('Cette conversation est clôturée. Ouvrez une nouvelle conversation.')]);
        }
        if ($staff && ! $staff->isSuperAdmin() && (int) $staff->consulate_id !== (int) $conversation->consulate_id) {
            throw ValidationException::withMessages(['body' => __('Cette conversation ne relève pas de votre consulat.')]);
        }

        $message = DB::transaction(function () use ($conversation, $body, $staff) {
            $message = $conversation->messages()->create([
                'sender' => $staff ? 'staff' : 'citizen',
                'user_id' => $staff?->id,
                'body' => $body,
            ]);
            $conversation->update([
                'last_message_at' => now(),
                'status' => $staff ? 'open' : $conversation->status,
                'unread_by_staff' => ! $staff,
                'unread_by_citizen' => (bool) $staff,
            ]);

            return $message;
        });

        if ($staff) {
            $citizen = $conversation->citizen;
            try {
                Mail::to($citizen->email)->send(new ConversationMessageMail($conversation, $citizen));
            } catch (\Throwable $e) {
                // L'envoi d'email ne doit pas annuler le message : il reste visible dans l'espace citoyen.
                Log::warning('Email de messagerie non envoyé', ['conversation' => $conversation->id, 'error' => $e->getMessage()]);
            }
        }

        return $message;
    }
}
