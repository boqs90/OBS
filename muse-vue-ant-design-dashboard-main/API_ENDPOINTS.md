# API Endpoints - OBS School Management System

## 📚 Planificación

### Plan de Estudios
- `GET /api/study-plans` - Listar todos los planes de estudio
- `POST /api/study-plans` - Crear nuevo plan de estudio
- `GET /api/study-plans/{id}` - Obtener plan específico
- `PUT /api/study-plans/{id}` - Actualizar plan existente
- `DELETE /api/study-plans/{id}` - Eliminar plan
- `PUT /api/study-plans/{id}/activate` - Activar plan

### Carga Académica
- `GET /api/academic-assignments` - Listar asignaciones académicas
- `POST /api/academic-assignments` - Crear nueva asignación
- `GET /api/academic-assignments/{id}` - Obtener asignación específica
- `PUT /api/academic-assignments/{id}` - Actualizar asignación existente
- `DELETE /api/academic-assignments/{id}` - Eliminar asignación
- `GET /api/teachers` - Listar docentes (para selección)
- `GET /api/subjects` - Listar asignaturas (para selección)

### Horarios de Clases
- `GET /api/class-schedules` - Listar horarios de clases
- `POST /api/class-schedules` - Crear nuevo horario
- `GET /api/class-schedules/{id}` - Obtener horario específico
- `PUT /api/class-schedules/{id}` - Actualizar horario existente
- `DELETE /api/class-schedules/{id}` - Eliminar horario
- `GET /api/class-schedules/week/{grade}` - Obtener horario semanal por grado
- `GET /api/teachers` - Listar docentes (para selección)
- `GET /api/subjects` - Listar asignaturas (para selección)

## 📋 Comunicados

### Comunicados Masivos
- `GET /api/communications` - Listar todos los comunicados
- `POST /api/communications` - Crear nuevo comunicado
- `GET /api/communications/{id}` - Obtener comunicado específico
- `PUT /api/communications/{id}` - Actualizar comunicado existente
- `DELETE /api/communications/{id}` - Eliminar comunicado
- `POST /api/communications/{id}/send` - Enviar comunicado inmediatamente
- `GET /api/communications/recipients/{type}` - Obtener lista de destinatarios por tipo

### Destinatarios Personalizados
- `POST /api/communications/send-custom` - Enviar a destinatarios personalizados
- `GET /api/communications/contacts/parents` - Obtener contactos de padres
- `GET /api/communications/contacts/teachers` - Obtener contactos de docentes
- `GET /api/communications/contacts/employees` - Obtener contactos de empleados

## 📚 Registro

### Asignaturas
- `GET /api/subjects` - Listar todas las asignaturas
- `POST /api/subjects` - Crear nueva asignatura
- `GET /api/subjects/{id}` - Obtener asignatura específica
- `PUT /api/subjects/{id}` - Actualizar asignatura existente
- `DELETE /api/subjects/{id}` - Eliminar asignatura
- `GET /api/subjects/by-level/{level}` - Obtener asignaturas por nivel educativo

## 📊 Reportes

### Reporte de Incidencias
- `GET /api/incidences` - Listar todas las incidencias
- `POST /api/incidences` - Crear nueva incidencia
- `GET /api/incidences/{id}` - Obtener incidencia específica
- `PUT /api/incidences/{id}` - Actualizar incidencia existente
- `DELETE /api/incidences/{id}` - Eliminar incidencia
- `GET /api/incidences/statistics` - Obtener estadísticas de incidencias

### Reportes de Asistencia
- `GET /api/reports/attendance` - Listar reportes de asistencia
- `POST /api/reports/attendance` - Generar nuevo reporte de asistencia
- `GET /api/reports/attendance/{id}` - Obtener reporte específico
- `GET /api/reports/attendance/by-grade/{grade}` - Reportes por grado
- `GET /api/reports/attendance/by-date/{date}` - Reportes por fecha

### Reportes Académicos
- `GET /api/reports/academic` - Listar reportes académicos
- `POST /api/reports/academic` - Generar nuevo reporte académico
- `GET /api/reports/academic/{id}` - Obtener reporte específico
- `GET /api/reports/academic/performance/{studentId}` - Rendimiento por estudiante
- `GET /api/reports/academic/performance/{gradeId}` - Rendimiento por grado

