-- Migration: Create attendance table
-- Description: Table for student attendance tracking
-- Created: 2026-02-11

-- Create the main attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id SERIAL PRIMARY KEY,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
    grade VARCHAR(50) NOT NULL,
    subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE RESTRICT,
    teacher_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'present' CHECK (status IN ('present', 'late', 'absent', 'excused')),
    arrival_time TIME,
    departure_time TIME,
    observations TEXT,
    academic_year VARCHAR(10) NOT NULL DEFAULT '2024',
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    updated_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_attendance_student_id ON attendance(student_id);
CREATE INDEX IF NOT EXISTS idx_attendance_grade ON attendance(grade);
CREATE INDEX IF NOT EXISTS idx_attendance_subject_id ON attendance(subject_id);
CREATE INDEX IF NOT EXISTS idx_attendance_teacher_id ON attendance(teacher_id);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(date);
CREATE INDEX IF NOT EXISTS idx_attendance_status ON attendance(status);
CREATE INDEX IF NOT EXISTS idx_attendance_academic_year ON attendance(academic_year);

-- Create composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_attendance_composite ON attendance(grade, subject_id, date, academic_year);
CREATE INDEX IF NOT EXISTS idx_attendance_student_date ON attendance(student_id, date);

-- Create unique constraint to prevent duplicate attendance records
CREATE UNIQUE INDEX IF NOT EXISTS idx_attendance_unique_record 
ON attendance(student_id, grade, subject_id, date, academic_year);

-- Create trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_attendance_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER attendance_updated_at_trigger
    BEFORE UPDATE ON attendance
    FOR EACH ROW
    EXECUTE FUNCTION update_attendance_updated_at();

-- Create a view for attendance details with student and subject information
CREATE OR REPLACE VIEW attendance_details AS
SELECT 
    a.id,
    a.student_id,
    s.first_name,
    s.last_name,
    s.student_id as student_number,
    s.photo,
    a.grade,
    a.subject_id,
    sub.name as subject_name,
    sub.code as subject_code,
    a.teacher_id,
    u.first_name || ' ' || u.last_name as teacher_name,
    a.date,
    a.status,
    a.arrival_time,
    a.departure_time,
    a.observations,
    a.academic_year,
    a.created_by,
    a.updated_by,
    a.created_at,
    a.updated_at
FROM attendance a
LEFT JOIN students s ON a.student_id = s.id
LEFT JOIN subjects sub ON a.subject_id = sub.id
LEFT JOIN users u ON a.teacher_id = u.id;

-- Create a view for attendance statistics
CREATE OR REPLACE VIEW attendance_statistics AS
SELECT 
    grade,
    subject_id,
    date,
    academic_year,
    COUNT(*) as total_students,
    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count,
    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
    COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused_count,
    ROUND(
        (COUNT(CASE WHEN status IN ('present', 'late') THEN 1 END) * 100.0 / COUNT(*)), 2
    ) as attendance_percentage
FROM attendance
GROUP BY grade, subject_id, date, academic_year;

-- Insert sample data
INSERT INTO attendance (student_id, grade, subject_id, teacher_id, date, status, arrival_time, observations, academic_year) VALUES
-- Sample attendance for 1st Grade - Mathematics
(1, '1er Grado', 2, 2, '2024-02-11', 'present', '08:00:00', 'Llegó a tiempo', '2024'),
(2, '1er Grado', 2, 2, '2024-02-11', 'present', '08:05:00', 'Llegó 5 minutos tarde', '2024'),
(3, '1er Grado', 2, 2, '2024-02-11', 'late', '08:15:00', 'Llegó 15 minutos tarde', '2024'),
(4, '1er Grado', 2, 2, '2024-02-11', 'absent', NULL, 'No asistió por enfermedad', '2024'),
(5, '1er Grado', 2, 2, '2024-02-11', 'present', '07:55:00', 'Llegó antes de tiempo', '2024'),

-- Sample attendance for 1st Grade - Language
(1, '1er Grado', 1, 2, '2024-02-11', 'present', '09:00:00', 'Participó activamente', '2024'),
(2, '1er Grado', 1, 2, '2024-02-11', 'present', '09:02:00', 'Buen desempeño', '2024'),
(3, '1er Grado', 1, 2, '2024-02-11', 'present', '09:00:00', 'Mejoró su puntualidad', '2024'),
(4, '1er Grado', 1, 2, '2024-02-11', 'excused', NULL, 'Justificado por médico', '2024'),
(5, '1er Grado', 1, 2, '2024-02-11', 'present', '08:58:00', 'Muy participativo', '2024'),

