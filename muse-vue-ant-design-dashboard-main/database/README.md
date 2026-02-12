# OBS School Management System - Database Migrations

## 📋 Migraciones de Base de Datos

Este directorio contiene todas las migraciones necesarias para configurar la base de datos del sistema escolar OBS.

### 🗂️ Estructura de Migraciones

Cada archivo de migración sigue la convención: `XXX_descripcion_tabla.sql`

### 📅 Lista de Migraciones

1. **001_create_subjects_table.sql** - Crear tabla de asignaturas
2. **002_create_study_plans_table.sql** - Crear tabla de planes de estudio
3. **003_create_academic_assignments_table.sql** - Crear tabla de asignaciones académicas
4. **004_create_class_schedules_table.sql** - Crear tabla de horarios de clases
5. **005_create_communications_table.sql** - Crear tabla de comunicados
6. **006_create_users_table.sql** - Crear tabla de usuarios y roles
7. **007_create_students_table.sql** - Crear tabla de estudiantes
8. **008_create_remaining_tables.sql** - Crear tablas restantes (docentes, grados, reportes, etc.)
9. **009_create_diary_pedagogico_table.sql** - Crear tabla de diario pedagógico
10. **010_create_attendance_table.sql** - Crear tabla de control de asistencia
11. **011_create_sales_and_inventory_tables.sql** - Crear tablas de ventas e inventario

### 🔧 Ejecución de Migraciones

Para ejecutar las migraciones en orden:

```bash
# Usando psql (PostgreSQL)
psql -h localhost -U obs_user -d obs_production -f 001_create_subjects_table.sql
psql -h localhost -U obs_user -d obs_production -f 002_create_study_plans_table.sql
psql -h localhost -U obs_user -d obs_production -f 003_create_academic_assignments_table.sql
psql -h localhost -U obs_user -d obs_production -f 004_create_class_schedules_table.sql
psql -h localhost -U obs_user -d obs_production -f 005_create_communications_table.sql
psql -h localhost -U obs_user -d obs_production -f 006_create_users_table.sql
psql -h localhost -U obs_user -d obs_production -f 007_create_students_table.sql
psql -h localhost -U obs_user -d obs_production -f 008_create_remaining_tables.sql
psql -h localhost -U obs_user -d obs_production -f 009_create_diary_pedagogico_table.sql
psql -h localhost -U obs_user -d obs_production -f 010_create_attendance_table.sql
psql -h localhost -U obs_user -d obs_production -f 011_create_sales_and_inventory_tables.sql
```

### 🗄️ Tablas Creadas

#### 📚 Planificación
- **subjects**: Catálogo de asignaturas con niveles y créditos
- **study_plans**: Planes de estudio con objetivos y competencias
- **academic_assignments**: Asignación de docentes a grados y asignaturas
- **class_schedules**: Horarios de clases con detección de conflictos
- **diary_pedagogico**: Registro diario de actividades y observaciones pedagógicas
- **attendance**: Control de asistencia de estudiantes

#### � Ventas e Inventario
- **inventory_products**: Catálogo de productos del inventario
- **sales**: Registro de ventas realizadas
- **sale_items**: Detalles de productos vendidos en cada venta
- **inventory_movements**: Movimientos de stock (entradas, salidas, ajustes)
- **inventory_adjustments**: Ajustes de inventario por discrepancias
- **inventory_adjustment_items**: Detalles de los ajustes de inventario

#### �📋 Comunicados
- **communications**: Comunicados masivos con múltiples canales
- **communication_recipients**: Seguimiento de entrega de comunicados
- **communication_attachments**: Gestión de archivos adjuntos

#### 👥 Usuarios y Seguridad
- **users**: Usuarios con roles y permisos basados en JSON
- **roles**: Definición de roles con permisos granulares
- **user_roles**: Relación muchos-a-muchos usuarios-roles
- **screens**: Pantallas del sistema con control de acceso
- **role_permissions**: Permisos específicos por rol y pantalla
- **user_sessions**: Gestión de sesiones activas
- **password_reset_requests**: Flujo de restablecimiento de contraseña
- **notifications**: Sistema de notificaciones internas

