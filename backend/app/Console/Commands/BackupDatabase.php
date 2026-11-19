<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Sauvegarde de la base (mysqldump, compressée). Le mot de passe passe par l'environnement, jamais par la ligne de commande.
 * Les sauvegardes anciennes sont purgées au-delà de la rétention configurée.
 */
class BackupDatabase extends Command
{
    protected $signature = 'consulat:backup {--keep= : Nombre de sauvegardes à conserver}';

    protected $description = 'Sauvegarde la base de données (fichier .sql.gz) et purge les plus anciennes.';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $file = $dir.'/'.$db['database'].'-'.now()->format('Ymd-His').'.sql';
        $binary = (string) env('MYSQLDUMP_PATH', 'mysqldump');

        $result = Process::timeout(600)
            ->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->run([
                $binary,
                '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
                '--single-transaction', '--routines', '--triggers', '--result-file='.$file,
                $db['database'],
            ]);

        if (! $result->successful()) {
            $this->error('Échec de la sauvegarde : '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        // Compression : fichier .sql.gz, puis suppression du SQL brut.
        $gz = $file.'.gz';
        $fp = gzopen($gz, 'wb9');
        gzwrite($fp, File::get($file));
        gzclose($fp);
        File::delete($file);

        $keep = (int) ($this->option('keep') ?? env('BACKUP_KEEP', 14));
        $files = collect(File::files($dir))->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'))
            ->sortByDesc(fn ($f) => $f->getMTime())->values();
        $files->slice($keep)->each(fn ($f) => File::delete($f->getPathname()));

        $this->info('Sauvegarde créée : '.$gz);

        return self::SUCCESS;
    }
}