## 👥 Usuarios y Autenticación

### Gestión de Usuarios
- `GET /api/users` - Listar todos los usuarios
- `POST /api/users` - Crear nuevo usuario
- `GET /api/users/{id}` - Obtener usuario específico
- `PUT /api/users/{id}` - Actualizar usuario existente
- `DELETE /api/users/{id}` - Eliminar usuario
- `PUT /api/users/{id}/status` - Cambiar estado de usuario
- `PUT /api/users/{id}/role` - Cambiar rol de usuario

### Roles y Permisos
- `GET /api/roles` - Listar todos los roles
- `POST /api/roles` - Crear nuevo rol
- `GET /api/roles/{id}` - Obtener rol específico
- `PUT /api/roles/{id}` - Actualizar rol existente
- `DELETE /api/roles/{id}` - Eliminar rol
- `GET /api/roles/{id}/permissions` - Obtener permisos del rol
- `POST /api/roles/{id}/permissions` - Asignar permisos a rol

### Pantallas y Permisos
- `GET /api/me/screens` - Obtener pantallas del usuario actual
- `GET /api/screens` - Listar todas las pantallas disponibles
- `GET /api/roles/{roleId}/screens` - Obtener pantallas por rol

### Sesiones
- `GET /api/sessions` - Listar sesiones activas
- `POST /api/sessions/{id}/revoke` - Revocar sesión específica
- `POST /api/sessions/revoke-all` - Revocar todas las sesiones excepto la actual
- `GET /api/sessions/user/{userId}` - Sesiones por usuario

### Restablecimiento de Contraseñas
- `POST /api/password-reset/request` - Solicitar restablecimiento
- `GET /api/password-reset/requests` - Listar solicitudes pendientes
- `PUT /api/password-reset/approve/{id}` - Aprobar solicitud
- `DELETE /api/password-reset/deny/{id}` - Rechazar solicitud
- `POST /api/password-reset/confirm` - Confirmar restablecimiento con token

## 💰 Pagos

### Gestión de Pagos
- `GET /api/payments` - Listar todos los pagos
- `POST /api/payments` - Registrar nuevo pago
- `GET /api/payments/{id}` - Obtener pago específico
- `PUT /api/payments/{id}` - Actualizar pago existente
- `DELETE /api/payments/{id}` - Eliminar pago
- `GET /api/payments/student/{studentId}` - Pagos por estudiante
- `GET /api/payments/by-date/{date}` - Pagos por fecha

### Matrículas
- `GET /api/enrollments` - Listar matrículas
- `POST /api/enrollments` - Crear nueva matrícula
- `GET /api/enrollments/{id}` - Obtener matrícula específica
- `PUT /api/enrollments/{id}` - Actualizar matrícula existente
- `DELETE /api/enrollments/{id}` - Eliminar matrícula
- `GET /api/enrollments/by-grade/{grade}` - Matrículas por grado
- `GET /api/enrollments/by-status/{status}` - Matrículas por estado

### Planilla y Contabilidad
- `GET /api/payroll` - Listar planillas
- `POST /api/payroll` - Generar nueva planilla
- `GET /api/accounting` - Listar registros contables
- `POST /api/accounting` - Crear registro contable
- `GET /api/accounting/balance` - Obtener balance general
- `GET /api/accounting/reports` - Generar reportes contables

## 📦 Inventario

### Control de Inventario
- `GET /api/inventory` - Listar todos los items del inventario
- `POST /api/inventory` - Agregar nuevo item
- `GET /api/inventory/{id}` - Obtener item específico
- `PUT /api/inventory/{id}` - Actualizar item existente
- `DELETE /api/inventory/{id}` - Eliminar item
- `GET /api/inventory/by-category/{category}` - Items por categoría
- `GET /api/inventory/low-stock` - Items con stock bajo
- `POST /api/inventory/{id}/adjust-stock` - Ajustar stock

### Categorías y Proveedores
- `GET /api/inventory/categories` - Listar categorías
- `POST /api/inventory/categories` - Crear nueva categoría
- `GET /api/inventory/suppliers` - Listar proveedores
- `POST /api/inventory/suppliers` - Agregar nuevo proveedor

## 📚 Registro Académico

