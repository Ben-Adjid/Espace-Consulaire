<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PostConversationMessage;
use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\PreRequest;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Messagerie de l'espace citoyen : le citoyen ne voit que SES conversations, dans SON consulat.
 * Les réponses du consulat sont signées « Consulat » : le nom des agents n'est jamais transmis.
 */
class ConversationController extends Controller
{
    public function __construct(private ConsulateContext $context, private PostConversationMessage $post)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $items = Conversation::withoutGlobalScopes()->where('citizen_id', $citizen->id)
            // reorder() : la relation messages() trie déjà par id croissant ; latest() seul ne l'annulerait pas.
            ->with(['messages' => fn ($q) => $q->reorder('id', 'desc')->limit(1)])
            ->orderByDesc('last_message_at')->orderByDesc('id')->get()
            ->map(fn (Conversation $c) => $this->summary($c));

        return response()->json([
            'data' => $items,
            'meta' => ['unread' => $items->where('unread', true)->count(), 'max_open' => Conversation::MAX_OPEN_PER_CITIZEN],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $citizen = $this->citizen($request, active: true);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:'.PostConversationMessage::MAX_BODY],
            'pre_request_id' => ['nullable', 'integer'],
        ]);

        $pre = null;
        if (! empty($data['pre_request_id'])) {
            $pre = PreRequest::withoutGlobalScopes()->where('citizen_id', $citizen->id)->find($data['pre_request_id']);
            if (! $pre) {
                return response()->json(['message' => __('Dossier introuvable.')], 404);
            }
        }

        $conversation = $this->post->start($citizen, $data['subject'], $data['body'], $pre);

        return response()->json(['data' => $this->thread($conversation)], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = $this->owned($request, $id);
        if (! $conversation instanceof Conversation) {
            return $conversation;
        }

        if ($conversation->unread_by_citizen) {
            $conversation->update(['unread_by_citizen' => false]);
        }

        return response()->json(['data' => $this->thread($conversation)]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $conversation = $this->owned($request, $id, active: true);
        if (! $conversation instanceof Conversation) {
            return $conversation;
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:'.PostConversationMessage::MAX_BODY]]);
        $this->post->reply($conversation, $data['body']);

        return response()->json(['data' => $this->thread($conversation->refresh())]);
    }

    private function summary(Conversation $c): array
    {
        $last = $c->messages->first();

        return [
            'id' => $c->id,
            'subject' => $c->subject,
            'status' => $c->status,
            'status_label' => __(Conversation::STATUSES[$c->status] ?? $c->status),
            'pre_request_id' => $c->pre_request_id,
            'unread' => $c->unread_by_citizen,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'last_excerpt' => $last ? Str::limit($last->body, 90) : null,
            'last_by' => $last ? ($last->sender === 'staff' ? 'consulate' : 'citizen') : null,
        ];
    }

    private function thread(Conversation $c): array
    {
        $c->load(['messages', 'preRequest.service']);

        return [
            'id' => $c->id,
            'subject' => $c->subject,
            'status' => $c->status,
            'status_label' => __(Conversation::STATUSES[$c->status] ?? $c->status),
            'can_reply' => $c->isOpen(),
            'pre_request' => $c->preRequest ? ['id' => $c->preRequest->id, 'service_name' => $c->preRequest->service?->name] : null,
            'messages' => $c->messages->map(fn (ConversationMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'by' => $m->sender === 'staff' ? 'consulate' : 'citizen',
                'at' => $m->created_at?->toIso8601String(),
            ])->values(),
        ];
    }

    private function citizen(Request $request, bool $active = false): Citizen|JsonResponse
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }
        if ($active && ! $citizen->isActive()) {
            return response()->json(['message' => __('Votre compte n’est pas encore actif.')], 403);
        }

        return $citizen;
    }

    private function owned(Request $request, int $id, bool $active = false): Conversation|JsonResponse
    {
        $citizen = $this->citizen($request, $active);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        return Conversation::withoutGlobalScopes()->where('citizen_id', $citizen->id)->where('id', $id)->first()
            ?? response()->json(['message' => __('Conversation introuvable.')], 404);
    }
}
