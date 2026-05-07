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

        // Admin (tú)
        User::firstOrCreate(
            ['email' => 'admin@quipubit.com'],
            [
                'name'     => 'Jean Admin',
                'password' => Hash::make('jcoa2026'),
                'role'     => 'admin',
                'institution_id' => null,
            ]
        );

        // Director
        User::firstOrCreate(
            ['email' => 'director@siagie.test'],
            [
                'name'           => 'Director',
                'password'       => Hash::make('password'),
                'institution_id' => $institution->id,
                'role'           => 'director',
            ]
        );

        // Docente
        User::firstOrCreate(
            ['email' => 'docente@siagie.test'],
            [
                'name'           => 'Docente',
                'password'       => Hash::make('password'),
                'institution_id' => $institution->id,
                'role'           => 'docente',
            ]
        );
    }
}