### Estudiantes
- `GET /api/students` - Listar todos los estudiantes
- `POST /api/students` - Crear nuevo estudiante
- `GET /api/students/{id}` - Obtener estudiante específico
- `PUT /api/students/{id}` - Actualizar estudiante existente
- `DELETE /api/students/{id}` - Eliminar estudiante
- `GET /api/students/by-grade/{grade}` - Estudiantes por grado
- `GET /api/students/by-status/{status}` - Estudiantes por estado
- `POST /api/students/{id}/promote` - Promover estudiante de grado

### Docentes
- `GET /api/teachers` - Listar todos los docentes
- `POST /api/teachers` - Crear nuevo docente
- `GET /api/teachers/{id}` - Obtener docente específico
- `PUT /api/teachers/{id}` - Actualizar docente existente
- `DELETE /api/teachers/{id}` - Eliminar docente
- `GET /api/teachers/by-subject/{subjectId}` - Docentes por asignatura
- `GET /api/teachers/by-grade/{grade}` - Docentes por grado

### Empleados
- `GET /api/employees` - Listar todos los empleados
- `POST /api/employees` - Crear nuevo empleado
- `GET /api/employees/{id}` - Obtener empleado específico
- `PUT /api/employees/{id}` - Actualizar empleado existente
- `DELETE /api/employees/{id}` - Eliminar empleado
- `GET /api/employees/by-department/{department}` - Empleados por departamento

### Grados y Secciones
- `GET /api/grades` - Listar todos los grados
- `POST /api/grades` - Crear nuevo grado
- `GET /api/grades/{id}` - Obtener grado específico
- `PUT /api/grades/{id}` - Actualizar grado existente
- `DELETE /api/grades/{id}` - Eliminar grado
- `GET /api/grades/sections/{gradeId}` - Secciones por grado

### Cargos
- `GET /api/positions` - Listar todos los cargos
- `POST /api/positions` - Crear nuevo cargo
- `GET /api/positions/{id}` - Obtener cargo específico
- `PUT /api/positions/{id}` - Actualizar cargo existente
- `DELETE /api/positions/{id}` - Eliminar cargo

## 🐛 Reporte de Errores y Mejoras

### Gestión de Reportes
- `GET /api/bug-reports` - Listar todos los reportes de errores
- `POST /api/bug-reports` - Crear nuevo reporte
- `GET /api/bug-reports/{id}` - Obtener reporte específico
- `PUT /api/bug-reports/{id}` - Actualizar estado de reporte
- `DELETE /api/bug-reports/{id}` - Eliminar reporte
- `POST /api/bug-reports/{id}/resolve` - Marcar como resuelto
- `GET /api/bug-reports/statistics` - Estadísticas de reportes

### Categorías y Prioridades
- `GET /api/bug-reports/categories` - Listar categorías de errores
- `POST /api/bug-reports/categories` - Crear nueva categoría
- `GET /api/bug-reports/priorities` - Listar niveles de prioridad

## 📊 Estadísticas y Dashboard

### Estadísticas Generales
- `GET /api/statistics/overview` - Estadísticas generales del sistema
- `GET /api/statistics/enrollment` - Estadísticas de matrícula
- `GET /api/statistics/attendance` - Estadísticas de asistencia
- `GET /api/statistics/academic` - Estadísticas académicas
- `GET /api/statistics/financial` - Estadísticas financieras

### Dashboard Personalizado
- `GET /api/dashboard/user/{userId}` - Dashboard personalizado por usuario
- `GET /api/dashboard/role/{roleId}` - Dashboard por rol
- `POST /api/dashboard/widgets` - Configurar widgets del dashboard

## 🔔 Notificaciones

### Gestión de Notificaciones
- `GET /api/notifications` - Listar notificaciones del usuario
- `POST /api/notifications` - Crear nueva notificación
- `PUT /api/notifications/{id}/read` - Marcar como leída
- `PUT /api/notifications/{id}/unread` - Marcar como no leída
- `DELETE /api/notifications/{id}` - Eliminar notificación
- `PUT /api/notifications/read-all` - Marcar todas como leídas
- `DELETE /api/notifications/delete-all` - Eliminar todas las notificaciones

### Configuración de Notificaciones
- `GET /api/notifications/settings` - Obtener configuración de notificaciones
- `PUT /api/notifications/settings` - Actualizar configuración
- `POST /api/notifications/send-bulk` - Enviar notificaciones masivas

