-- Migration: Create users table with roles and permissions
-- Description: Create comprehensive users table with role-based access control
-- Created: 2024-02-11

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user' CHECK (role IN ('sistema', 'super_usuario', 'administrador', 'director', 'secretaria', 'docente', 'empleado', 'padre')),
    phone VARCHAR(50),
    address TEXT,
    date_of_birth DATE,
    gender VARCHAR(10) CHECK (gender IN ('masculino', 'femenino', 'otro')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended', 'pending')),
    last_login TIMESTAMP,
    email_verified BOOLEAN DEFAULT false,
    phone_verified BOOLEAN DEFAULT false,
    has_temp_password BOOLEAN DEFAULT false,
    temp_password_expires TIMESTAMP,
    profile_picture VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create roles table
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSONB NOT NULL DEFAULT '[]', -- Array of permission objects
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_roles junction table
CREATE TABLE user_roles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INTEGER REFERENCES users(id)
);

-- Create screens table for permission management
CREATE TABLE screens (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_permissions junction table
CREATE TABLE role_permissions (
    id SERIAL PRIMARY KEY,
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    screen_id INTEGER NOT NULL REFERENCES screens(id) ON DELETE CASCADE,
    permissions JSONB NOT NULL DEFAULT '{"read": true, "create": false, "update": false, "delete": false}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_sessions table for session management
CREATE TABLE user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address INET,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create password_reset_requests table
CREATE TABLE password_reset_requests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_last_login ON users(last_login);
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_expires_at ON user_sessions(expires_at);
CREATE INDEX idx_password_reset_token ON password_reset_requests(token);

-- Insert default roles
INSERT INTO roles (name, description, permissions, status) VALUES
('sistema', 'Acceso completo al sistema con todos los privilegios', 
'[
  {"key": "*", "permissions": {"read": true, "create": true, "update": true, "delete": true, "admin": true}}
]', 
'active'),

('super_usuario', 'Acceso administrativo con casi todos los privilegios', 
'[
  {"key": "users", "permissions": {"read": true, "create": true, "update": true, "delete": true, "admin": true}},
  {"key": "roles", "permissions": {"read": true, "create": true, "update": true, "delete": true, "admin": true}},
  {"key": "reports", "permissions": {"read": true, "create": true, "update": true, "delete": true, "admin": true}},
  {"key": "settings", "permissions": {"read": true, "create": true, "update": true, "delete": true, "admin": true}}
]', 
'active'),

('director', 'Director escolar con privilegios administrativos', 
'[
  {"key": "users", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}},
  {"key": "students", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}},
  {"key": "teachers", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}},
  {"key": "reports", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}},
  {"key": "academic", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}}
]', 
'active'),

('secretaria', 'Secretaría escolar con privilegios administrativos básicos', 
'[
  {"key": "students", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}},
  {"key": "teachers", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": true}},
  {"key": "reports", "permissions": {"read": true, "create": true, "update": false, "delete": false, "admin": true}}
]', 
'active'),

