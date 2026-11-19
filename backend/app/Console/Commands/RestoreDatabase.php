<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Restauration d'une sauvegarde. Par défaut dans une base de test séparée (vérification sans risque).
 * Restaurer dans la base de production exige --target=<nom> explicite et --force.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'consulat:restore {file : Chemin du fichier .sql.gz} {--target= : Base cible (par défaut : consulat_restore_test)} {--force : Confirme l’écrasement de la base cible}';

    protected $description = 'Restaure une sauvegarde dans une base cible et vérifie le nombre de tables.';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        $target = (string) ($this->option('target') ?: 'consulat_restore_test');

        if ($target === $db['database'] && ! $this->option('force')) {
            $this->error('La base cible est la base active : ajoutez --force pour confirmer l’écrasement.');

            return self::FAILURE;
        }

        $sql = $this->readGzip((string) $this->argument('file'));
        if ($sql === null) {
            $this->error('Fichier introuvable ou illisible.');

            return self::FAILURE;
        }

        DB::statement("DROP DATABASE IF EXISTS `$target`");
        DB::statement("CREATE DATABASE `$target` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tmp = storage_path('app/backups/restore-'.now()->format('YmdHis').'.sql');
        File::put($tmp, $sql);

        $result = Process::timeout(600)
            ->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->input(File::get($tmp))
            ->run([(string) env('MYSQL_PATH', 'mysql'), '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'], $target]);
        File::delete($tmp);

        if (! $result->successful()) {
            $this->error('Échec de la restauration : '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        $tables = DB::connection(config('database.default'))->select("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = ?", [$target]);
        $this->info('Restauration réussie dans « '.$target.' » : '.$tables[0]->n.' tables.');

        return self::SUCCESS;
    }

    private function readGzip(string $path): ?string
    {
        if (! File::exists($path)) {
            return null;
        }
        $fp = gzopen($path, 'rb');
        $out = '';
        while (! gzeof($fp)) {
            $out .= gzread($fp, 1 << 16);
        }
        gzclose($fp);

        return $out;
    }
}