## 📱 Integraciones Externas

### WhatsApp Business API
- `POST /api/whatsapp/send` - Enviar mensaje WhatsApp
- `GET /api/whatsapp/status/{messageId}` - Verificar estado de envío
- `GET /api/whatsapp/templates` - Listar plantillas de WhatsApp
- `POST /api/whatsapp/templates` - Crear nueva plantilla

### Email Service
- `POST /api/email/send` - Enviar correo electrónico
- `GET /api/email/status/{messageId}` - Verificar estado de envío
- `GET /api/email/templates` - Listar plantillas de correo
- `POST /api/email/templates` - Crear nueva plantilla

### SMS Service
- `POST /api/sms/send` - Enviar mensaje SMS
- `GET /api/sms/status/{messageId}` - Verificar estado de envío
- `GET /api/sms/balance` - Consultar saldo de SMS

## 🔒 Seguridad

### Autenticación
- `POST /api/auth/login` - Inicio de sesión
- `POST /api/auth/logout` - Cierre de sesión
- `POST /api/auth/refresh` - Refrescar token
- `POST /api/auth/forgot-password` - Olvidé contraseña
- `POST /api/auth/reset-password` - Restablecer contraseña
- `GET /api/auth/verify-token/{token}` - Verificar token de restablecimiento

### Seguridad de Cuenta
- `GET /api/security/activity/{userId}` - Historial de actividad
- `POST /api/security/enable-2fa` - Activar autenticación de dos factores
- `PUT /api/security/disable-2fa` - Desactivar autenticación de dos factores
- `GET /api/security/sessions/{userId}` - Sesiones activas del usuario
- `POST /api/security/revoke-session/{sessionId}` - Revocar sesión específica

## 📁 Archivos y Documentos

### Gestión de Documentos
- `GET /api/documents` - Listar documentos
- `POST /api/documents` - Subir nuevo documento
- `GET /api/documents/{id}` - Obtener documento específico
- `PUT /api/documents/{id}` - Actualizar documento existente
- `DELETE /api/documents/{id}` - Eliminar documento
- `GET /api/documents/by-category/{category}` - Documentos por categoría
- `GET /api/documents/search` - Buscar documentos

### Documentos Académicos
- `GET /api/documents/students/{studentId}` - Documentos por estudiante
- `GET /api/documents/subjects/{subjectId}` - Documentos por asignatura
- `POST /api/documents/upload-bulk` - Subir múltiples documentos

## 🏫 Importación/Exportación

### Importación de Datos
- `POST /api/import/students` - Importar estudiantes
- `POST /api/import/teachers` - Importar docentes
- `POST /api/import/subjects` - Importar asignaturas
- `POST /api/import/enrollments` - Importar matrículas
- `GET /api/import/templates` - Obtener plantillas de importación

### Exportación de Datos
- `GET /api/export/students` - Exportar estudiantes
- `GET /api/export/teachers` - Exportar docentes
- `GET /api/export/academic-records` - Exportar registros académicos
- `GET /api/export/attendance` - Exportar asistencia
- `GET /api/export/financial` - Exportar datos financieros
- `POST /api/export/custom` - Exportación personalizada

## 🎯 Configuración del Sistema

### Configuración General
- `GET /api/settings` - Obtener configuración general
- `PUT /api/settings` - Actualizar configuración general
- `GET /api/settings/school` - Configuración de la escuela
- `GET /api/settings/system` - Configuración del sistema

### Configuración Académica
- `GET /api/settings/academic` - Configuración académica
- `PUT /api/settings/academic` - Actualizar configuración académica
- `GET /api/settings/grading` - Configuración de calificación
- `GET /api/settings/attendance` - Configuración de asistencia

### Configuración de Notificaciones
- `GET /api/settings/notifications` - Configuración de notificaciones
- `PUT /api/settings/notifications` - Actualizar configuración de notificaciones
- `GET /api/settings/email` - Configuración de correo
- `GET /api/settings/sms` - Configuración de SMS

## 🔄 Tareas Programadas