#### 📚 Registro Académico
- **students**: Información completa de estudiantes
- **academic_records**: Historial académico y calificaciones
- **student_documents**: Gestión documental de estudiantes
- **student_attendance**: Control de asistencia detallada

#### 🏫 Sistema Completo
- **teachers**: Información docente con especializaciones
- **grades**: Configuración de grados y secciones
- **positions**: Cargos y departamentos
- **bug_reports**: Sistema de reporte de errores
- **audit_logs**: Auditoría completa de cambios

### 🔐 Características de Seguridad

- **Autenticación JWT**: Tokens firmados con expiración configurable
- **Control de Acceso**: Basado en roles y permisos granulares
- **Validación de Entrada**: Sanitización y validación completa
- **Auditoría Completa**: Registro de todas las acciones importantes
- **Restablecimiento Seguro**: Flujo seguro con tokens de un solo uso
- **Sesiones Múltiples**: Soporte para múltiples dispositivos por usuario

### 📊 Optimizaciones de Rendimiento

- **Índices Estratégicos**: Optimizados para consultas frecuentes
- **Vistas Materializadas**: Para consultas complejas y reporting
- **Caching**: Tablas auxiliares para datos calculados
- **Consultas Parametrizadas**: Prevención de inyección SQL

### 🔧 Configuración para Producción

Las migraciones incluyen:

- **Restricciones CHECK**: Validación de datos a nivel de base de datos
- **Valores por Defecto**: Configuración automática de campos comunes
- **Relaciones FK**: Integridad referencial completa
- **Timestamps**: Control automático de created_at/updated_at
- **Comentarios**: Documentación detallada de cada tabla y columna

### 🚀 Despliegue en Producción

1. **Verificar Conexión**: Asegurar conexión a base de datos PostgreSQL
2. **Ejecutar en Orden**: Las migraciones deben ejecutarse secuencialmente
3. **Verificar Datos**: Confirmar que los datos iniciales se cargaron correctamente
4. **Crear Índices**: Los índices se crean automáticamente en las migraciones
5. **Testing**: Realizar pruebas de integración con todas las tablas

### 📝 Notas Importantes

- **Versionado**: Cada migración está versionada y documentada
- **Rollback**: Mantener scripts de rollback para cada migración
- **Backup**: Realizar backup completo antes de migraciones en producción
- **Monitoreo**: Configurar logging adecuado para detectar problemas temprano

---

**Para ejecutar todas las migraciones:**

```bash
# Script completo de migración
#!/bin/bash
echo "Iniciando migraciones de OBS School Management System..."

# Conexión a base de datos
DB_HOST="localhost"
DB_PORT="5432"
DB_NAME="obs_production"
DB_USER="obs_user"

# Lista de migraciones en orden
MIGRATIONS=(
    "001_create_subjects_table.sql"
    "002_create_study_plans_table.sql"
    "003_create_academic_assignments_table.sql"
    "004_create_class_schedules_table.sql"
    "005_create_communications_table.sql"
    "006_create_users_table.sql"
    "007_create_students_table.sql"
    "008_create_remaining_tables.sql"
)

# Ejecutar cada migración
for migration in "${MIGRATIONS[@]}"; do
    echo "Ejecutando $migration..."
    psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -f "$migration"
    if [ $? -eq 0 ]; then
        echo "✅ $migration ejecutada correctamente"
    else
        echo "❌ Error en $migration"
        exit 1
    fi
done

echo "🎉 Migraciones completadas exitosamente!"
```

---

**📋 Documentación API**: Ver `API_ENDPOINTS.md` para todos los endpoints correspondientes

**🔧 Variables de Entorno**: Configurar variables de entorno según `API_ENDPOINTS.md`

**📊 Monitoreo**: Implementar logging y métricas para producción

---
