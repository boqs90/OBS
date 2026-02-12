<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            // Preescolar
            ['name' => 'Prekínder', 'description' => 'Preescolar', 'status' => 'Activo'],
            ['name' => 'Kínder', 'description' => 'Preescolar', 'status' => 'Activo'],

            // Primaria
            ['name' => '1ro Primaria', 'description' => 'Primaria', 'status' => 'Activo'],
            ['name' => '2do Primaria', 'description' => 'Primaria', 'status' => 'Activo'],
            ['name' => '3ro Primaria', 'description' => 'Primaria', 'status' => 'Activo'],
            ['name' => '4to Primaria', 'description' => 'Primaria', 'status' => 'Activo'],
            ['name' => '5to Primaria', 'description' => 'Primaria', 'status' => 'Activo'],
            ['name' => '6to Primaria', 'description' => 'Primaria', 'status' => 'Activo'],

            // Básica / Middle School
            ['name' => '7mo', 'description' => 'Básica', 'status' => 'Activo'],
            ['name' => '8vo', 'description' => 'Básica', 'status' => 'Activo'],
            ['name' => '9no', 'description' => 'Básica', 'status' => 'Activo'],

            // High School
            ['name' => '10mo (High School)', 'description' => 'High School', 'status' => 'Activo'],
            ['name' => '11vo (High School)', 'description' => 'High School', 'status' => 'Activo'],
            ['name' => '12vo (High School)', 'description' => 'High School', 'status' => 'Activo'],
        ];

        foreach ($grades as $g) {
            Grade::updateOrCreate(
                ['name' => $g['name']],
                ['description' => $g['description'], 'status' => $g['status']]
            );
        }
    }
}