### Gestión de Tareas
- `GET /api/scheduled-tasks` - Listar tareas programadas
- `POST /api/scheduled-tasks` - Crear nueva tarea programada
- `GET /api/scheduled-tasks/{id}` - Obtener tarea específica
- `PUT /api/scheduled-tasks/{id}` - Actualizar tarea existente
- `DELETE /api/scheduled-tasks/{id}` - Eliminar tarea programada
- `POST /api/scheduled-tasks/{id}/execute` - Ejecutar tarea manualmente

### Tareas Automáticas
- `GET /api/automated-tasks` - Listar tareas automáticas
- `POST /api/automated-tasks` - Crear nueva tarea automática
- `PUT /api/automated-tasks/{id}/toggle` - Activar/desactivar tarea
- `GET /api/automated-tasks/logs/{id}` - Logs de ejecución

## 📊 Reportes Avanzados

### Reportes Personalizados
- `POST /api/reports/custom` - Generar reporte personalizado
- `GET /api/reports/templates` - Listar plantillas de reportes
- `POST /api/reports/templates` - Crear nueva plantilla
- `GET /api/reports/schedule/{id}` - Programar generación de reporte

### Reportes Financieros
- `GET /api/reports/financial/balance` - Balance general
- `GET /api/reports/financial/income-expense` - Ingresos y egresos
- `GET /api/reports/financial/by-period` - Reportes por período
- `GET /api/reports/financial/by-category` - Reportes por categoría

## 🎓 Gestión de Cursos

### Cursos Online
- `GET /api/courses` - Listar cursos online
- `POST /api/courses` - Crear nuevo curso
- `GET /api/courses/{id}` - Obtener curso específico
- `PUT /api/courses/{id}` - Actualizar curso existente
- `DELETE /api/courses/{id}` - Eliminar curso
- `GET /api/courses/{id}/enrollments` - Estudiantes inscritos
- `POST /api/courses/{id}/enroll` - Inscribir estudiante

### Contenido de Cursos
- `GET /api/courses/{id}/modules` - Módulos del curso
- `POST /api/courses/{id}/modules` - Agregar módulo
- `GET /api/courses/{id}/assignments` - Tareas del curso
- `POST /api/courses/{id}/assignments` - Crear tarea
- `GET /api/courses/{id}/progress/{studentId}` - Progreso por estudiante

## 🏥‍♂️ Eventos y Actividades

### Gestión de Eventos
- `GET /api/events` - Listar eventos escolares
- `POST /api/events` - Crear nuevo evento
- `GET /api/events/{id}` - Obtener evento específico
- `PUT /api/events/{id}` - Actualizar evento existente
- `DELETE /api/events/{id}` - Eliminar evento
- `GET /api/events/calendar/{year}/{month}` - Eventos por mes/año

### Actividades Extracurriculares
- `GET /api/activities` - Listar actividades extracurriculares
- `POST /api/activities` - Crear nueva actividad
- `GET /api/activities/{id}` - Obtener actividad específica
- `PUT /api/activities/{id}` - Actualizar actividad existente
- `DELETE /api/activities/{id}` - Eliminar actividad
- `GET /api/activities/{id}/participants` - Participantes de actividad

## 📋 Auditoría y Logs

### Logs del Sistema
- `GET /api/logs/system` - Logs del sistema
- `GET /api/logs/security` - Logs de seguridad
- `GET /api/logs/api` - Logs de llamadas a la API
- `GET /api/logs/auth` - Logs de autenticación
- `GET /api/logs/errors` - Logs de errores

### Auditoría de Cambios
- `GET /api/audit/changes` - Cambios en los datos
- `GET /api/audit/access` - Registros de acceso
- `GET /api/audit/user/{userId}` - Actividad por usuario
- `POST /api/audit/export` - Exportar registros de auditoría

## 🌐 Multi-idioma

### Gestión de Idiomas
- `GET /api/languages` - Listar idiomas disponibles
- `GET /api/languages/{code}` - Obtener configuración de idioma
- `PUT /api/languages/{code}` - Actualizar configuración de idioma
- `GET /api/translations/{language}` - Traducciones por idioma
- `POST /api/translations` - Crear nueva traducción
- `PUT /api/translations/{id}` - Actualizar traducción existente

## 📱 Móvil (API para App)

### API Móvil
- `GET /api/mobile/version` - Versión actual de la app móvil
- `GET /api/mobile/config` - Configuración para app móvil
- `POST /api/auth/mobile-login` - Login desde app móvil
- `GET /api/mobile/sync/{userId}` - Sincronización de datos
- `POST /api/mobile/offline-data` - Datos para modo offline

