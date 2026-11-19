<?php

namespace App\Console\Commands;

use App\Actions\EndMemberships;
use Illuminate\Console\Command;

/** Termine chaque nuit les adhésions dont le mandat est arrivé à échéance sans nouvelle élection ni archivage. */
class EndExpiredMemberships extends Command
{
    protected $signature = 'consulat:end-expired-memberships';

    protected $description = 'Passe à « inactif » les adhésions liées à un mandat terminé.';

    public function handle(EndMemberships $action): int
    {
        $count = $action();
        $this->info("{$count} adhésion(s) terminée(s).");

        return self::SUCCESS;
    }
}
