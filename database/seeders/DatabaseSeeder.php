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
        // Crear institución de prueba
        $institution = Institution::create([
            'name'          => 'IE 36006 Huancavelica',
            'code'          => '36006',
            'ugel'          => 'UGEL Huancavelica',
            'district'      => 'Huancavelica',
            'province'      => 'Huancavelica',
            'region'        => 'Huancavelica',
            'level'         => 'secundaria',
            'director_name' => 'Director de Prueba',
        ]);

        // Crear usuario administrador
        User::create([
            'name'           => 'Administrador',
            'email'          => 'admin@siagie.test',
            'password'       => Hash::make('password'),
            'institution_id' => $institution->id,
        ]);
    }
}