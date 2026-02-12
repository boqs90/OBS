<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Directora', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente Prekinder', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de Kinder', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente 1er Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente 2do Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de 3er Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente 4to Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente 5to Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente 6to Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de 7mo Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de 8vo Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente 9no Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de 10mo Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de 11mo Grado', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de Arte', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de Educ. Física', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de Informática', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de Español y CCSS', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Docente de Música', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Maestra asistente de Kinder', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Maestra asistente de Prekinder', 'description' => null, 'status' => 'Activo'],
            ['name' => 'Maestra asistente', 'description' => null, 'status' => 'Activo'],
        ];

        foreach ($positions as $p) {
            Position::updateOrCreate(
                ['name' => $p['name']],
                ['description' => $p['description'], 'status' => $p['status'], 'is_system' => true]
            );
        }
    }
}

