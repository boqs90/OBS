-- Migration: Create study_plans table
-- Description: Create table for managing study plans/curriculum
-- Created: 2024-02-11

CREATE TABLE study_plans (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    version DECIMAL(3,1) NOT NULL DEFAULT 1.0,
    grade VARCHAR(50) NOT NULL CHECK (grade IN ('pre-kinder', 'kinder', '1ro', '2do', '3ro', '4to', '5to', '6to')),
    academic_year INTEGER NOT NULL,
    description TEXT,
    learning_objectives TEXT NOT NULL,
    competencies TEXT,
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'active', 'archived')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create indexes for faster lookups
CREATE INDEX idx_study_plans_grade ON study_plans(grade);
CREATE INDEX idx_study_plans_status ON study_plans(status);
CREATE INDEX idx_study_plans_academic_year ON study_plans(academic_year);
CREATE INDEX idx_study_plans_created_by ON study_plans(created_by);

-- Insert default study plans
INSERT INTO study_plans (name, version, grade, academic_year, description, learning_objectives, competencies, status, created_by) VALUES
('Plan de Estudios Primaria 2024', 1.0, 'pre-kinder', 2024, 
'Plan de estudios para nivel pre-kinder enfocado en desarrollo socioemocional, habilidades motoras básicas y preparación para la lectoescritura',
'Desarrollar habilidades de socialización, comunicación básica, coordinación motora fina y gruesa, reconocimiento de colores y formas, seguimiento de instrucciones simples',
'Fomentar la autonomía, la curiosidad, el respeto por turnos, la colaboración con pares, el desarrollo del lenguaje a través de juegos y actividades dirigidas', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, 'kinder', 2024, 
'Plan de estudios para nivel kinder enfocado en alfabetización inicial, matemáticas básicas, exploración científica y desarrollo del pensamiento crítico',
'Dominar el alfabeto, reconocer fonemas iniciales, comprender correspondencia grafema-fonema, leer palabras simples, contar hasta 20, identificar patrones numéricos básicos, realizar observaciones simples y formular preguntas',
'Promover el pensamiento lógico-matemático, la comunicación efectiva, el trabajo colaborativo, la resolución pacífica de conflictos y el cuidado del entorno natural', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, '1ro', 2024, 
'Plan de estudios para primer grado enfocado en consolidación de lectoescritura, operaciones matemáticas fundamentales y comprensión lectora',
'Leer fluidamente textos apropiados para la edad, escribir oraciones y párrafos cortos, realizar sumas y restas hasta 20, resolver problemas de suma y resta sencillos, identificar figuras geométricas básicas, expresar ideas y sentimientos de forma clara',
'Desarrollar la comprensión lectora, la expresión escrita, el razonamiento matemático, la creatividad, la responsabilidad en el trabajo escolar y el respeto por las diferencias culturales', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, '2do', 2024, 
'Plan de estudios para segundo grado enfocado en lectura comprensiva, cálculo con llevadas y escritura creativa',
'Leer con entonación y fluidez textos de mayor complejidad, escribir narrativas coherentes, realizar multiplicaciones básicas (tablas del 2, 3, 5, 10), resolver problemas de dos pasos, identificar características de diferentes tipos de textos',
'Fomentar el análisis crítico de textos, la planificación en la escritura, el uso adecuado de estrategias de cálculo, la investigación guiada y la presentación ordenada del trabajo', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, '3ro', 2024, 
'Plan de estudios para tercer grado enfocado en lectura crítica, fracciones y geometría básica',
'Leer y analizar diferentes tipos de textos, identificar propósito y punto de vista del autor, comprender y representar fracciones simples, identificar y clasificar figuras geométricas, resolver problemas de multiplicación y división sencillos',
'Desarrollar el pensamiento analítico, la argumentación basada en evidencia, la representación matemática de situaciones reales, la investigación autónoma y la comunicación efectiva de resultados matemáticos', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, '4to', 2024, 
'Plan de estudios para cuarto grado enfocado en redacción, decimales y ciencias naturales básicas',
'Redactar diferentes tipos de textos (narrativos, expositivos, argumentativos), entender el sistema decimal hasta centésimos, realizar operaciones con decimales, investigar ciclos de vida y ecosistemas, diseñar experimentos simples',
'Perfeccionar la estructura y coherencia en la escritura, aplicar operaciones matemáticas a situaciones reales, desarrollar el método científico básico, comunicar hallazgos de forma clara y organizada', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, '5to', 2024, 
'Plan de estudios para quinto grado enfocado en análisis literario, operaciones complejas y preparación para secundaria',
'Analizar elementos literarios complejos, realizar operaciones con fracciones y decimales, resolver problemas de múltiples pasos, investigar eventos históricos nacionales, comprender la estructura del gobierno y la economía básica',
'Consolidar todas las habilidades básicas de primaria, desarrollar el pensamiento abstracto, fomentar la investigación documentada, preparar para la transición exitosa a la educación secundaria', 'active', 1),

('Plan de Estudios Primaria 2024', 1.0, '6to', 2024, 
'Plan de estudios para sexto grado enfocado en consolidación final y transición a secundaria',
'Reforzar todas las competencias desarrolladas en primaria, introducir conceptos básicos de álgebra y geometría analítica, desarrollar habilidades de investigación y presentación, fomentar la autonomía en el aprendizaje, preparar académica y emocionalmente para la secundaria',
'Dominar los contenidos fundamentales de primaria, desarrollar pensamiento algebraico inicial, aplicar el método científico sistemático, crear proyectos interdisciplinarios, establecer metas de aprendizaje a mediano plazo', 'active', 1);

-- Add comments
COMMENT ON TABLE study_plans IS 'Tabla de planes de estudio del sistema escolar OBS';
