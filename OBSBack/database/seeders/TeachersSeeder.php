<?php

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Seeder;

class TeachersSeeder extends Seeder
{
    public function run(): void
    {
        // Fuente: CSV proporcionado por el usuario (No., NOMBRE COMPLETO, CARGO, OBSERVACIONES)
        $rows = [
            ['legacy_no' => 5,  'fullName' => 'Angie Rebecca Martínez Alemán',        'position' => 'Docente Prekinder',                 'observations' => null],
            ['legacy_no' => 6,  'fullName' => 'Paula Gelsomina Maradiaga Valladares', 'position' => 'Docente de Kinder',                 'observations' => null],
            ['legacy_no' => 7,  'fullName' => 'Cristian Adalid Madrid Sandoval',      'position' => 'Docente 1er Grado',                 'observations' => null],
            ['legacy_no' => 8,  'fullName' => 'María José Prieto Antúnez',            'position' => 'Docente 2do Grado',                 'observations' => null],
            ['legacy_no' => 9,  'fullName' => 'Samuel Arturo Núñez Munguia',          'position' => 'Docente de 3er Grado',              'observations' => null],
            ['legacy_no' => 10, 'fullName' => 'Rina Fabiola Ruíz Solís',              'position' => 'Docente 4to Grado',                 'observations' => null],
            ['legacy_no' => 11, 'fullName' => 'Francis Elizabeth Sevilla Medina',    'position' => 'Docente 5to Grado',                 'observations' => null],
            ['legacy_no' => 12, 'fullName' => 'Silvia Sarahí Núñez Rosales',          'position' => 'Docente 6to Grado',                 'observations' => null],
            ['legacy_no' => 13, 'fullName' => 'María Anayansi Vallecillo Duarte',     'position' => 'Docente de 7mo Grado',              'observations' => null],
            ['legacy_no' => 14, 'fullName' => 'Dora Alejandra Herrera López',         'position' => 'Docente de 8vo Grado',              'observations' => null],
            ['legacy_no' => 15, 'fullName' => 'Lastenia Nicole Zelaya Lanza',         'position' => 'Docente 9no Grado',                 'observations' => null],
            ['legacy_no' => 16, 'fullName' => 'Daylis Ivanneth Hernández Fajardo',    'position' => 'Docente de 10mo Grado',             'observations' => null],
            ['legacy_no' => 17, 'fullName' => 'Martin Adarbin Orellana Tercero',      'position' => 'Docente de 11mo Grado',             'observations' => null],
            ['legacy_no' => 18, 'fullName' => 'Eliezer Ariel Vaquedano Cárcamo',      'position' => 'Docente de Arte',                   'observations' => null],
            ['legacy_no' => 19, 'fullName' => 'Yesika Nicolle Gamez Meyer',           'position' => 'Docente de Educ. Física',           'observations' => null],
            ['legacy_no' => 20, 'fullName' => 'Rafael Antonio Fajardo Núñez',         'position' => 'Docente de Informática',            'observations' => null],
            ['legacy_no' => 21, 'fullName' => 'Gerardo Joel Contreras Ruíz',          'position' => 'Docente de Español y CCSS',         'observations' => null],
            ['legacy_no' => 22, 'fullName' => 'Juan José Puerto Oyuela',              'position' => 'Docente de Música',                 'observations' => null],
            ['legacy_no' => 23, 'fullName' => 'Genesis Fernanda Arias Ortéz',         'position' => 'Maestra asistente de Kinder',       'observations' => null],
            ['legacy_no' => 24, 'fullName' => 'Brendy Yarely Jiménez Vega',           'position' => 'Maestra asistente de Prekinder',    'observations' => null],
            ['legacy_no' => 25, 'fullName' => 'Jenifer Alejandra Martínez Galeas',    'position' => 'Maestra asistente',                 'observations' => null],
        ];

        foreach ($rows as $r) {
            $legacyNo = $r['legacy_no'];

            // Email temporal único para cumplir con NOT NULL + UNIQUE
            $email = "teacher{$legacyNo}@obs.local";

            // Campos “placeholder” para adaptar a tu CRUD actual (puedes editarlos luego)
            // - identityNumber es requerido en el formulario/controlador y debe ser único
            // - birthDate / entryDate son requeridos en el formulario/controlador
            $identityNumber = "PEND-{$legacyNo}";
            $birthDate = '2000-01-01';
            $entryDate = now()->toDateString();

            Teacher::updateOrCreate(
                ['legacy_no' => $legacyNo],
                [
                    'fullName' => $r['fullName'],
                    'email' => $email,
                    'position' => $r['position'],
                    'observations' => $r['observations'],
                    'identityNumber' => $identityNumber,
                    'birthDate' => $birthDate,
                    'entryDate' => $entryDate,
                    'exitDate' => null,
                    'phone' => null,
                    'specialty' => null,
                    'status' => 'Activo',
                ]
            );
        }
    }
}

