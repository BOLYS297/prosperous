<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\RapportController;
use App\Models\Boutique;
use App\Models\User;
use App\Models\Vente;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RapportsVentesJournalieresTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_include_daily_sales_per_boutique(): void
    {
        $boutiqueA = Boutique::create(['nom' => 'Boutique A', 'type' => 'boutique']);
        $boutiqueB = Boutique::create(['nom' => 'Boutique B', 'type' => 'boutique']);

        $admin = User::create([
            'nom_utilisateur' => 'adminreport',
            'email' => 'adminreport@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'boutique_id' => $boutiqueA->id,
        ]);

        $firstDate = Carbon::create(2026, 7, 3, 10, 0, 0);
        $secondDate = Carbon::create(2026, 7, 3, 15, 0, 0);
        $thirdDate = Carbon::create(2026, 7, 4, 9, 0, 0);

        $venteA = Vente::create(['boutique_id' => $boutiqueA->id, 'user_id' => $admin->id, 'montant_total' => 12000]);
        $venteA->forceFill(['created_at' => $firstDate, 'updated_at' => $firstDate])->save();

        $venteB = Vente::create(['boutique_id' => $boutiqueB->id, 'user_id' => $admin->id, 'montant_total' => 8000]);
        $venteB->forceFill(['created_at' => $secondDate, 'updated_at' => $secondDate])->save();

        $venteC = Vente::create(['boutique_id' => $boutiqueA->id, 'user_id' => $admin->id, 'montant_total' => 5000]);
        $venteC->forceFill(['created_at' => $thirdDate, 'updated_at' => $thirdDate])->save();

        Auth::login($admin);

        $controller = app(RapportController::class);
        $response = $controller->index(new Request(['mois' => '07', 'annee' => '2026']));

        $this->assertEquals(2, $response->getData()['ventesJournalieresParBoutique']->count());
        $this->assertTrue($response->getData()['ventesJournalieresParBoutique']->has('2026-07-03'));
    }
}
