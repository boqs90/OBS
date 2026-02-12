-- Migration: Create communications table
-- Description: Create table for managing communications and messaging
-- Created: 2024-02-11

CREATE TABLE communications (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    recipient_type VARCHAR(20) NOT NULL CHECK (recipient_type IN ('parents', 'teachers', 'employees', 'all')),
    grades TEXT[], -- Array of grades for targeted communication
    message TEXT NOT NULL,
    channels VARCHAR(20)[] NOT NULL CHECK (channels IN ('whatsapp', 'email')),
    priority VARCHAR(20) DEFAULT 'normal' CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'scheduled', 'sent', 'failed')),
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP,
    custom_recipients JSONB, -- Array of custom recipients with type and value
    attachments JSONB, -- Array of file attachments
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

-- Create indexes for faster lookups
CREATE INDEX idx_communications_status ON communications(status);
CREATE INDEX idx_communications_recipient_type ON communications(recipient_type);
CREATE INDEX idx_communications_priority ON communications(priority);
CREATE INDEX idx_communications_scheduled_at ON communications(scheduled_at);
CREATE INDEX idx_communications_created_by ON communications(created_by);

-- Create communication recipients table for tracking
CREATE TABLE communication_recipients (
    id SERIAL PRIMARY KEY,
    communication_id INTEGER NOT NULL REFERENCES communications(id) ON DELETE CASCADE,
    recipient_type VARCHAR(20) NOT NULL CHECK (recipient_type IN ('parent', 'teacher', 'employee', 'custom')),
    recipient_id INTEGER, -- Reference to users table for teachers/employees, or custom contact
    recipient_email VARCHAR(255),
    recipient_phone VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'delivered', 'failed', 'bounced')),
    sent_at TIMESTAMP,
    delivered_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for recipients tracking
CREATE INDEX idx_communication_recipients_communication_id ON communication_recipients(communication_id);
CREATE INDEX idx_communication_recipients_status ON communication_recipients(status);
CREATE INDEX idx_communication_recipients_type ON communication_recipients(recipient_type);

-- Create communication attachments table
CREATE TABLE communication_attachments (
    id SERIAL PRIMARY KEY,
    communication_id INTEGER NOT NULL REFERENCES communications(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_size BIGINT,
    file_type VARCHAR(100),
    file_path VARCHAR(500),
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for attachments
CREATE INDEX idx_communication_attachments_communication_id ON communication_attachments(communication_id);

-- Insert sample communications
INSERT INTO communications (title, recipient_type, grades, message, channels, priority, status, created_by) VALUES
('Bienvenida al Nuevo Año Escolar', 'all', ARRAY['pre-kinder', 'kinder', '1ro', '2do', '3ro', '4to', '5to', '6to'], 
'Estimados padres de familia y estudiantes: ¡Les damos la más cordial bienvenida al nuevo año escolar 2024! Estamos emocionados de compartir con ustedes esta nueva etapa llena de aprendizaje y descubrimientos. Nuestro equipo docente está preparado para ofrecerles la mejor educación posible. Pronto estarán recibiendo información importante sobre las fechas de inicio, horarios de clases y materiales necesarios. ¡Les deseamos un año escolar exitoso y lleno de logros!', 
ARRAY['whatsapp', 'email'], 'normal', 'sent', 1),

('Reunión de Padres de Familia - 1er Grado', '1ro', ARRAY['1ro'], 
'Estimados padres del primer grado: Les invitamos a nuestra reunión de padres de familia que se realizará el próximo viernes 15 de febrero a las 2:00 PM en el salón de usos múltiples. En esta reunión discutiremos el plan de estudios del primer grado, las metas de aprendizaje para este semestre y las actividades extracurriculares disponibles. Es importante la asistencia de al menos un representante por familia. Agradecemos de antemano su puntualidad y colaboración.', 
ARRAY['whatsapp', 'email'], 'high', 'draft', 1),

('Recordatorio de Pago de Matrícula', 'parents', ARRAY['kinder', '1ro', '2do', '3ro', '4to', '5to', '6to'], 
'Estimados padres de familia: Les recordamos que la fecha límite para completar el pago de matrícula del año escolar 2024 es el próximo viernes 28 de febrero. Para evitar recargos y asegurar la reserva de cupo de sus hijos, les solicitamos realizar el pago a la brevedad posible. Pueden realizarlo a través de nuestro portal en línea, en la oficina de administración o mediante transferencia bancaria. Si ya realizaron el pago, por favor ignore este mensaje. Para cualquier consulta, no duden en contactarnos. ¡Gracias!', 
ARRAY['whatsapp', 'email'], 'high', 'draft', 1),

('Capacitación Docente - Uso de Plataforma Digital', 'teachers', ARRAY['1ro', '2do', '3ro', '4to', '5to', '6to'], 
'Estimados docentes: Les informamos que este sábado 17 de febrero a las 9:00 AM realizaremos una capacitación obligatoria sobre el uso de nuestra nueva plataforma digital de gestión académica. La capacitación se realizará en el laboratorio de computación y tendrá una duración de 3 horas. Es importante que todos asistan para familiarizarse con las nuevas herramientas que facilitarán su trabajo diario. Por favor confirmar su asistencia enviando un correo a administración@obs-school.com. Les agradecemos su colaboración para mejorar continuamente nuestros procesos educativos.', 
ARRAY['email'], 'normal', 'draft', 1);

-- Add comments
COMMENT ON TABLE communications IS 'Tabla de comunicados del sistema escolar OBS';
COMMENT ON COLUMN communications.recipient_type IS 'Tipo de destinatarios: parents, teachers, employees, all';
COMMENT ON COLUMN communications.channels IS 'Canales de envío: whatsapp, email';
COMMENT ON COLUMN communications.custom_recipients IS 'Destinatarios personalizados en formato JSON';
COMMENT ON COLUMN communications.attachments IS 'Archivos adjuntos en formato JSON';

COMMENT ON TABLE communication_recipients IS 'Tabla de seguimiento de destinatarios de comunicados';
COMMENT ON TABLE communication_attachments IS 'Tabla de archivos adjuntos de comunicados';