-- Sample attendance for 2nd Grade - Mathematics
(6, '2do Grado', 2, 3, '2024-02-11', 'present', '08:00:00', 'Excelente trabajo', '2024'),
(7, '2do Grado', 2, 3, '2024-02-11', 'late', '08:10:00', 'Problemas de transporte', '2024'),
(8, '2do Grado', 2, 3, '2024-02-11', 'present', '07:58:00', 'Muy responsable', '2024'),
(9, '2do Grado', 2, 3, '2024-02-11', 'present', '08:01:00', 'Buen comportamiento', '2024'),
(10, '2do Grado', 2, 3, '2024-02-11', 'absent', NULL, 'Sin justificación', '2024');

-- Add comments to table and columns
COMMENT ON TABLE attendance IS 'Tabla para el registro de asistencia de estudiantes';
COMMENT ON COLUMN attendance.id IS 'Identificador único del registro de asistencia';
COMMENT ON COLUMN attendance.student_id IS 'ID del estudiante';
COMMENT ON COLUMN attendance.grade IS 'Grado escolar';
COMMENT ON COLUMN attendance.subject_id IS 'ID de la asignatura';
COMMENT ON COLUMN attendance.teacher_id IS 'ID del docente que tomó la asistencia';
COMMENT ON COLUMN attendance.date IS 'Fecha de la clase';
COMMENT ON COLUMN attendance.status IS 'Estado de asistencia (present, late, absent, excused)';
COMMENT ON COLUMN attendance.arrival_time IS 'Hora de llegada a clase';
COMMENT ON COLUMN attendance.departure_time IS 'Hora de salida de clase';
COMMENT ON COLUMN attendance.observations IS 'Observaciones sobre la asistencia';
COMMENT ON COLUMN attendance.academic_year IS 'Año académico';
COMMENT ON COLUMN attendance.created_by IS 'Usuario que creó el registro';
COMMENT ON COLUMN attendance.updated_by IS 'Usuario que actualizó el registro';
COMMENT ON COLUMN attendance.created_at IS 'Fecha de creación del registro';
COMMENT ON COLUMN attendance.updated_at IS 'Fecha de última actualización';