('docente', 'Docente con acceso a módulos académicos', 
'[
  {"key": "students", "permissions": {"read": true, "create": false, "update": true, "delete": false, "admin": true}},
  {"key": "subjects", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": true}},
  {"key": "schedules", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": true}},
  {"key": "assignments", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": true}},
  {"key": "attendance", "permissions": {"read": true, "create": true, "update": false, "delete": false, "admin": true}},
  {"key": "grades", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": true}}
]', 
'active'),

('empleado', 'Empleado con acceso limitado a módulos específicos', 
'[
  {"key": "inventory", "permissions": {"read": true, "create": true, "update": true, "delete": false, "admin": true}},
  {"key": "reports", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": true}}
]', 
'active'),

('padre', 'Padre de familia con acceso a información de hijos', 
'[
  {"key": "students", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": false}},
  {"key": "reports", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": false}},
  {"key": "communications", "permissions": {"read": true, "create": false, "update": false, "delete": false, "admin": false}}
]', 
'active');

-- Insert default screens
INSERT INTO screens (key, name, description, module, icon, status) VALUES
('dashboard', 'Dashboard Principal', 'Panel principal con estadísticas y acceso rápido', 'general', 'dashboard', 'active'),
('profile', 'Perfil de Usuario', 'Gestión del perfil personal', 'general', 'user', 'active'),
('matricula', 'Matrícula', 'Registro de nuevos estudiantes', 'academic', 'user', 'active'),
('tables', 'Gestión de Usuarios', 'Administración de usuarios y roles', 'administration', 'users', 'active'),
('roles', 'Gestión de Roles', 'Configuración de roles y permisos', 'administration', 'roles', 'active'),
('notificaciones', 'Notificaciones', 'Centro de notificaciones del sistema', 'general', 'bell', 'active'),
('sesiones', 'Sesiones Activas', 'Gestión de sesiones de usuario', 'security', 'key', 'active'),
('registro/alumnos', 'Registro de Alumnos', 'Mantenimiento de datos de estudiantes', 'academic', 'database', 'active'),
('registro/maestros', 'Registro de Maestros', 'Mantenimiento de datos de docentes', 'academic', 'database', 'active'),
('registro/empleados', 'Registro de Empleados', 'Mantenimiento de datos de empleados', 'academic', 'database', 'active'),
('registro/cargos', 'Registro de Cargos', 'Gestión de cargos y puestos', 'academic', 'database', 'active'),
('registro/grados', 'Registro de Grados', 'Configuración de grados y secciones', 'academic', 'database', 'active'),
('registro/asignaturas', 'Registro de Asignaturas', 'Catálogo de materias y cursos', 'academic', 'database', 'active'),
('pagos/matriculas', 'Pagos de Matrículas', 'Gestión de pagos de matrícula', 'financial', 'dollar', 'active'),
('pagos/planilla', 'Planilla', 'Gestión de planilla y nómina', 'financial', 'dollar', 'active'),
('pagos/contabilidad', 'Contabilidad', 'Registros contables y financieros', 'financial', 'dollar', 'active'),
('reportes/asistencia', 'Reportes de Asistencia', 'Control y reportes de asistencia', 'reports', 'chart', 'active'),
('reportes/academico', 'Reportes Académicos', 'Reportes de rendimiento académico', 'reports', 'chart', 'active'),
('reportes/ingreso', 'Ingreso de Reportes', 'Generación y gestión de reportes', 'reports', 'edit', 'active'),
('boletas/calificaciones', 'Boletas de Calificaciones', 'Boletas y certificados de notas', 'reports', 'file-text', 'active'),
('inventario/control', 'Control de Inventario', 'Gestión de inventario escolar', 'inventory', 'box', 'active'),
('reportes/errores', 'Reporte de Errores', 'Sistema de reporte de errores y mejoras', 'support', 'bug', 'active'),
('reportes/incidencias', 'Reporte de Incidencias', 'Registro y seguimiento de incidencias', 'support', 'alert', 'active'),
('comunicados', 'Comunicados', 'Envío de comunicados masivos', 'communications', 'mail', 'active'),
('planificacion/estudios', 'Plan de Estudios', 'Gestión de planes de estudio y currículum', 'academic', 'book', 'active'),
('planificacion/carga', 'Carga Académica', 'Asignación de carga docente', 'academic', 'calendar', 'active'),
('planificacion/horarios', 'Horarios de Clases', 'Gestión de horarios y cronograma', 'academic', 'calendar', 'active'),
('password-reset-requests', 'Restablecimiento de Contraseña', 'Gestión de solicitudes de cambio de contraseña', 'security', 'key', 'active');

-- Add comments
COMMENT ON TABLE users IS 'Tabla de usuarios del sistema escolar OBS';
COMMENT ON TABLE users.role IS 'Rol del usuario: sistema, super_usuario, administrador, director, secretaria, docente, empleado, padre';
COMMENT ON TABLE users.status IS 'Estado del usuario: active, inactive, suspended, pending';
COMMENT ON TABLE users.has_temp_password IS 'Indica si el usuario tiene contraseña temporal';

COMMENT ON TABLE roles IS 'Tabla de roles del sistema con permisos JSON';
COMMENT ON TABLE roles.permissions IS 'Permisos en formato JSON con objetos de permisos';

COMMENT ON TABLE user_roles IS 'Tabla de relación muchos-a-muchos entre usuarios y roles';

COMMENT ON TABLE screens IS 'Tabla de pantallas del sistema con control de acceso';
COMMENT ON TABLE screens.key IS 'Identificador único de la pantalla';
COMMENT ON TABLE screens.permissions IS 'Permisos específicos por pantalla y rol';

COMMENT ON TABLE user_sessions IS 'Tabla de sesiones activas de usuarios';
COMMENT ON TABLE user_sessions.session_token IS 'Token único de sesión JWT';

COMMENT ON TABLE password_reset_requests IS 'Tabla de solicitudes de restablecimiento de contraseña';
