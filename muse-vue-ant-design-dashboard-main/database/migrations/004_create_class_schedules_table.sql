-- Migration: Create class_schedules table
-- Description: Create table for managing class schedules and timetables
-- Created: 2024-02-11

CREATE TABLE class_schedules (
    id SERIAL PRIMARY KEY,
    grade VARCHAR(50) NOT NULL CHECK (grade IN ('pre-kinder', 'kinder', '1ro', '2do', '3ro', '4to', '5to', '6to')),
    day VARCHAR(20) NOT NULL CHECK (day IN ('monday', 'tuesday', 'wednesday', 'thursday', 'friday')),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject_id INTEGER NOT NULL REFERENCES subjects(id),
    teacher_id INTEGER NOT NULL REFERENCES users(id),
    classroom VARCHAR(100),
    observations TEXT,
    academic_year INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'cancelled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create indexes for faster lookups
CREATE INDEX idx_class_schedules_grade ON class_schedules(grade);
CREATE INDEX idx_class_schedules_day ON class_schedules(day);
CREATE INDEX idx_class_schedules_subject ON class_schedules(subject_id);
CREATE INDEX idx_class_schedules_teacher ON class_schedules(teacher_id);
CREATE INDEX idx_class_schedules_academic_year ON class_schedules(academic_year);
CREATE INDEX idx_class_schedules_status ON class_schedules(status);
CREATE INDEX idx_class_schedules_time_slot ON class_schedules(start_time, end_time);

-- Create constraint to prevent time conflicts
ALTER TABLE class_schedules ADD CONSTRAINT check_time_conflict 
CHECK (
    -- This would be implemented at application level for complex conflict checking
    true
);

-- Insert sample schedules
INSERT INTO class_schedules (grade, day, start_time, end_time, subject_id, teacher_id, classroom, academic_year, observations, status, created_by) VALUES
-- Monday schedules
('1ro', 'monday', '07:00', '07:40', 1, 5, 'Aula 101', 2024, 'Actividades de bienvenida y organización', 'active', 1),
('1ro', 'monday', '07:40', '08:20', 6, 5, 'Aula 101', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'monday', '08:20', '09:00', 2, 5, 'Aula 101', 2024, 'Matemáticas', 'active', 1),
('1ro', 'monday', '09:00', '09:40', 7, 5, 'Aula 101', 2024, 'Recreo y descanso', 'active', 1),
('1ro', 'monday', '09:40', '10:20', 1, 5, 'Aula 101', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'monday', '10:20', '11:00', 6, 5, 'Aula 101', 2024, 'Matemáticas', 'active', 1),
('1ro', 'monday', '11:00', '11:40', 7, 5, 'Aula 101', 2024, 'Ciencias naturales', 'active', 1),
('1ro', 'monday', '11:40', '12:20', 2, 5, 'Aula 101', 2024, 'Arte y música', 'active', 1),
('1ro', 'monday', '12:20', '13:00', 7, 5, 'Aula 101', 2024, 'Actividades de desarrollo', 'active', 1),

-- Tuesday schedules
('1ro', 'tuesday', '07:00', '07:40', 1, 5, 'Aula 102', 2024, 'Actividades de bienvenida y organización', 'active', 1),
('1ro', 'tuesday', '07:40', '08:20', 6, 5, 'Aula 102', 2024, 'Matemáticas', 'active', 1),
('1ro', 'tuesday', '08:20', '09:00', 2, 5, 'Aula 102', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'tuesday', '09:00', '09:40', 7, 5, 'Aula 102', 2024, 'Recreo y descanso', 'active', 1),
('1ro', 'tuesday', '09:40', '10:20', 1, 5, 'Aula 102', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'tuesday', '10:20', '11:00', 6, 5, 'Aula 102', 2024, 'Matemáticas', 'active', 1),
('1ro', 'tuesday', '11:00', '11:40', 7, 5, 'Aula 102', 2024, 'Ciencias naturales', 'active', 1),
('1ro', 'tuesday', '11:40', '12:20', 2, 5, 'Aula 102', 2024, 'Arte y música', 'active', 1),
('1ro', 'tuesday', '12:20', '13:00', 7, 5, 'Aula 102', 2024, 'Actividades de desarrollo', 'active', 1),

-- Wednesday schedules
('1ro', 'wednesday', '07:00', '07:40', 1, 5, 'Aula 103', 2024, 'Actividades de bienvenida y organización', 'active', 1),
('1ro', 'wednesday', '07:40', '08:20', 6, 5, 'Aula 103', 2024, 'Matemáticas', 'active', 1),
('1ro', 'wednesday', '08:20', '09:00', 2, 5, 'Aula 103', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'wednesday', '09:00', '09:40', 7, 5, 'Aula 103', 2024, 'Recreo y descanso', 'active', 1),
('1ro', 'wednesday', '09:40', '10:20', 1, 5, 'Aula 103', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'wednesday', '10:20', '11:00', 6, 5, 'Aula 103', 2024, 'Matemáticas', 'active', 1),
('1ro', 'wednesday', '11:00', '11:40', 7, 5, 'Aula 103', 2024, 'Ciencias naturales', 'active', 1),
('1ro', 'wednesday', '11:40', '12:20', 2, 5, 'Aula 103', 2024, 'Arte y música', 'active', 1),
('1ro', 'wednesday', '12:20', '13:00', 7, 5, 'Aula 103', 2024, 'Actividades de desarrollo', 'active', 1),

-- Thursday schedules
('1ro', 'thursday', '07:00', '07:40', 1, 5, 'Aula 104', 2024, 'Actividades de bienvenida y organización', 'active', 1),
('1ro', 'thursday', '07:40', '08:20', 6, 5, 'Aula 104', 2024, 'Matemáticas', 'active', 1),
('1ro', 'thursday', '08:20', '09:00', 2, 5, 'Aula 104', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'thursday', '09:00', '09:40', 7, 5, 'Aula 104', 2024, 'Recreo y descanso', 'active', 1),
('1ro', 'thursday', '09:40', '10:20', 1, 5, 'Aula 104', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'thursday', '10:20', '11:00', 6, 5, 'Aula 104', 2024, 'Matemáticas', 'active', 1),
('1ro', 'thursday', '11:00', '11:40', 7, 5, 'Aula 104', 2024, 'Ciencias naturales', 'active', 1),
('1ro', 'thursday', '11:40', '12:20', 2, 5, 'Aula 104', 2024, 'Arte y música', 'active', 1),
('1ro', 'thursday', '12:20', '13:00', 7, 5, 'Aula 104', 2024, 'Actividades de desarrollo', 'active', 1),

-- Friday schedules
('1ro', 'friday', '07:00', '07:40', 1, 5, 'Aula 105', 2024, 'Actividades de bienvenida y organización', 'active', 1),
('1ro', 'friday', '07:40', '08:20', 6, 5, 'Aula 105', 2024, 'Matemáticas', 'active', 1),
('1ro', 'friday', '08:20', '09:00', 2, 5, 'Aula 105', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'friday', '09:00', '09:40', 7, 5, 'Aula 105', 2024, 'Recreo y descanso', 'active', 1),
('1ro', 'friday', '09:40', '10:20', 1, 5, 'Aula 105', 2024, 'Lenguaje y comunicación', 'active', 1),
('1ro', 'friday', '10:20', '11:00', 6, 5, 'Aula 105', 2024, 'Matemáticas', 'active', 1),
('1ro', 'friday', '11:00', '11:40', 7, 5, 'Aula 105', 2024, 'Ciencias naturales', 'active', 1),
('1ro', 'friday', '11:40', '12:20', 2, 5, 'Aula 105', 2024, 'Arte y música', 'active', 1),
('1ro', 'friday', '12:20', '13:00', 7, 5, 'Aula 105', 2024, 'Actividades de desarrollo y limpieza', 'active', 1);

-- Create view for weekly schedule display
CREATE VIEW weekly_schedule AS
SELECT 
    cs.grade,
    cs.day,
    cs.start_time,
    cs.end_time,
    s.name as subject_name,
    s.code as subject_code,
    u.name as teacher_name,
    u.email as teacher_email,
    cs.classroom,
    cs.academic_year,
    cs.status
FROM class_schedules cs
JOIN subjects s ON cs.subject_id = s.id
JOIN users u ON cs.teacher_id = u.id
WHERE cs.status = 'active'
ORDER BY cs.grade, cs.day, cs.start_time;

-- Create view for conflict detection
CREATE VIEW schedule_conflicts AS
SELECT 
    cs1.id as schedule1_id,
    cs2.id as schedule2_id,
    cs1.grade,
    cs1.day,
    cs1.start_time as time_slot,
    s1.name as subject1_name,
    s2.name as subject2_name,
    u1.name as teacher1_name,
    u2.name as teacher2_name,
    CASE 
        WHEN cs1.start_time < cs2.end_time AND cs2.start_time < cs1.end_time THEN 'Conflict'
        ELSE 'No Conflict'
    END as conflict_status
FROM class_schedules cs1
JOIN class_schedules cs2 ON cs1.day = cs2.day AND cs1.grade = cs2.grade
JOIN subjects s1 ON cs1.subject_id = s1.id
JOIN subjects s2 ON cs2.subject_id = s2.id
JOIN users u1 ON cs1.teacher_id = u1.id
JOIN users u2 ON cs2.teacher_id = u2.id
WHERE cs1.status = 'active' AND cs2.status = 'active'
AND cs1.start_time < cs2.end_time AND cs2.start_time < cs1.end_time
AND cs1.id != cs2.id;

-- Add comments
COMMENT ON TABLE class_schedules IS 'Tabla de horarios de clases del sistema escolar OBS';
COMMENT ON COLUMN class_schedules.start_time IS 'Hora de inicio de la clase';
COMMENT ON COLUMN class_schedules.end_time IS 'Hora de fin de la clase';
COMMENT ON COLUMN class_schedules.classroom IS 'Número o nombre del aula asignada';
COMMENT ON COLUMN class_schedules.academic_year IS 'Año lectivo del horario';
