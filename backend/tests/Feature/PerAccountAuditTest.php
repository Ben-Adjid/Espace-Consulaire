<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Appointment;
use App\Models\User;
use Database\Seeders\ConsulateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Audit compte par compte, sur les 9 comptes de démonstration (base de test isolée, application neuve par compte) :
 * connexion par le formulaire, tableau de bord, sidebar exacte, pages interdites, périmètre des données, déconnexion.
 * Avec AUDIT_REPORT=1, écrit le tableau dans docs/AUDIT-COMPTES.md.
 */
class PerAccountAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, array<int, string>> */
    private static array $rows = [];

    /** Rubriques réservées : jamais visibles en dehors des rôles indiqués. */
    private const ONLY = [
        'Tableau global' => ['super_admin'],
        'Nouveau consulat' => ['super_admin'],
        'Abonnements des consulats' => ['super_admin'],
        'Mon abonnement' => ['consulate_admin'],
        'Équipe et comptes' => ['super_admin', 'consulate_admin'],
        'Journal d’activité' => ['super_admin', 'consulate_admin'],
    ];

    /** Pages interdites par rôle (accès direct par URL). */
    private const FORBIDDEN = [
        'consulate_admin' => ['/admin/tableau-global', '/admin/nouveau-consulat', '/admin/consulate-payments'],
        'staff' => ['/admin/tableau-global', '/admin/nouveau-consulat', '/admin/consulate-payments', '/admin/users', '/admin/mon-abonnement', '/admin/activity-logs'],
        'super_admin' => ['/admin/mon-abonnement'],
    ];

    public static function accounts(): array
    {
        $list = ['superadmin@demo.test'];
        foreach (['tchad', 'tchad-cm', 'tchad-gh', 'tchad-tg'] as $c) {
            $list[] = "admin.{$c}@demo.test";
            $list[] = "staff.{$c}@demo.test";
        }

        return array_combine($list, array_map(fn ($e) => [$e], $list));
    }

    #[DataProvider('accounts')]
    public function test_account_matches_its_role(string $email): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, ConsulateSeeder::class, SuperAdminSeeder::class]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::where('email', $email)->firstOrFail();
        $role = $user->getRoleNames()->first();
        $anomalies = [];

        // 1. Connexion réelle par le formulaire, puis tableau de bord
        Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => ConsulateSeeder::demoPassword()])
            ->call('authenticate')
            ->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($user);
        $html = $this->get('/admin')->assertOk()->getContent();
        $dashboard = str_contains($html, 'Abonnements à renouveler') ? 'plateforme' : (str_contains($html, 'Aujourd’hui au consulat') ? 'consulat (travail du jour)' : '?');

        // 2. Sidebar : rubriques réellement affichées
        preg_match_all('/fi-sidebar-item-label[^>]*>\s*([^<]+?)\s*</u', $html, $m);
        $menu = array_values(array_unique(array_map(fn ($l) => html_entity_decode(trim($l)), $m[1])));
        foreach (self::ONLY as $item => $roles) {
            if (in_array($item, $menu, true) && ! in_array($role, $roles, true)) {
                $anomalies[] = "voit « {$item} »";
            }
        }

        // 3. Pages interdites par URL
        foreach (self::FORBIDDEN[$role] ?? [] as $url) {
            $status = $this->get($url)->status();
            if (! in_array($status, [403, 404], true)) {
                $anomalies[] = "{$url} → {$status}";
            }
        }

        // 4. Périmètre : la liste des rendez-vous ne contient que ceux de son consulat
        $list = $this->get('/admin/appointments')->assertOk()->getContent();
        if ($user->consulate_id) {
            $foreign = Appointment::withoutGlobalScopes()->where('consulate_id', '!=', $user->consulate_id)
                ->pluck('reference')->filter(fn ($ref) => str_contains($list, $ref));
            if ($foreign->isNotEmpty()) {
                $anomalies[] = 'voit des rendez-vous d’un autre consulat';
            }
        }

        // 5. Déconnexion
        $this->post('/admin/logout');
        $this->assertGuest();

        self::$rows[$email] = [$email, $role, $user->consulate?->slug ?? 'toute la plateforme', $dashboard,
            count($menu).' : '.implode(', ', $menu), $anomalies ? implode(' ; ', $anomalies) : 'aucune'];

        $this->assertSame([], $anomalies, "{$email} : ".implode(' ; ', $anomalies));
    }

    public static function tearDownAfterClass(): void
    {
        if (getenv('AUDIT_REPORT') && self::$rows) {
            $md = "# Audit compte par compte\n\nComptes de démonstration, base de test isolée, application neuve par compte. "
                ."Généré par `backend/tests/Feature/PerAccountAuditTest.php` le ".date('d/m/Y H:i').".\n\n"
                ."Vérifié pour chaque compte : connexion par le formulaire, tableau de bord, sidebar, pages interdites par URL, "
                ."périmètre des rendez-vous, déconnexion.\n\n"
                ."| Utilisateur | Rôle | Périmètre | Tableau de bord | Sidebar | Anomalies |\n|---|---|---|---|---|---|\n";
            foreach (self::$rows as $r) {
                $md .= '| '.implode(' | ', $r)." |\n";
            }
            file_put_contents(dirname(__DIR__, 3).'/docs/AUDIT-COMPTES.md', $md);
        }
        parent::tearDownAfterClass();
    }
}
