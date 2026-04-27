<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Institution;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Solo crea si no existe
        $institution = Institution::firstOrCreate(
            ['code' => '36006'],
            [
                'name'          => 'IE 36006 Huancavelica',
                'ugel'          => 'UGEL Huancavelica',
                'district'      => 'Huancavelica',
                'province'      => 'Huancavelica',
                'region'        => 'Huancavelica',
                'level'         => 'secundaria',
                'director_name' => 'Director de Prueba',
            ]
        );

        // Solo crea si no existe
        User::firstOrCreate(
            ['email' => 'admin@siagie.test'],
            [
                'name'           => 'Administrador',
                'password'       => Hash::make('password'),
                'institution_id' => $institution->id,
            ]
        );
    }
}