<?php

namespace Database\Seeders;

use App\Models\Grossiste;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GrossisteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grossistes = [
            // Exemple 1 - À remplacer avec vos données
            [
                'nom' => 'Grossiste Sud Moto',
                'code' => 'GROSS-001',
                'contact' => '0781234567',
            ],
            // Exemple 2 - À remplacer avec vos données
            [
                'nom' => 'Grossiste Dakar Auto',
                'code' => 'GROSS-002',
                'contact' => '0782345678',
            ],
            // ========================================
            // AJOUTEZ VOS GROSSISTES CI-DESSOUS
            // ========================================
            // Modèle à copier/coller :
            // [
            //     'nom' => 'Nom du grossiste',
            //     'code' => 'GROSS-XXX',
            //     'contact' => 'Téléphone ou email',
            // ],
        ];

        foreach ($grossistes as $grossiste) {
            Grossiste::firstOrCreate(
                ['code' => $grossiste['code']],
                $grossiste
            );
        }
    }
}
