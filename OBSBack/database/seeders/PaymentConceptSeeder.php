<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentConcept;

class PaymentConceptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $concepts = [
            [
                'name' => 'Matrícula',
                'description' => 'Pago anual de matrícula escolar',
                'amount' => 500.00,
                'type' => 'one_time',
                'status' => 'active'
            ],
            [
                'name' => 'Mensualidad',
                'description' => 'Pago mensual de colegiatura',
                'amount' => 200.00,
                'type' => 'monthly',
                'status' => 'active'
            ],
            [
                'name' => 'Material Escolar',
                'description' => 'Cuota para material y libros',
                'amount' => 150.00,
                'type' => 'one_time',
                'status' => 'active'
            ],
            [
                'name' => 'Actividades Extraescolares',
                'description' => 'Cuota para deportes y actividades culturales',
                'amount' => 50.00,
                'type' => 'optional',
                'status' => 'active'
            ],
            [
                'name' => 'Tecnología',
                'description' => 'Cuota para uso de laboratorio de computación',
                'amount' => 30.00,
                'type' => 'monthly',
                'status' => 'active'
            ]
        ];

        foreach ($concepts as $concept) {
            PaymentConcept::create($concept);
        }
    }
}
