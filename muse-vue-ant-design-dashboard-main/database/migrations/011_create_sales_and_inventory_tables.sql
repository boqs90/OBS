-- Migration: Create sales and inventory management tables
-- Description: Tables for managing inventory, sales, and stock control
-- Created: 2026-02-11

-- Create inventory_products table
CREATE TABLE IF NOT EXISTS inventory_products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    sku VARCHAR(100) UNIQUE,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INTEGER NOT NULL DEFAULT 0,
    min_stock INTEGER NOT NULL DEFAULT 5,
    max_stock INTEGER NOT NULL DEFAULT 1000,
    unit VARCHAR(50) NOT NULL DEFAULT 'unidad',
    supplier VARCHAR(255),
    barcode VARCHAR(100),
    image_url VARCHAR(500),
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for inventory_products
CREATE INDEX IF NOT EXISTS idx_inventory_products_category ON inventory_products(category);
CREATE INDEX IF NOT EXISTS idx_inventory_products_sku ON inventory_products(sku);
CREATE INDEX IF NOT EXISTS idx_inventory_products_supplier ON inventory_products(supplier);
CREATE INDEX IF NOT EXISTS idx_inventory_products_active ON inventory_products(is_active);

-- Create sales table
CREATE TABLE IF NOT EXISTS sales (
    id SERIAL PRIMARY KEY,
    sale_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_contact VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    customer_address TEXT,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create sale_items table
CREATE TABLE IF NOT EXISTS sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES inventory_products(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create inventory_movements table for stock tracking
CREATE TABLE IF NOT EXISTS inventory_movements (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES inventory_products(id) ON DELETE RESTRICT,
    movement_type VARCHAR(20) NOT NULL CHECK (movement_type IN ('in', 'out', 'adjustment')),
    quantity INTEGER NOT NULL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    reason VARCHAR(255),
    previous_stock INTEGER NOT NULL DEFAULT 0,
    new_stock INTEGER NOT NULL DEFAULT 0,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create inventory_adjustments table
CREATE TABLE IF NOT EXISTS inventory_adjustments (
    id SERIAL PRIMARY KEY,
    adjustment_number VARCHAR(50) UNIQUE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    approved_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create inventory_adjustment_items table
CREATE TABLE IF NOT EXISTS inventory_adjustment_items (
    id SERIAL PRIMARY KEY,
    adjustment_id INTEGER NOT NULL REFERENCES inventory_adjustments(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES inventory_products(id) ON DELETE RESTRICT,
    current_stock INTEGER NOT NULL DEFAULT 0,
    expected_stock INTEGER NOT NULL DEFAULT 0,
    difference INTEGER NOT NULL DEFAULT 0,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for sales tables
CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(created_at);
CREATE INDEX IF NOT EXISTS idx_sales_status ON sales(status);
CREATE INDEX IF NOT EXISTS idx_sales_payment_status ON sales(payment_status);
CREATE INDEX IF NOT EXISTS idx_sales_customer ON sales(customer_name);
CREATE INDEX IF NOT EXISTS idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_product_id ON sale_items(product_id);
CREATE INDEX IF NOT EXISTS idx_inventory_movements_product_id ON inventory_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_inventory_movements_date ON inventory_movements(created_at);
CREATE INDEX IF NOT EXISTS idx_inventory_adjustments_status ON inventory_adjustments(status);

-- Create trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_timestamps()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER inventory_products_updated_at_trigger
    BEFORE UPDATE ON inventory_products
    FOR EACH ROW
    EXECUTE FUNCTION update_timestamps();

CREATE TRIGGER sales_updated_at_trigger
    BEFORE UPDATE ON sales
    FOR EACH ROW
    EXECUTE FUNCTION update_timestamps();

CREATE TRIGGER inventory_adjustments_updated_at_trigger
    BEFORE UPDATE ON inventory_adjustments
    FOR EACH ROW
    EXECUTE FUNCTION update_timestamps();

-- Create trigger for automatic stock movement on sales
CREATE OR REPLACE FUNCTION create_sale_stock_movement()
RETURNS TRIGGER AS $$
DECLARE
    v_product_id INTEGER;
    v_quantity INTEGER;
    v_current_stock INTEGER;
BEGIN
    -- Create stock movement for each sale item
    FOR v_product_id, v_quantity IN 
        SELECT product_id, quantity FROM sale_items WHERE sale_id = NEW.id
    LOOP
        -- Get current stock
        SELECT stock INTO v_current_stock FROM inventory_products WHERE id = v_product_id;
        
        -- Create inventory movement
        INSERT INTO inventory_movements (
            product_id, movement_type, quantity, reference_type, reference_id,
            previous_stock, new_stock, reason, created_by
        ) VALUES (
            v_product_id, 'out', v_quantity, 'sale', NEW.id,
            v_current_stock, v_current_stock - v_quantity,
            'Venta #' || NEW.sale_number, NEW.created_by
        );
        
        -- Update product stock
        UPDATE inventory_products 
        SET stock = stock - v_quantity, updated_at = CURRENT_TIMESTAMP
        WHERE id = v_product_id;
    END LOOP;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER sale_stock_movement_trigger
    AFTER INSERT ON sales
    FOR EACH ROW
    EXECUTE FUNCTION create_sale_stock_movement();

-- Create view for sales with items
CREATE OR REPLACE VIEW sales_details AS
SELECT 
    s.id,
    s.sale_number,
    s.customer_name,
    s.customer_contact,
    s.customer_email,
    s.customer_phone,
    s.customer_address,
    s.payment_method,
    s.payment_status,
    s.status,
    s.subtotal,
    s.tax_amount,
    s.discount_amount,
    s.total_amount,
    s.paid_amount,
    s.change_amount,
    s.notes,
    s.created_by,
    s.created_at,
    s.updated_at,
    u.first_name || ' ' || u.last_name as created_by_name,
    COUNT(si.id) as item_count,
    SUM(si.quantity) as total_quantity
FROM sales s
LEFT JOIN sale_items si ON s.id = si.sale_id
LEFT JOIN users u ON s.created_by = u.id
GROUP BY s.id, u.first_name, u.last_name;

-- Create view for inventory with stock status
CREATE OR REPLACE VIEW inventory_status AS
SELECT 
    ip.id,
    ip.name,
    ip.description,
    ip.category,
    ip.sku,
    ip.price,
    ip.cost,
    ip.stock,
    ip.min_stock,
    ip.max_stock,
    ip.unit,
    ip.supplier,
    ip.barcode,
    ip.image_url,
    ip.is_active,
    ip.created_at,
    ip.updated_at,
    CASE 
        WHEN ip.stock <= 0 THEN 'out_of_stock'
        WHEN ip.stock <= ip.min_stock THEN 'low_stock'
        WHEN ip.stock >= ip.max_stock THEN 'overstock'
        ELSE 'in_stock'
    END as stock_status,
    (ip.price - ip.cost) as profit_margin,
    ROUND(((ip.price - ip.cost) / ip.cost * 100), 2) as profit_percentage
FROM inventory_products ip;

-- Create function to generate sale number
CREATE OR REPLACE FUNCTION generate_sale_number()
RETURNS TEXT AS $$
DECLARE
    v_date TEXT;
    v_sequence INTEGER;
    v_sale_number TEXT;
BEGIN
    v_date := TO_CHAR(NOW(), 'YYYYMMDD');
    
    -- Get last sequence for today
    SELECT COALESCE(MAX(CAST(SUBSTRING(sale_number FROM 9) AS INTEGER)), 0) + 1
    INTO v_sequence
    FROM sales
    WHERE sale_number LIKE 'SAL-' || v_date || '-%';
    
    v_sale_number := 'SAL-' || v_date || '-' || LPAD(v_sequence::TEXT, 4, '0');
    
    RETURN v_sale_number;
END;
$$ LANGUAGE plpgsql;

-- Create function to generate adjustment number
CREATE OR REPLACE FUNCTION generate_adjustment_number()
RETURNS TEXT AS $$
DECLARE
    v_date TEXT;
    v_sequence INTEGER;
    v_adjustment_number TEXT;
BEGIN
    v_date := TO_CHAR(NOW(), 'YYYYMMDD');
    
    -- Get last sequence for today
    SELECT COALESCE(MAX(CAST(SUBSTRING(adjustment_number FROM 9) AS INTEGER)), 0) + 1
    INTO v_sequence
    FROM inventory_adjustments
    WHERE adjustment_number LIKE 'ADJ-' || v_date || '-%';
    
    v_adjustment_number := 'ADJ-' || v_date || '-' || LPAD(v_sequence::TEXT, 4, '0');
    
    RETURN v_adjustment_number;
END;
$$ LANGUAGE plpgsql;

-- Insert sample inventory products
INSERT INTO inventory_products (name, description, category, sku, price, cost, stock, min_stock, max_stock, unit, supplier, barcode, is_active) VALUES
('Uniforme Escolar Primaria', 'Uniforme completo para primaria', 'Uniformes', 'UNI-001', 500.00, 350.00, 50, 10, 200, 'conjunto', 'Uniformes Escolares S.A.', '1234567890123', true),
('Cuadernos 100 Hojas', 'Cuadernos universitarios de 100 hojas', 'Útiles', 'UTI-001', 25.00, 18.00, 200, 50, 500, 'unidad', 'Papelería Central', '1234567890124', true),
('Libro de Matemáticas 1er Grado', 'Libro de texto oficial', 'Libros', 'LIB-001', 150.00, 120.00, 30, 5, 100, 'unidad', 'Editorial Educativa', '1234567890125', true),
('Lápices Grafito #2', 'Caja de 12 lápices grafito', 'Materiales', 'MAT-001', 45.00, 30.00, 100, 20, 300, 'caja', 'Artículos Escolares', '1234567890126', true),
('Mochila Escolar', 'Mochila resistente con múltiples compartimentos', 'Otros', 'OTR-001', 350.00, 250.00, 25, 5, 100, 'unidad', 'Accesorios Escolares', '1234567890127', true),
('Borrador Blanco', 'Paquete de 6 borradores blancos', 'Materiales', 'MAT-002', 15.00, 10.00, 150, 30, 400, 'paquete', 'Papelería Central', '1234567890128', true),
('Regla 30cm', 'Regla de plástico de 30cm', 'Útiles', 'UTI-002', 8.00, 5.00, 300, 50, 800, 'unidad', 'Artículos Escolares', '1234567890129', true),
('Colores 12 Unidades', 'Caja de colores de 12 unidades', 'Materiales', 'MAT-003', 35.00, 25.00, 80, 15, 250, 'caja', 'Artículos Escolares', '1234567890130', true);

-- Insert sample sales
INSERT INTO sales (sale_number, customer_name, customer_contact, customer_email, customer_phone, payment_method, payment_status, status, subtotal, tax_amount, discount_amount, total_amount, paid_amount, change_amount, notes, created_by) VALUES
('SAL-20260211-0001', 'Juan Pérez', 'juan.perez@email.com', 'juan.perez@email.com', '98765432', 'cash', 'paid', 'completed', 500.00, 75.00, 0.00, 575.00, 600.00, 25.00, 'Cliente satisfecho', 1),
('SAL-20260211-0002', 'María González', 'maria.g@email.com', 'maria.g@email.com', '87654321', 'card', 'paid', 'completed', 150.00, 22.50, 10.00, 162.50, 162.50, 0.00, 'Pago con tarjeta', 1),
('SAL-20260211-0003', 'Carlos Rodríguez', 'carlos.r@email.com', 'carlos.r@email.com', '76543210', 'transfer', 'pending', 'pending', 350.00, 52.50, 0.00, 402.50, 0.00, 0.00, 'Espera transferencia', 1);

-- Insert sample sale items
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, discount_percentage, subtotal) VALUES
(1, 1, 1, 500.00, 0.00, 500.00),
(2, 3, 1, 150.00, 0.00, 150.00),
(3, 5, 1, 350.00, 0.00, 350.00);

-- Insert sample inventory movements
INSERT INTO inventory_movements (product_id, movement_type, quantity, reference_type, reference_id, previous_stock, new_stock, reason, created_by) VALUES
(1, 'out', 1, 'sale', 1, 50, 49, 'Venta #SAL-20260211-0001', 1),
(3, 'out', 1, 'sale', 2, 30, 29, 'Venta #SAL-20260211-0002', 1),
(5, 'out', 1, 'sale', 3, 25, 24, 'Venta #SAL-20260211-0003', 1);

-- Add comments to tables
COMMENT ON TABLE inventory_products IS 'Catálogo de productos del inventario';
COMMENT ON TABLE sales IS 'Registro de ventas realizadas';
COMMENT ON TABLE sale_items IS 'Detalles de productos vendidos en cada venta';
COMMENT ON TABLE inventory_movements IS 'Movimientos de stock (entradas, salidas, ajustes)';
COMMENT ON TABLE inventory_adjustments IS 'Ajustes de inventario por discrepancias';
COMMENT ON TABLE inventory_adjustment_items IS 'Detalles de los ajustes de inventario';

-- Create function to get low stock products
CREATE OR REPLACE FUNCTION get_low_stock_products()
RETURNS TABLE(
    id INTEGER,
    name VARCHAR,
    category VARCHAR,
    current_stock INTEGER,
    min_stock INTEGER,
    stock_difference INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        ip.id,
        ip.name,
        ip.category,
        ip.stock as current_stock,
        ip.min_stock,
        ip.stock - ip.min_stock as stock_difference
    FROM inventory_products ip
    WHERE ip.stock <= ip.min_stock AND ip.is_active = true
    ORDER BY ip.stock ASC;
END;
$$ LANGUAGE plpgsql;

-- Create function to get sales statistics
CREATE OR REPLACE FUNCTION get_sales_statistics(
    p_start_date DATE DEFAULT NULL,
    p_end_date DATE DEFAULT NULL
)
RETURNS TABLE(
    total_sales DECIMAL(10,2),
    total_items INTEGER,
    total_orders INTEGER,
    average_order_value DECIMAL(10,2),
    today_sales DECIMAL(10,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_sales,
        COALESCE(SUM(total_quantity), 0) as total_items,
        COALESCE(COUNT(*), 0) as total_orders,
        COALESCE(AVG(total_amount), 0) as average_order_value,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN total_amount ELSE 0 END), 0) as today_sales
    FROM sales_details
    WHERE 
        (p_start_date IS NULL OR DATE(created_at) >= p_start_date)
        AND (p_end_date IS NULL OR DATE(created_at) <= p_end_date)
        AND status = 'completed';
END;
$$ LANGUAGE plpgsql;

-- Create audit triggers
CREATE OR REPLACE FUNCTION audit_inventory_tables()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES (TG_TABLE_NAME, 'INSERT', NEW.id, NULL, row_to_json(NEW), NEW.created_by, CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES (TG_TABLE_NAME, 'UPDATE', NEW.id, row_to_json(OLD), row_to_json(NEW), COALESCE(NEW.updated_by, OLD.created_by), CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO audit_logs (table_name, operation, record_id, old_data, new_data, user_id, created_at)
        VALUES (TG_TABLE_NAME, 'DELETE', OLD.id, row_to_json(OLD), NULL, OLD.created_by, CURRENT_TIMESTAMP);
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Apply audit triggers
CREATE TRIGGER inventory_products_audit_trigger
    AFTER INSERT OR UPDATE OR DELETE ON inventory_products
    FOR EACH ROW
    EXECUTE FUNCTION audit_inventory_tables();

CREATE TRIGGER sales_audit_trigger
    AFTER INSERT OR UPDATE OR DELETE ON sales
    FOR EACH ROW
    EXECUTE FUNCTION audit_inventory_tables();

CREATE TRIGGER inventory_adjustments_audit_trigger
    AFTER INSERT OR UPDATE OR DELETE ON inventory_adjustments
    FOR EACH ROW
    EXECUTE FUNCTION audit_inventory_tables();
