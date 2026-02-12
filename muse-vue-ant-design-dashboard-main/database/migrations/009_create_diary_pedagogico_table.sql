-- Migration: Create diary_pedagogico table
-- Description: Table for pedagogical diary entries
-- Created: 2026-02-11

-- Create the main diary_pedagogico table
CREATE TABLE IF NOT EXISTS diary_pedagogico (
    id SERIAL PRIMARY KEY,
    grade VARCHAR(50) NOT NULL,
    subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE RESTRICT,
    teacher_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    student_count INTEGER NOT NULL DEFAULT 0,
    topic VARCHAR(255),
    activities TEXT,
    observations TEXT,
    resources TEXT,
    assessment TEXT,
    homework TEXT,
    academic_year VARCHAR(10) NOT NULL DEFAULT '2024',
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'cancelled', 'postponed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_grade ON diary_pedagogico(grade);
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_subject_id ON diary_pedagogico(subject_id);
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_teacher_id ON diary_pedagogico(teacher_id);
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_date ON diary_pedagogico(date);
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_academic_year ON diary_pedagogico(academic_year);
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_status ON diary_pedagogico(status);

-- Create a composite index for common queries
CREATE INDEX IF NOT EXISTS idx_diary_pedagogico_composite ON diary_pedagogico(grade, subject_id, date, academic_year);

-- Create trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_diary_pedagogico_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER diary_pedagogico_updated_at_trigger
    BEFORE UPDATE ON diary_pedagogico
    FOR EACH ROW
    EXECUTE FUNCTION update_diary_pedagogico_updated_at();

-- Create a view for diary entries with subject and teacher information
CREATE OR REPLACE VIEW diary_pedagogico_details AS
SELECT 
    dp.id,
    dp.grade,
    dp.subject_id,
    s.name as subject_name,
    s.code as subject_code,
    dp.teacher_id,
    u.first_name || ' ' || u.last_name as teacher_name,
    dp.date,
    dp.start_time,
    dp.end_time,
    dp.student_count,
    dp.topic,
    dp.activities,
    dp.observations,
    dp.resources,
    dp.assessment,
    dp.homework,
    dp.academic_year,
    dp.status,
    dp.created_at,
    dp.updated_at
FROM diary_pedagogico dp
LEFT JOIN subjects s ON dp.subject_id = s.id
LEFT JOIN users u ON dp.teacher_id = u.id;

-- Insert sample data
INSERT INTO diary_pedagogico (grade, subject_id, teacher_id, date, start_time, end_time, student_count, topic, activities, observations, resources, assessment, homework, academic_year) VALUES
('1er Grado', 1, 2, '2024-02-11', '08:00:00', '09:30:00', 25, 'Introducción a las letras', 'Actividades de reconocimiento de vocales, canciones educativas, ejercicios de trazo', 'Los estudiantes mostraron gran interés, algunos necesitaron apoyo adicional en el trazo', 'Pizarra, crayolas, cartillas educativas, audio de canciones', 'Observación directa de participación, ejercicios de trazo evaluados', 'Practicar el trazo de vocales en casa', '2024'),
('1er Grado', 2, 2, '2024-02-11', '10:00:00', '11:30:00', 25, 'Números del 1 al 10', 'Conteo con objetos, juegos numéricos, actividades de reconocimiento', 'Buena participación general, dificultad en algunos estudiantes con el número 6 y 9', 'Bloques de construcción, tarjetas numéricas, pizarra', 'Ejercicios prácticos de conteo, evaluación oral', 'Contar objetos en casa y traer lista', '2024'),
('2do Grado', 1, 3, '2024-02-11', '08:00:00', '09:30:00', 22, 'Lectura comprensiva', 'Lectura de cuentos cortos, preguntas de comprensión, dibujo de historias', 'Algunos estudiantes avanzaron rápidamente, otros necesitaron lectura guiada', 'Libros de cuentos, hojas de trabajo, colores', 'Participación en lectura, respuestas a preguntas, dibujos', 'Leer un cuento corto con los padres', '2024'),
('2do Grado', 3, 3, '2024-02-11', '10:00:00', '11:30:00', 22, 'Sumas básicas', 'Ejercicios de sumas, juegos con dados, resolución de problemas simples', 'Buena comprensión del concepto, algunos errores en cálculos', 'Pizarra, cuadernos, dados, material manipulativo', 'Ejercicios escritos, participación en juegos', 'Practicar 5 sumas en el cuaderno', '2024'),
('3er Grado', 4, 4, '2024-02-11', '08:00:00', '09:30:00', 20, 'El ciclo del agua', 'Explicación con diagramas, experimento simple, dibujo del ciclo', 'Gran interés en el experimento, buena comprensión del proceso', 'Diagramas, vasos, agua, colorante, hojas de trabajo', 'Participación en experimento, dibujos del ciclo, preguntas', 'Investigar sobre el ciclo del agua', '2024');

-- Add comments to table and columns
COMMENT ON TABLE diary_pedagogico IS 'Tabla para el registro diario de actividades pedagógicas por clase';
COMMENT ON COLUMN diary_pedagogico.id IS 'Identificador único del registro';
COMMENT ON COLUMN diary_pedagogico.grade IS 'Grado escolar';
COMMENT ON COLUMN diary_pedagogico.subject_id IS 'ID de la asignatura';
COMMENT ON COLUMN diary_pedagogico.teacher_id IS 'ID del docente';
COMMENT ON COLUMN diary_pedagogico.date IS 'Fecha de la clase';
COMMENT ON COLUMN diary_pedagogico.start_time IS 'Hora de inicio de la clase';
COMMENT ON COLUMN diary_pedagogico.end_time IS 'Hora de fin de la clase';
COMMENT ON COLUMN diary_pedagogico.student_count IS 'Número de estudiantes presentes';
COMMENT ON COLUMN diary_pedagogico.topic IS 'Tema principal de la clase';
COMMENT ON COLUMN diary_pedagogico.activities IS 'Actividades realizadas durante la clase';
COMMENT ON COLUMN diary_pedagogico.observations IS 'Observaciones pedagógicas sobre el desarrollo';
COMMENT ON COLUMN diary_pedagogico.resources IS 'Recursos y materiales utilizados';
COMMENT ON COLUMN diary_pedagogico.assessment IS 'Evaluación formativa realizada';
COMMENT ON COLUMN diary_pedagogico.homework IS 'Tareas asignadas para casa';
COMMENT ON COLUMN diary_pedagogico.academic_year IS 'Año académico';
COMMENT ON COLUMN diary_pedagogico.status IS 'Estado del registro (active, cancelled, postponed)';
COMMENT ON COLUMN diary_pedagogico.created_at IS 'Fecha de creación del registro';
COMMENT ON COLUMN diary_pedagogico.updated_at IS 'Fecha de última actualización';

-- Create a function to get diary entries by filters
CREATE OR REPLACE FUNCTION get_diary_entries_by_filters(
    p_grade VARCHAR DEFAULT NULL,
    p_subject_id INTEGER DEFAULT NULL,
    p_date DATE DEFAULT NULL,
    p_academic_year VARCHAR DEFAULT NULL,
    p_page INTEGER DEFAULT 1,
    p_page_size INTEGER DEFAULT 10
)
RETURNS TABLE(
    total_count BIGINT,
    data JSON
) AS $$
DECLARE
    offset_val INTEGER;
BEGIN
    offset_val := (p_page - 1) * p_page_size;
    
    RETURN QUERY
    SELECT 
        COUNT(*) OVER()::BIGINT as total_count,
        json_agg(
            json_build_object(
                'id', dp.id,
                'grade', dp.grade,
                'subject_id', dp.subject_id,
                'subject_name', s.name,
                'teacher_id', dp.teacher_id,
                'teacher_name', u.first_name || ' ' || u.last_name,
                'date', dp.date,
                'start_time', dp.start_time,
                'end_time', dp.end_time,
                'student_count', dp.student_count,
                'topic', dp.topic,
                'activities', dp.activities,
                'observations', dp.observations,
                'resources', dp.resources,
                'assessment', dp.assessment,
                'homework', dp.homework,
                'academic_year', dp.academic_year,
                'status', dp.status,
                'created_at', dp.created_at,
                'updated_at', dp.updated_at
            )
        ) as data
    FROM diary_pedagogico dp
    LEFT JOIN subjects s ON dp.subject_id = s.id
    LEFT JOIN users u ON dp.teacher_id = u.id
    WHERE 
        (p_grade IS NULL OR dp.grade = p_grade)
        AND (p_subject_id IS NULL OR dp.subject_id = p_subject_id)
        AND (p_date IS NULL OR dp.date = p_date)
        AND (p_academic_year IS NULL OR dp.academic_year = p_academic_year)
        AND dp.status = 'active'
    ORDER BY dp.date DESC, dp.start_time DESC
    LIMIT p_page_size OFFSET offset_val;
END;
$$ LANGUAGE plpgsql;

-- Create audit trigger for diary_pedagogico
CREATE OR REPLACE FUNCTION audit_diary_pedagogico()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES ('diary_pedagogico', 'INSERT', NEW.id, NULL, row_to_json(NEW), NEW.teacher_id, CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES ('diary_pedagogico', 'UPDATE', NEW.id, row_to_json(OLD), row_to_json(NEW), NEW.teacher_id, CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES ('diary_pedagogico', 'DELETE', OLD.id, row_to_json(OLD), NULL, OLD.teacher_id, CURRENT_TIMESTAMP);
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER diary_pedagogico_audit_trigger
    AFTER INSERT OR UPDATE OR DELETE ON diary_pedagogico
    FOR EACH ROW
    EXECUTE FUNCTION audit_diary_pedagogico();
