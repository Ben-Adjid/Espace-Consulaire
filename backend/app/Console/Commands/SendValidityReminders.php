<?php

namespace App\Console\Commands;

use App\Mail\ValidityReminderMail;
use App\Models\PreRequest;
use App\Models\RequestDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Rappels d'échéance, une seule fois par élément : pièces déposées qui expirent dans les 30 jours,
 * et validités de dossiers délivrés (carte, visa, passeport) qui prennent fin dans les 30 jours.
 */
class SendValidityReminders extends Command
{
    protected $signature = 'consulat:send-validity-reminders';

    protected $description = 'Rappelle aux citoyens les échéances de documents et de validités dans les 30 jours.';

    public function handle(): int
    {
        $horizon = today()->addDays(30)->toDateString();
        $sent = 0;

        RequestDocument::with('preRequest.citizen', 'preRequest.service')
            ->whereNotNull('expires_on')->whereNull('expiry_reminded_at')
            ->whereBetween('expires_on', [today()->toDateString(), $horizon])
            ->get()
            ->each(function (RequestDocument $doc) use (&$sent) {
                $citizen = $doc->preRequest?->citizen;
                if (! $citizen?->email) {
                    return;
                }
                Mail::to($citizen->email)->send(new ValidityReminderMail($citizen, $doc->label, $doc->expires_on->toDateString(), $this->url($citizen)));
                $doc->forceFill(['expiry_reminded_at' => now()])->save();
                $sent++;
            });

        PreRequest::with('citizen', 'service')
            ->whereNotNull('valid_until')->whereNull('validity_reminded_at')
            ->whereBetween('valid_until', [today()->toDateString(), $horizon])
            ->get()
            ->each(function (PreRequest $pre) use (&$sent) {
                $citizen = $pre->citizen;
                if (! $citizen?->email) {
                    return;
                }
                Mail::to($citizen->email)->send(new ValidityReminderMail($citizen, (string) $pre->service?->name, $pre->valid_until->toDateString(), $this->url($citizen)));
                $pre->forceFill(['validity_reminded_at' => now()])->save();
                $sent++;
            });

        $this->info("{$sent} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }

    private function url($citizen): string
    {
        $slug = $citizen->consulate()->value('slug');

        return rtrim((string) config('app.frontend_url'), '/').'/'.$slug.'/'.($citizen->locale ?: 'fr').'/compte';
    }
}
