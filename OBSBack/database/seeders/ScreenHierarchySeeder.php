<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreenHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        // Estructura jerárquica: Sección → Categoría → Enlaces
        
        // 1. Crear secciones principales (Nivel 1)
        $sections = [
            ['key' => 'dashboard_section', 'label' => 'Dashboard', 'link_type' => 'section', 'sort_order' => 10],
            ['key' => 'academic_section', 'label' => 'Gestión Académica', 'link_type' => 'section', 'sort_order' => 20],
            ['key' => 'admin_section', 'label' => 'Administración', 'link_type' => 'section', 'sort_order' => 30],
            ['key' => 'reports_section', 'label' => 'Reportes', 'link_type' => 'section', 'sort_order' => 40],
        ];

        foreach ($sections as $section) {
            DB::table('screens')->updateOrInsert(
                ['key' => $section['key']],
                array_merge($section, [
                    'section' => $section['label'],
                    'category' => null,
                    'parent_id' => null,
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // 2. Crear categorías (Nivel 2)
        $categories = [
            // Categorías de Gestión Académica
            ['key' => 'registration_category', 'label' => 'Registro', 'section' => 'Gestión Académica', 'parent_key' => 'academic_section', 'sort_order' => 210],
            ['key' => 'billing_category', 'label' => 'Pagos', 'section' => 'Gestión Académica', 'parent_key' => 'academic_section', 'sort_order' => 220],
            
            // Categorías de Administración
            ['key' => 'user_admin_category', 'label' => 'Usuarios', 'section' => 'Administración', 'parent_key' => 'admin_section', 'sort_order' => 310],
            ['key' => 'system_category', 'label' => 'Sistema', 'section' => 'Administración', 'parent_key' => 'admin_section', 'sort_order' => 320],
        ];

        foreach ($categories as $category) {
            $parentId = DB::table('screens')->where('key', $category['parent_key'])->value('id');
            
            DB::table('screens')->updateOrInsert(
                ['key' => $category['key']],
                [
                    'label' => $category['label'],
                    'section' => $category['section'],
                    'category' => $category['label'],
                    'link_type' => 'category',
                    'parent_id' => $parentId,
                    'sort_order' => $category['sort_order'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 3. Actualizar enlaces existentes (Nivel 3)
        $links = [
            // Dashboard
            ['key' => '/dashboard', 'label' => 'Resumen', 'section' => 'Dashboard', 'parent_key' => 'dashboard_section', 'sort_order' => 11],
            
            // Gestión Académica - Registro
            ['key' => '/matricula', 'label' => 'Matrícula', 'section' => 'Gestión Académica', 'category' => 'Registro', 'parent_key' => 'registration_category', 'sort_order' => 211],
            ['key' => '/registro/alumnos', 'label' => 'Registro Alumnos', 'section' => 'Gestión Académica', 'category' => 'Registro', 'parent_key' => 'registration_category', 'sort_order' => 212],
            ['key' => '/registro/maestros', 'label' => 'Registro Maestros', 'section' => 'Gestión Académica', 'category' => 'Registro', 'parent_key' => 'registration_category', 'sort_order' => 213],
            ['key' => '/registro/empleados', 'label' => 'Registro Empleados', 'section' => 'Gestión Académica', 'category' => 'Registro', 'parent_key' => 'registration_category', 'sort_order' => 214],
            ['key' => '/registro/cargos', 'label' => 'Cargos', 'section' => 'Gestión Académica', 'category' => 'Registro', 'parent_key' => 'registration_category', 'sort_order' => 215],
            ['key' => '/registro/grados', 'label' => 'Grados', 'section' => 'Gestión Académica', 'category' => 'Registro', 'parent_key' => 'registration_category', 'sort_order' => 216],
            
            // Gestión Académica - Pagos
            ['key' => '/billing', 'label' => 'Pagos', 'section' => 'Gestión Académica', 'category' => 'Pagos', 'parent_key' => 'billing_category', 'sort_order' => 221],
            
            // Administración - Usuarios
            ['key' => '/notificaciones', 'label' => 'Notificaciones', 'section' => 'Administración', 'category' => 'Usuarios', 'parent_key' => 'user_admin_category', 'sort_order' => 311],
            ['key' => '/sesiones', 'label' => 'Sesiones', 'section' => 'Administración', 'category' => 'Usuarios', 'parent_key' => 'user_admin_category', 'sort_order' => 312],
            ['key' => '/tables', 'label' => 'Administrar usuarios', 'section' => 'Administración', 'category' => 'Usuarios', 'parent_key' => 'user_admin_category', 'sort_order' => 313],
            ['key' => '/roles', 'label' => 'Administrar roles', 'section' => 'Administración', 'category' => 'Usuarios', 'parent_key' => 'user_admin_category', 'sort_order' => 314],
            
            // Administración - Sistema
            ['key' => '/profile', 'label' => 'Perfiles', 'section' => 'Administración', 'category' => 'Sistema', 'parent_key' => 'system_category', 'sort_order' => 321],
            
            // Reportes
            ['key' => '/rtl', 'label' => 'Reportes', 'section' => 'Reportes', 'parent_key' => 'reports_section', 'sort_order' => 41],
        ];

        foreach ($links as $link) {
            $parentId = DB::table('screens')->where('key', $link['parent_key'])->value('id');
            
            DB::table('screens')->updateOrInsert(
                ['key' => $link['key']],
                [
                    'label' => $link['label'],
                    'section' => $link['section'],
                    'category' => $link['category'] ?? null,
                    'link_type' => 'link',
                    'parent_id' => $parentId,
                    'sort_order' => $link['sort_order'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