### Notificaciones Push
- `POST /api/mobile/push/register` - Registrar dispositivo para push
- `DELETE /api/mobile/push/unregister` - Desregistrar dispositivo
- `POST /api/mobile/push/send` - Enviar notificación push
- `GET /api/mobile/push/history/{userId}` - Historial de notificaciones push

## 🔧 Mantenimiento

### Modo Mantenimiento
- `GET /api/maintenance/status` - Estado del mantenimiento
- `POST /api/maintenance/enable` - Activar modo mantenimiento
- `POST /api/maintenance/disable` - Desactivar modo mantenimiento
- `GET /api/maintenance/message` - Mensaje de mantenimiento

### Backup y Restauración
- `GET /api/maintenance/backup` - Crear backup
- `POST /api/maintenance/restore` - Restaurar desde backup
- `GET /api/maintenance/backups` - Listar backups disponibles
- `DELETE /api/maintenance/backup/{id}` - Eliminar backup

---

## 📝 Notas para Desarrolladores

### Estandares de Respuesta
- **Formato de respuesta**: JSON consistente
- **Códigos de estado HTTP**: Uso apropiado de códigos
- **Mensajes de error**: Descriptivos y en español
- **Validación de entrada**: Validar todos los datos de entrada
- **Autenticación**: Token JWT en header Authorization

### Buenas Prácticas
- **Versionado de API**: Usar versionado semántico
- **Documentación**: Mantener documentación actualizada
- **Rate Limiting**: Implementar límites de uso
- **Logging**: Registrar todas las operaciones importantes
- **Testing**: Tests unitarios y de integración

### Seguridad
- **HTTPS**: Forzar HTTPS en producción
- **CORS**: Configurar apropiadamente
- **Input Validation**: Validar y sanitizar todas las entradas
- **SQL Injection**: Usar consultas parametrizadas
- **XSS**: Escapar contenido HTML en respuestas
- **Authentication**: Verificar tokens en cada petición

### Base de Datos
- **Migraciones**: Archivos SQL versionados
- **Seeders**: Datos iniciales para desarrollo
- **Indexes**: Índices apropiados para rendimiento
- **Backups**: Respaldos automáticos programados

---

## 🚀 Para Producción

### Checklist Final
- [ ] Revisar todos los endpoints implementados
- [ ] Verificar validaciones de seguridad
- [ ] Configurar variables de entorno
- [ ] Establecer conexión a base de datos producción
- [ ] Configurar HTTPS y certificados SSL
- [ ] Implementar rate limiting
- [ ] Configurar monitoreo y logging
- [ ] Realizar pruebas de carga
- [ ] Documentar API completa
- [ ] Preparar scripts de migración
- [ ] Configurar backup automático
- [ ] Establecer estrategia de rollback

### Variables de Entorno Requeridas
```bash
# Base de datos
DB_HOST=localhost
DB_PORT=5432
DB_NAME=obs_production
DB_USER=obs_user
DB_PASSWORD=secure_password

# API
API_URL=https://api.obs-school.com
API_PORT=443
JWT_SECRET=your-super-secret-jwt-key
JWT_EXPIRES_IN=24h

# Correo
MAIL_HOST=smtp.obs-school.com
MAIL_PORT=587
MAIL_USER=noreply@obs-school.com
MAIL_PASSWORD=email_password
MAIL_FROM=noreply@obs-school.com

# WhatsApp
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_PHONE_NUMBER_ID=123456789
WHATSAPP_VERSION=18.0

# Almacenamiento
STORAGE_TYPE=s3
AWS_ACCESS_KEY=your-access-key
AWS_SECRET_KEY=your-secret-key
AWS_BUCKET=obs-school-files
AWS_REGION=us-east-1

# Redis (caching)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=redis_password

# Monitoreo
SENTRY_DSN=https://your-sentry-dsn
LOG_LEVEL=info
```

### Comandos de Producción
```bash
# Instalar dependencias
npm ci --production

# Ejecutar migraciones
npm run migrate

# Cargar datos iniciales
npm run seed

# Construir para producción
npm run build

# Iniciar servidor
npm run start:prod
```
