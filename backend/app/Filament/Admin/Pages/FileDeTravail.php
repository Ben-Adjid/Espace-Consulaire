<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Admin\Resources\ConversationResource;
use App\Filament\Admin\Resources\PreRequestResource;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\PreRequest;
use Filament\Pages\Page;

/**
 * Ma file de travail : ce qui attend l'agent, en un seul écran.
 * Dossiers à décider (en retard et urgents d'abord), messages non lus, rendez-vous du jour non encore arrivés.
 */
class FileDeTravail extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Accueil du public';

    protected static ?string $navigationLabel = 'Ma file de travail';

    protected static ?string $title = 'Ma file de travail';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.admin.pages.file-de-travail';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin()
            || $user?->can('decide_requests')
            || $user?->can('manage_messages')
            || $user?->can('manage_appointments'));
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $canDecide = (bool) ($user?->isSuperAdmin() || $user?->can('decide_requests'));
        $canMessage = (bool) ($user?->isSuperAdmin() || $user?->can('manage_messages'));
        $canAppointments = (bool) ($user?->isSuperAdmin() || $user?->can('manage_appointments'));

        // Dossiers : en retard d'abord, puis urgents, puis les plus anciens.
        $requests = $canDecide
            ? PreRequest::query()->where('status', 'submitted')->with(['service', 'citizen', 'assignee'])->get()
                ->map(fn (PreRequest $p) => ['model' => $p, 'overdue' => $p->isOverdue()])
                // Clé de tri : en retard (0 d'abord), puis urgent (0 d'abord), puis date de soumission la plus ancienne.
                ->sortBy(fn (array $row) => [
                    $row['overdue'] ? 0 : 1,
                    $row['model']->is_urgent ? 0 : 1,
                    $row['model']->submitted_at?->timestamp ?? 0,
                ])->values()->take(30)
            : collect();

        $messages = $canMessage
            ? Conversation::query()->where('unread_by_staff', true)->where('status', 'open')
                ->with('citizen')->latest('last_message_at')->limit(30)->get()
            : collect();

        $appointmentsWaiting = $canAppointments
            ? Appointment::query()->whereDate('appointment_date', today())
                ->where('status', '!=', AppointmentStatus::Cancelled)
                ->whereNull('checked_in_at')->count()
            : null;

        return [
            'requests' => $requests,
            'messages' => $messages,
            'appointmentsWaiting' => $appointmentsWaiting,
            'requestsUrl' => PreRequestResource::getUrl('index'),
            'messagesUrl' => $canMessage ? ConversationResource::getUrl('index') : null,
        ];
    }
}
