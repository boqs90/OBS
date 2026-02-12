-- Migration: Create students table
-- Description: Create comprehensive students table with academic tracking
-- Created: 2024-02-11

CREATE TABLE students (
    id SERIAL PRIMARY KEY,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    second_last_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(50),
    date_of_birth DATE NOT NULL,
    gender VARCHAR(10) CHECK (gender IN ('masculino', 'femenino', 'otro')),
    grade VARCHAR(50) NOT NULL CHECK (grade IN ('pre-kinder', 'kinder', '1ro', '2do', '3ro', '4to', '5to', '6to')),
    section VARCHAR(10),
    enrollment_date DATE NOT NULL,
    enrollment_status VARCHAR(20) DEFAULT 'active' CHECK (enrollment_status IN ('active', 'inactive', 'graduated', 'transferred', 'suspended')),
    address TEXT,
    parent_guardian_name VARCHAR(255),
    parent_guardian_phone VARCHAR(50),
    parent_guardian_email VARCHAR(255),
    parent_guardian_id INTEGER REFERENCES users(id),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(50),
    emergency_contact_relationship VARCHAR(50),
    medical_info TEXT,
    allergies TEXT,
    special_needs TEXT,
    previous_school VARCHAR(255),
    enrollment_number VARCHAR(20),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'graduated', 'transferred', 'suspended')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create academic_records table for student academic history
CREATE TABLE academic_records (
    id SERIAL PRIMARY KEY,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    academic_year INTEGER NOT NULL,
    grade VARCHAR(50) NOT NULL,
    subject_id INTEGER REFERENCES subjects(id),
    teacher_id INTEGER REFERENCES users(id),
    period VARCHAR(20) CHECK (period IN ('primer', 'segundo', 'tercer', 'cuarto')),
    final_grade DECIMAL(5,2),
    attendance_rate DECIMAL(5,2),
    conduct_grade VARCHAR(5),
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create student_documents table
CREATE TABLE student_documents (
    id SERIAL PRIMARY KEY,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    document_type VARCHAR(50) NOT NULL CHECK (document_type IN ('birth_certificate', 'report_card', 'medical_certificate', 'transfer_certificate', 'id_card', 'photo', 'other')),
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    mime_type VARCHAR(100),
    upload_date DATE NOT NULL,
    expiry_date DATE,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'expired')),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INTEGER REFERENCES users(id)
);

-- Create student_attendance table
CREATE TABLE student_attendance (
    id SERIAL PRIMARY KEY,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    grade VARCHAR(50) NOT NULL,
    subject_id INTEGER REFERENCES subjects(id),
    teacher_id INTEGER REFERENCES users(id),
    attendance_status VARCHAR(20) CHECK (attendance_status IN ('present', 'absent', 'late', 'excused', 'suspended')),
    arrival_time TIME,
    departure_time TIME,
    absence_reason VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INTEGER REFERENCES users(id)
);

-- Create indexes for performance
CREATE INDEX idx_students_grade ON students(grade);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_enrollment_status ON students(enrollment_status);
CREATE INDEX idx_students_parent_guardian ON students(parent_guardian_id);
CREATE INDEX idx_students_student_code ON students(student_code);

CREATE INDEX idx_academic_records_student_year ON academic_records(student_id, academic_year);
CREATE INDEX idx_academic_records_subject ON academic_records(subject_id);
CREATE INDEX idx_academic_records_teacher ON academic_records(teacher_id);

CREATE INDEX idx_student_documents_student ON student_documents(student_id);
CREATE INDEX idx_student_documents_type ON student_documents(document_type);

CREATE INDEX idx_student_attendance_student_date ON student_attendance(student_id, date);
CREATE INDEX idx_student_attendance_status ON student_attendance(attendance_status);

-- Insert sample students
INSERT INTO students (student_code, first_name, last_name, second_last_name, email, phone, date_of_birth, gender, grade, section, enrollment_date, enrollment_status, address, parent_guardian_name, parent_guardian_phone, parent_guardian_email, enrollment_number, status, created_by) VALUES
('PREK001', 'ANA', 'GARCÍA', 'LOPEZ', 'ana.garcia@ejemplo.com', '50412345678', '2018-03-15', 'femenino', 'pre-kinder', 'A', '2024-01-15', 'active', 'Colonia Las Flores, Calle Principal #123', 'MARÍA GARCÍA', '50412345679', '50412345679', 'maria.garcia@ejemplo.com', 1, 'PREK2024001', 'active'),

('KIN001', 'CARLOS', 'RODRÍGUEZ', 'MARTÍNEZ', 'carlos.rodriguez@ejemplo.com', '50412345679', '2017-08-20', 'masculino', 'kinder', 'B', '2024-01-15', 'active', 'Colonia Los Pinos, Calle Secundaria #456', 'JOSÉ RODRÍGUEZ', '50412345680', '50412345680', 'jose.rodriguez@ejemplo.com', 1, 'KIN2024002', 'active'),

('1RO001', 'MARÍA', 'FERNÁNDEZ', 'SÁNCHEZ', 'maria.fernandez@ejemplo.com', '50412345678', '2016-05-10', 'femenino', '1ro', 'A', '2024-01-15', 'active', 'Colonia El Roble, Avenida Central #789', 'CARMEN FERNÁNDEZ', '50412345681', '50412345681', 'carmen.fernandez@ejemplo.com', 1, '1RO2024001', 'active'),

('2DO001', 'JOSÉ', 'MARTÍNEZ', 'RAMÍREZ', 'jose.martinez@ejemplo.com', '50412345677', '2015-07-22', 'masculino', '2do', 'B', '2024-01-15', 'active', 'Colonia Las Acacias, Calle Principal #234', 'ROSA MARTÍNEZ', '50412345682', '50412345682', 'rosa.martinez@ejemplo.com', 1, '2DO2024001', 'active');

-- Add comments
COMMENT ON TABLE students IS 'Tabla de estudiantes del sistema escolar OBS';
COMMENT ON TABLE students.student_code IS 'Código único del estudiante';
COMMENT ON TABLE students.enrollment_status IS 'Estado de matrícula: active, inactive, graduated, transferred, suspended';
COMMENT ON TABLE students.parent_guardian_id IS 'ID del padre/tutor responsable';

COMMENT ON TABLE academic_records IS 'Tabla de registros académicos de estudiantes';
COMMENT ON TABLE academic_records.final_grade IS 'Calificación final del período';
COMMENT ON TABLE academic_records.attendance_rate IS 'Porcentaje de asistencia del período';

COMMENT ON TABLE student_documents IS 'Tabla de documentos de estudiantes';
COMMENT ON TABLE student_documents.document_type IS 'Tipo de documento: birth_certificate, report_card, etc.';

COMMENT ON TABLE student_attendance IS 'Tabla de asistencia de estudiantes';
COMMENT ON TABLE student_attendance.attendance_status IS 'Estado de asistencia: present, absent, late, etc.';
