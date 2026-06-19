<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // admin
        User::updateOrCreate(
            ['nom_utilisateur' => 'admin'],
            [
                'email' => 'dd9159360@gmail.com',
                'password' => bcrypt('admin2018'),
                'role' => 'super_admin',
            ]
        );
    }
}