-- Create a function to get attendance by filters
CREATE OR REPLACE FUNCTION get_attendance_by_filters(
    p_grade VARCHAR DEFAULT NULL,
    p_subject_id INTEGER DEFAULT NULL,
    p_date DATE DEFAULT NULL,
    p_academic_year VARCHAR DEFAULT NULL
)
RETURNS TABLE(
    id INTEGER,
    student_id INTEGER,
    first_name VARCHAR,
    last_name VARCHAR,
    student_number VARCHAR,
    photo VARCHAR,
    grade VARCHAR,
    subject_id INTEGER,
    subject_name VARCHAR,
    teacher_id INTEGER,
    teacher_name VARCHAR,
    date DATE,
    status VARCHAR,
    arrival_time TIME,
    departure_time TIME,
    observations TEXT,
    academic_year VARCHAR,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.id,
        a.student_id,
        s.first_name,
        s.last_name,
        s.student_id as student_number,
        s.photo,
        a.grade,
        a.subject_id,
        sub.name as subject_name,
        a.teacher_id,
        u.first_name || ' ' || u.last_name as teacher_name,
        a.date,
        a.status,
        a.arrival_time,
        a.departure_time,
        a.observations,
        a.academic_year,
        a.created_at,
        a.updated_at
    FROM attendance a
    LEFT JOIN students s ON a.student_id = s.id
    LEFT JOIN subjects sub ON a.subject_id = sub.id
    LEFT JOIN users u ON a.teacher_id = u.id
    WHERE 
        (p_grade IS NULL OR a.grade = p_grade)
        AND (p_subject_id IS NULL OR a.subject_id = p_subject_id)
        AND (p_date IS NULL OR a.date = p_date)
        AND (p_academic_year IS NULL OR a.academic_year = p_academic_year)
    ORDER BY s.last_name, s.first_name;
END;
$$ LANGUAGE plpgsql;

-- Create a function for batch attendance insertion
CREATE OR REPLACE FUNCTION batch_insert_attendance(
    p_attendance_records JSON
)
RETURNS INTEGER AS $$
DECLARE
    record_count INTEGER := 0;
    record JSON;
    student_id_val INTEGER;
    grade_val VARCHAR;
    subject_id_val INTEGER;
    teacher_id_val INTEGER;
    date_val DATE;
    status_val VARCHAR;
    arrival_time_val TIME;
    observations_val TEXT;
    academic_year_val VARCHAR;
BEGIN
    -- Loop through each attendance record in the JSON array
    FOR record IN SELECT * FROM json_array_elements(p_attendance_records)
    LOOP
        -- Extract values from JSON
        student_id_val := (record->>'student_id')::INTEGER;
        grade_val := record->>'grade';
        subject_id_val := (record->>'subject_id')::INTEGER;
        teacher_id_val := (record->>'teacher_id')::INTEGER;
        date_val := (record->>'date')::DATE;
        status_val := record->>'status';
        arrival_time_val := CASE WHEN (record->>'arrival_time') = '' THEN NULL ELSE (record->>'arrival_time')::TIME END;
        observations_val := record->>'observations';
        academic_year_val := COALESCE(record->>'academic_year', '2024');

        -- Insert or update attendance record
        INSERT INTO attendance (
            student_id, grade, subject_id, teacher_id, date, status, 
            arrival_time, observations, academic_year
        ) VALUES (
            student_id_val, grade_val, subject_id_val, teacher_id_val, date_val, status_val,
            arrival_time_val, observations_val, academic_year_val
        )
        ON CONFLICT (student_id, grade, subject_id, date, academic_year)
        DO UPDATE SET
            status = EXCLUDED.status,
            arrival_time = EXCLUDED.arrival_time,
            observations = EXCLUDED.observations,
            updated_at = CURRENT_TIMESTAMP;

        record_count := record_count + 1;
    END LOOP;

    RETURN record_count;
END;
$$ LANGUAGE plpgsql;

-- Create audit trigger for attendance
CREATE OR REPLACE FUNCTION audit_attendance()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES ('attendance', 'INSERT', NEW.id, NULL, row_to_json(NEW), NEW.created_by, CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES ('attendance', 'UPDATE', NEW.id, row_to_json(OLD), row_to_json(NEW), NEW.updated_by, CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES ('attendance', 'DELETE', OLD.id, row_to_json(OLD), NULL, OLD.created_by, CURRENT_TIMESTAMP);
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER attendance_audit_trigger
    AFTER INSERT OR UPDATE OR DELETE ON attendance
    FOR EACH ROW
    EXECUTE FUNCTION audit_attendance();

-- Create a function to get monthly attendance report
CREATE OR REPLACE FUNCTION get_monthly_attendance_report(
    p_grade VARCHAR DEFAULT NULL,
    p_month INTEGER DEFAULT NULL,
    p_year INTEGER DEFAULT NULL,
    p_academic_year VARCHAR DEFAULT NULL
)
RETURNS TABLE(
    student_id INTEGER,
    first_name VARCHAR,
    last_name VARCHAR,
    total_classes INTEGER,
    present_count INTEGER,
    late_count INTEGER,
    absent_count INTEGER,
    excused_count INTEGER,
    attendance_percentage DECIMAL(5,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        s.id as student_id,
        s.first_name,
        s.last_name,
        COUNT(*) as total_classes,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
        COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as excused_count,
        ROUND(
            (COUNT(CASE WHEN a.status IN ('present', 'late') THEN 1 END) * 100.0 / COUNT(*)), 2
        ) as attendance_percentage
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id
    WHERE 
        (p_grade IS NULL OR s.grade = p_grade)
        AND (p_academic_year IS NULL OR a.academic_year = p_academic_year OR a.academic_year IS NULL)
        AND (p_month IS NULL OR EXTRACT(MONTH FROM a.date) = p_month OR a.date IS NULL)
        AND (p_year IS NULL OR EXTRACT(YEAR FROM a.date) = p_year OR a.date IS NULL)
    GROUP BY s.id, s.first_name, s.last_name
    ORDER BY s.last_name, s.first_name;
END;
$$ LANGUAGE plpgsql;
