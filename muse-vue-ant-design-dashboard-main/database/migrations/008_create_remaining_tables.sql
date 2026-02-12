-- Migration: Create remaining core tables
-- Description: Create remaining tables for complete school management system
-- Created: 2024-02-11

-- Create teachers table
CREATE TABLE teachers (
    id SERIAL PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    second_last_name VARCHAR(100),
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    date_of_birth DATE NOT NULL,
    gender VARCHAR(10) CHECK (gender IN ('masculino', 'femenino', 'otro')),
    specialization VARCHAR(100),
    degree VARCHAR(255),
    hire_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'on_leave', 'suspended')),
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create grades table
CREATE TABLE grades (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    level VARCHAR(50) NOT NULL CHECK (level IN ('primaria', 'secundaria', 'bachillerato')),
    section VARCHAR(10),
    capacity INTEGER DEFAULT 30,
    classroom VARCHAR(100),
    head_teacher_id INTEGER REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create positions table
CREATE TABLE positions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    department VARCHAR(100),
    level VARCHAR(50) CHECK (level IN ('operativo', 'administrativo', 'docente', 'directivo')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create bug_reports table
CREATE TABLE bug_reports (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high', 'critical')),
    status VARCHAR(20) DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'resolved', 'closed', 'rejected')),
    reporter_id INTEGER REFERENCES users(id),
    assigned_to_id INTEGER REFERENCES users(id),
    screenshot_path VARCHAR(500),
    browser_info VARCHAR(255),
    steps_to_reproduce TEXT,
    expected_behavior TEXT,
    actual_behavior TEXT,
    resolution TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_by INTEGER REFERENCES users(id),
    resolved_at TIMESTAMP
);

-- Create notifications table
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) CHECK (type IN ('info', 'success', 'warning', 'error')),
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    action_url VARCHAR(500),
    icon VARCHAR(50)
);

-- Create audit_logs table
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_teachers_user_id ON teachers(user_id);
CREATE INDEX idx_teachers_status ON teachers(status);
CREATE INDEX idx_teachers_specialization ON teachers(specialization);

CREATE INDEX idx_grades_level ON grades(level);
CREATE INDEX idx_grades_status ON grades(status);

CREATE INDEX idx_bug_reports_status ON bug_reports(status);
CREATE INDEX idx_bug_reports_priority ON bug_reports(priority);
CREATE INDEX idx_bug_reports_reporter ON bug_reports(reporter_id);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read_at ON notifications(read_at);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- Insert sample data
INSERT INTO grades (name, level, section, capacity, status) VALUES
('Pre-Kínder A', 'primaria', 'A', 25, 'Aula 1', 'active'),
('Pre-Kínder B', 'primaria', 'B', 25, 'Aula 2', 'active'),
('Kínder A', 'primaria', 'kinder', '30, 'Aula 3', 'active'),
('Kínder B', 'primaria', 'kinder', '30, 'Aula 4', 'active'),
('1er Grado A', 'primaria', '1ro', 'A', 35, 'Aula 5', 'active'),
('1er Grado B', 'primaria', '1ro', 'B', 35, 'Aula 6', 'active'),
('2do Grado A', 'primaria', '2do', 'A', 35, 'Aula 7', 'active'),
('2do Grado B', 'primaria', '2do', 'B', 35, 'Aula 8', 'active'),
('3er Grado A', 'primaria', '3ro', 'A', 35, 'Aula 9', 'active'),
('3er Grado B', 'primaria', '3ro', 'B', 35, 'Aula 10', 'active'),
('4to Grado A', 'primaria', '4to', 'A', 35, 'Aula 11', 'active'),
('4to Grado B', 'primaria', '4to', 'B', 35, 'Aula 12', 'active'),
('5to Grado A', 'primaria', '5to', 'A', 35, 'Aula 13', 'active'),
('5to Grado B', 'primaria', '5to', 'B', 35, 'Aula 14', 'active'),
('6to Grado A', 'primaria', '6to', 'A', 35, 'Aula 15', 'active'),
('6to Grado B', 'primaria', '6to', 'B', 35, 'Aula 16', 'active');

INSERT INTO positions (name, description, department, level, status) VALUES
('Director Escolar', 'Responsable de la dirección general del centro educativo', 'directivo', 'directivo', 'active'),
('Secretario Académico', 'Gestión de asuntos académicos y administrativos', 'administrativo', 'secretario', 'active'),
('Coordinador Pedagógico', 'Supervisión y coordinación del personal docente', 'docente', 'coordinador', 'active'),
('Docente Primaria', 'Docente de educación primaria', 'docente', 'docente', 'active'),
('Docente Secundaria', 'Docente de educación secundaria', 'docente', 'docente', 'active'),
('Profesor Especializado', 'Profesor de asignaturas específicas', 'docente', 'docente', 'active'),
('Psicólogo Escolar', 'Apoyo psicológico y orientación educativa', 'docente', 'psicologo', 'active'),
('Trabajador Social', 'Servicios sociales y apoyo familiar', 'operativo', 'trabajador_social', 'active'),
('Personal Administrativo', 'Personal de apoyo administrativo', 'operativo', 'administrativo', 'active'),
('Bibliotecario', 'Gestión de biblioteca y recursos educativos', 'operativo', 'bibliotecario', 'active');

-- Add comments
COMMENT ON TABLE teachers IS 'Tabla de docentes del sistema escolar OBS';
COMMENT ON TABLE teachers.employee_code IS 'Código único de empleado';

COMMENT ON TABLE bug_reports IS 'Tabla de reportes de errores y mejoras del sistema';
COMMENT ON TABLE bug_reports.priority IS 'Prioridad: low, medium, high, critical';

COMMENT ON TABLE notifications IS 'Tabla de notificaciones del sistema';
COMMENT ON TABLE notifications.type IS 'Tipo: info, success, warning, error';

COMMENT ON TABLE audit_logs IS 'Tabla de auditoría del sistema para seguimiento de cambios';
