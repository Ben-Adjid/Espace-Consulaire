<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\StaffAbsence;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

/**
 * Calendrier de la semaine : rendez-vous par jour et par heure, jours de fermeture et absences de l'équipe.
 * Données limitées au consulat de l'utilisateur (scope), le super administrateur voit tous les consulats.
 */
class CalendrierRendezVous extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Accueil du public';

    protected static ?string $navigationLabel = 'Calendrier';

    protected static ?string $title = 'Calendrier des rendez-vous';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.calendrier';

    /** Lundi de la semaine affichée (Y-m-d). */
    public string $week = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_appointments'));
    }

    public function mount(): void
    {
        $this->week = CarbonImmutable::today()->startOfWeek()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->week = CarbonImmutable::parse($this->week)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->week = CarbonImmutable::parse($this->week)->addWeek()->toDateString();
    }

    public function thisWeek(): void
    {
        $this->mount();
    }

    protected function getViewData(): array
    {
        $start = CarbonImmutable::parse($this->week ?: today()->startOfWeek()->toDateString())->startOfWeek();
        $end = $start->addDays(5); // lundi → samedi

        $appointments = Appointment::query()->with('service')
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Appointment $a) => $a->appointment_date->toDateString());

        $closed = ClosedDate::query()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()
            ->keyBy(fn ($c) => CarbonImmutable::parse($c->date)->toDateString());

        $absences = StaffAbsence::query()->with('user')->overlapping($start->toDateString(), $end->toDateString())->get();

        $days = [];
        for ($d = $start; $d->lte($end); $d = $d->addDay()) {
            $key = $d->toDateString();
            $days[] = [
                'date' => $d,
                'is_today' => $d->isToday(),
                'closed' => $closed[$key] ?? null,
                'appointments' => $appointments[$key] ?? collect(),
                'absent' => $absences->filter(fn (StaffAbsence $a) => $a->starts_on->toDateString() <= $key && $a->ends_on->toDateString() >= $key)
                    ->map(fn (StaffAbsence $a) => $a->user?->name)->filter()->values(),
            ];
        }

        return [
            'days' => $days,
            'label' => $start->locale(app()->getLocale())->isoFormat('D MMM').' – '.$end->locale(app()->getLocale())->isoFormat('D MMM YYYY'),
            'total' => $appointments->flatten()->count(),
        ];
    }
}
