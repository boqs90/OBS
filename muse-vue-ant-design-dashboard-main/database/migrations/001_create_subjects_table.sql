-- Migration: Create subjects table
-- Description: Create table for managing subjects/courses
-- Created: 2024-02-11

CREATE TABLE subjects (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    level VARCHAR(50) NOT NULL CHECK (level IN ('primaria', 'secundaria', 'bachillerato')),
    credits INTEGER DEFAULT 1 CHECK (credits >= 0 AND credits <= 10),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster lookups
CREATE INDEX idx_subjects_level ON subjects(level);
CREATE INDEX idx_subjects_status ON subjects(status);
CREATE INDEX idx_subjects_code ON subjects(code);

-- Insert default subjects
INSERT INTO subjects (name, code, description, level, credits, status) VALUES
('Matemáticas', 'MAT101', 'Matemáticas básicas para primaria', 'primaria', 5, 'active'),
('Lenguaje', 'LEN101', 'Lectura y escritura en español', 'primaria', 4, 'active'),
('Ciencias', 'SCI101', 'Ciencias naturales básicas', 'primaria', 3, 'active'),
('Estudios Sociales', 'SOC101', 'Historia y geografía', 'primaria', 3, 'active'),
('Inglés', 'ENG101', 'Inglés como segundo idioma', 'primaria', 2, 'active'),
('Educación Física', 'EDF101', 'Actividades físicas y deportes', 'primaria', 2, 'active'),
('Arte', 'ART101', 'Expresión artística y creatividad', 'primaria', 1, 'active'),
('Matemáticas Avanzadas', 'MAT201', 'Álgebra, geometría y trigonometría', 'secundaria', 6, 'active'),
('Lenguaje y Literatura', 'LEN201', 'Análisis literario y composición', 'secundaria', 5, 'active'),
('Biología', 'BIO201', 'Estudio de los seres vivos', 'secundaria', 4, 'active'),
('Química', 'QUI201', 'Fundamentos de química', 'secundaria', 4, 'active'),
('Física', 'FIS201', 'Principios de física clásica', 'secundaria', 4, 'active'),
('Historia Universal', 'HIS201', 'Historia mundial y nacional', 'secundaria', 3, 'active'),
('Geografía', 'GEO201', 'Geografía física y humana', 'secundaria', 3, 'active'),
('Cálculo Diferencial', 'CAL301', 'Cálculo y análisis matemático', 'bachillerato', 6, 'active'),
('Álgebra Lineal', 'ALG301', 'Sistemas de ecuaciones lineales', 'bachillerato', 5, 'active'),
('Literatura Universal', 'LIT301', 'Literatura mundial y comparada', 'bachillerato', 4, 'active'),
('Filosofía', 'FIL301', 'Introducción a la filosofía', 'bachillerato', 3, 'active'),
('Economía', 'ECO301', 'Principios básicos de economía', 'bachillerato', 4, 'active');

-- Add comments
COMMENT ON TABLE subjects IS 'Tabla de asignaturas del sistema escolar OBS';
