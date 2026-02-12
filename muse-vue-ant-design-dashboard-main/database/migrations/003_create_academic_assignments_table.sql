-- Migration: Create academic_assignments table
-- Description: Create table for managing teacher-subject assignments and workload
-- Created: 2024-02-11

CREATE TABLE academic_assignments (
    id SERIAL PRIMARY KEY,
    grade VARCHAR(50) NOT NULL CHECK (grade IN ('pre-kinder', 'kinder', '1ro', '2do', '3ro', '4to', '5to', '6to')),
    teacher_id INTEGER NOT NULL REFERENCES users(id),
    subject_ids INTEGER[] NOT NULL, -- Array of subject IDs
    weekly_hours INTEGER NOT NULL DEFAULT 1 CHECK (weekly_hours >= 1 AND weekly_hours <= 40),
    academic_year INTEGER NOT NULL,
    observations TEXT,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'pending')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create indexes for faster lookups
CREATE INDEX idx_academic_assignments_grade ON academic_assignments(grade);
CREATE INDEX idx_academic_assignments_teacher ON academic_assignments(teacher_id);
CREATE INDEX idx_academic_assignments_status ON academic_assignments(status);
CREATE INDEX idx_academic_assignments_academic_year ON academic_assignments(academic_year);
CREATE INDEX idx_academic_assignments_created_by ON academic_assignments(created_by);

-- Create trigger function to calculate subjects count
CREATE OR REPLACE FUNCTION update_subjects_count()
RETURNS TRIGGER AS $$
BEGIN
    -- This function would be called by application logic to update a cached count
    -- For performance reasons, we'll maintain a separate cache table
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create assignment cache table for performance
CREATE TABLE assignment_cache (
    teacher_id INTEGER PRIMARY KEY REFERENCES users(id),
    subject_count INTEGER DEFAULT 0,
    total_hours INTEGER DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample assignments
INSERT INTO academic_assignments (grade, teacher_id, subject_ids, weekly_hours, academic_year, observations, status, created_by) VALUES
-- Pre-Kinder assignments
('pre-kinder', 1, ARRAY[1, 2, 3], 20, 2024, 'Docente especializado en educación preescolar con experiencia en desarrollo infantil', 'active', 1),
('pre-kinder', 2, ARRAY[1, 2, 3], 20, 2024, 'Docente bilingüe con certificación en educación temprana', 'active', 1),

-- Kinder assignments  
('kinder', 3, ARRAY[1, 2, 4, 5], 25, 2024, 'Docente titular con experiencia en alfabetización inicial', 'active', 1),
('kinder', 4, ARRAY[1, 2, 4, 5], 25, 2024, 'Docente de apoyo especializado en lectoescritura', 'active', 1),

-- First grade assignments
('1ro', 5, ARRAY[1, 6, 7], 30, 2024, 'Docente con especialidad en matemáticas y ciencias naturales', 'active', 1),
('1ro', 6, ARRAY[2, 8, 9], 30, 2024, 'Docente especializado en lenguaje y comunicación', 'active', 1),

-- Second grade assignments
('2do', 7, ARRAY[1, 6, 7], 30, 2024, 'Docente con experiencia en educación primaria avanzada', 'active', 1),
('2do', 8, ARRAY[2, 8, 9], 30, 2024, 'Docente especializado en matemáticas y razonamiento lógico', 'active', 1),

-- Third grade assignments
('3ro', 9, ARRAY[1, 6, 7], 30, 2024, 'Docente con certificación en educación primaria y especialización en ciencias', 'active', 1),
('3ro', 10, ARRAY[1, 6, 7], 30, 2024, 'Docente con experiencia en proyectos interdisciplinarios', 'active', 1),

-- Fourth grade assignments
('4to', 11, ARRAY[1, 6, 7], 30, 2024, 'Docente con maestría en educación primaria', 'active', 1),
('4to', 12, ARRAY[1, 6, 7], 30, 2024, 'Docente especializado en tecnología educativa', 'active', 1),

-- Fifth grade assignments
('5to', 13, ARRAY[1, 6, 7], 30, 2024, 'Docente con experiencia en preparación para secundaria', 'active', 1),
('5to', 14, ARRAY[1, 6, 7], 30, 2024, 'Docente con especialización en proyectos educativos innovadores', 'active', 1),

-- Sixth grade assignments
('6to', 15, ARRAY[1, 6, 7], 30, 2024, 'Docente coordinador con experiencia en transición educativa', 'active', 1),
('6to', 16, ARRAY[1, 6, 7], 30, 2024, 'Docente con especialización en orientación educativa', 'active', 1);

-- Add comments
COMMENT ON TABLE academic_assignments IS 'Tabla de asignaciones académicas del sistema escolar OBS';
COMMENT ON COLUMN academic_assignments.subject_ids IS 'Array de IDs de asignaturas asignadas al docente';
COMMENT ON COLUMN academic_assignments.weekly_hours IS 'Total de horas semanales asignadas al docente';
COMMENT ON COLUMN academic_assignments.academic_year IS 'Año lectivo de la asignación';
COMMENT ON COLUMN academic_assignments.observations IS 'Observaciones adicionales sobre la asignación';

-- Create view for assignment details
CREATE VIEW assignment_details AS
SELECT 
    aa.id,
    aa.grade,
    u.name as teacher_name,
    u.email as teacher_email,
    ARRAY_AGG(s.name ORDER BY s.name) as subject_names,
    aa.weekly_hours,
    aa.academic_year,
    aa.observations,
    aa.status,
    aa.created_at,
    aa.updated_at
FROM academic_assignments aa
JOIN users u ON aa.teacher_id = u.id
LEFT JOIN unnest(aa.subject_ids) AS subject_id ON true
LEFT JOIN subjects s ON subject_id = s.id
GROUP BY aa.id, aa.grade, u.name, u.email, aa.weekly_hours, aa.academic_year, aa.observations, aa.status, aa.created_at, aa.updated_at;
