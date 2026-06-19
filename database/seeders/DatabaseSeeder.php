<?php

namespace Database\Seeders;

use App\Models\Boutique;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Boutique::firstOrCreate(
            ['nom' => 'Boutique 1'],
            ['type' => 'boutique', 'solde' => 0]
        );

        Boutique::firstOrCreate(
            ['nom' => 'Boutique 2'],
            ['type' => 'boutique', 'solde' => 0]
        );

        Boutique::firstOrCreate(
            ['nom' => 'Magasin Central'],
            ['type' => 'magasin', 'solde' => 0]
        );

        $this->call([
            AdminSeeder::class,
            ProduitSeeder::class,
            GrossisteSeeder::class,
            PrixGrossisteSeeder::class,
            StockBoutique1Seeder::class,
        ]);
    }
}
