<?php

namespace App\Filament\Admin\Resources\ConversationResource\Pages;

use App\Actions\PostConversationMessage;
use App\Filament\Admin\Resources\ConversationResource;
use App\Filament\Admin\Resources\PreRequestResource;
use App\Models\Conversation;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/** Fil d'une conversation : messages, réponse (avec modèles), clôture et réouverture. */
class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    protected static string $view = 'filament.admin.conversation-thread';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Ouvrir la conversation marque les messages du citoyen comme lus par le consulat.
        if ($this->record->unread_by_staff) {
            $this->record->update(['unread_by_staff' => false]);
        }
    }

    public function getTitle(): string
    {
        return $this->record->subject;
    }

    protected function getHeaderActions(): array
    {
        $canWrite = fn () => auth()->user()?->can('update', $this->record);

        return [
            Actions\Action::make('reply')->label(__('Répondre'))->icon('heroicon-o-paper-airplane')
                ->visible($canWrite)
                ->form(ConversationResource::messageFields())
                ->action(function (array $data) {
                    ConversationResource::send(
                        fn () => app(PostConversationMessage::class)->reply($this->record, $data['body'], \Filament\Facades\Filament::auth()->user()),
                        __('Réponse envoyée'),
                    );
                    $this->record->refresh();
                }),
            Actions\Action::make('close')->label(__('Clôturer'))->icon('heroicon-o-lock-closed')->color('gray')
                ->visible(fn () => $canWrite() && $this->record->isOpen())
                ->requiresConfirmation()
                ->modalDescription(__('Le citoyen ne pourra plus écrire dans cette conversation. Une réponse du consulat la rouvrira.'))
                ->action(fn () => $this->record->update(['status' => 'closed'])),
            Actions\Action::make('reopen')->label(__('Rouvrir'))->icon('heroicon-o-lock-open')->color('gray')
                ->visible(fn () => $canWrite() && ! $this->record->isOpen())
                ->action(fn () => $this->record->update(['status' => 'open'])),
            Actions\Action::make('preRequest')->label(__('Voir le dossier'))->icon('heroicon-o-folder-open')->color('gray')
                ->visible(fn () => $this->record->pre_request_id && PreRequestResource::canViewAny())
                ->url(fn () => PreRequestResource::getUrl('index', ['tableSearch' => (string) $this->record->citizen?->email])),
        ];
    }

    /**
     * Messages pour la vue (avec le nom de l'agent : visible du personnel uniquement).
     * Nommée threadMessages() et non getMessages() : Livewire appelle getMessages() pour les messages de validation.
     */
    public function threadMessages()
    {
        return $this->record->messages()->with('user')->get();
    }

    public function statusLabel(): string
    {
        return __(Conversation::STATUSES[$this->record->status] ?? $this->record->status);
    }
}
