-- Script to create the products table for PostgreSQL

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INTEGER,
    unit_of_measure VARCHAR(50),
    quantity_in_stock INTEGER DEFAULT 0,
    purchase_price DECIMAL(10, 2),
    selling_price DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Trigger function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger to execute the function before any update on products table
CREATE TRIGGER update_products_updated_at
BEFORE UPDATE ON products
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- Indexes for common lookups
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);
CREATE INDEX IF NOT EXISTS idx_products_parent_id ON products(parent_id);

-- Sample data (optional, for testing)
-- INSERT INTO products (name, description, unit_of_measure, quantity_in_stock, purchase_price, selling_price) VALUES
-- ('Laptop Pro', 'High-performance laptop for professionals', 'piece', 50, 800.00, 1200.00),
-- ('Wireless Mouse', 'Ergonomic wireless mouse', 'piece', 200, 15.00, 25.00),
-- ('Office Chair', 'Comfortable ergonomic office chair', 'piece', 30, 100.00, 180.00);
--
-- -- Example of a product with a parent (component)
-- INSERT INTO products (name, description, parent_id, unit_of_measure, quantity_in_stock, purchase_price, selling_price) VALUES
-- ('Laptop Battery', 'Replacement battery for Laptop Pro', 1, 'piece', 20, 50.00, 80.00);

COMMENT ON COLUMN products.parent_id IS 'ID of the parent product, if this is a component or sub-product. NULL if it is a main product.';
COMMENT ON COLUMN products.unit_of_measure IS 'e.g., piece, kg, liter, meter';
COMMENT ON COLUMN products.quantity_in_stock IS 'Current stock level of the product.';
COMMENT ON COLUMN products.purchase_price IS 'Cost price of the product.';
COMMENT ON COLUMN products.selling_price IS 'Retail price of the product.';

-- Script to create the suppliers table for PostgreSQL

CREATE TABLE IF NOT EXISTS suppliers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger function to update updated_at timestamp for suppliers
-- (Could reuse the existing one if named generically, or create a specific one)
-- For clarity, let's assume we might want different trigger logic later, so a new one:
CREATE OR REPLACE FUNCTION update_suppliers_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger to execute the function before any update on suppliers table
CREATE TRIGGER update_suppliers_updated_at
BEFORE UPDATE ON suppliers
FOR EACH ROW
EXECUTE FUNCTION update_suppliers_updated_at_column();

-- Indexes for common lookups
CREATE INDEX IF NOT EXISTS idx_suppliers_name ON suppliers(name);
CREATE INDEX IF NOT EXISTS idx_suppliers_email ON suppliers(email);

COMMENT ON TABLE suppliers IS 'Stores information about product suppliers.';
COMMENT ON COLUMN suppliers.contact_person IS 'Primary contact person at the supplier.';
COMMENT ON COLUMN suppliers.email IS 'Contact email for the supplier, must be unique.';

-- Script to create the clients table for PostgreSQL

CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    client_type VARCHAR(50) DEFAULT 'connu', -- 'connu', 'occasionnel'
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_client_type CHECK (client_type IN ('connu', 'occasionnel'))
);

-- Reusing the generic trigger function 'update_updated_at_column' defined for products table
-- If it's not generic, create a specific one for clients:
-- CREATE OR REPLACE FUNCTION update_clients_updated_at_column()
-- RETURNS TRIGGER AS $$
-- BEGIN
--    NEW.updated_at = NOW();
--    RETURN NEW;
-- END;
-- $$ language 'plpgsql';

-- Trigger to execute the function before any update on clients table
-- Ensuring we use a generic trigger function if available, or create one.
-- The existing 'update_updated_at_column' is suitable.
CREATE TRIGGER update_clients_updated_at
BEFORE UPDATE ON clients
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column(); -- Reusing generic trigger

-- Indexes for common lookups
CREATE INDEX IF NOT EXISTS idx_clients_name ON clients(name);
CREATE INDEX IF NOT EXISTS idx_clients_email ON clients(email);
CREATE INDEX IF NOT EXISTS idx_clients_type ON clients(client_type);

COMMENT ON TABLE clients IS 'Stores information about customers/clients.';
COMMENT ON COLUMN clients.client_type IS 'Type of client: ''connu'' (known/regular) or ''occasionnel'' (occasional).';
COMMENT ON COLUMN clients.email IS 'Contact email for the client, must be unique if provided.';

-- Module d'Approvisionnement

-- Purchase Orders Table
CREATE TABLE IF NOT EXISTS purchase_orders (
    id SERIAL PRIMARY KEY,
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'partially_received', 'received', 'cancelled'
    total_amount DECIMAL(12, 2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_po_status CHECK (status IN ('pending', 'partially_received', 'received', 'cancelled'))
);

CREATE TRIGGER update_purchase_orders_updated_at
BEFORE UPDATE ON purchase_orders
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE INDEX IF NOT EXISTS idx_po_supplier_id ON purchase_orders(supplier_id);
CREATE INDEX IF NOT EXISTS idx_po_status ON purchase_orders(status);

COMMENT ON TABLE purchase_orders IS 'Stores supplier purchase orders.';
COMMENT ON COLUMN purchase_orders.total_amount IS 'Total calculated amount for the order based on its items.';

-- Purchase Order Items Table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id SERIAL PRIMARY KEY,
    purchase_order_id INTEGER NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    quantity_ordered INTEGER NOT NULL CHECK (quantity_ordered > 0),
    unit_price DECIMAL(10, 2) NOT NULL, -- Purchase price at the time of order
    sub_total DECIMAL(12, 2) GENERATED ALWAYS AS (quantity_ordered * unit_price) STORED -- Generated column
);

CREATE INDEX IF NOT EXISTS idx_poi_purchase_order_id ON purchase_order_items(purchase_order_id);
CREATE INDEX IF NOT EXISTS idx_poi_product_id ON purchase_order_items(product_id);

COMMENT ON TABLE purchase_order_items IS 'Individual items within a purchase order.';
COMMENT ON COLUMN purchase_order_items.unit_price IS 'Price of the product at the time the order was placed.';
COMMENT ON COLUMN purchase_order_items.sub_total IS 'Calculated as quantity_ordered * unit_price.';


-- Deliveries Table (Réceptions de livraison)
CREATE TABLE IF NOT EXISTS deliveries (
    id SERIAL PRIMARY KEY,
    purchase_order_id INTEGER REFERENCES purchase_orders(id) ON DELETE SET NULL,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE RESTRICT, -- Required if no PO
    delivery_date DATE NOT NULL,
    is_partial BOOLEAN DEFAULT FALSE,
    notes TEXT,
    type VARCHAR(50) DEFAULT 'purchase', -- 'purchase', 'free_sample', 'return', 'other'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_delivery_type CHECK (type IN ('purchase', 'free_sample', 'return', 'other')),
    CONSTRAINT check_delivery_source CHECK (purchase_order_id IS NOT NULL OR supplier_id IS NOT NULL) -- Must have a PO or a supplier for direct delivery
);

CREATE TRIGGER update_deliveries_updated_at
BEFORE UPDATE ON deliveries
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE INDEX IF NOT EXISTS idx_del_purchase_order_id ON deliveries(purchase_order_id);
CREATE INDEX IF NOT EXISTS idx_del_supplier_id ON deliveries(supplier_id);
CREATE INDEX IF NOT EXISTS idx_del_type ON deliveries(type);

COMMENT ON TABLE deliveries IS 'Records incoming deliveries of products.';
COMMENT ON COLUMN deliveries.type IS 'Nature of the delivery, e.g., regular purchase, free sample, customer return, etc.';

-- Delivery Items Table
CREATE TABLE IF NOT EXISTS delivery_items (
    id SERIAL PRIMARY KEY,
    delivery_id INTEGER NOT NULL REFERENCES deliveries(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    quantity_received INTEGER NOT NULL CHECK (quantity_received > 0),
    purchase_order_item_id INTEGER REFERENCES purchase_order_items(id) ON DELETE SET NULL -- Link to specific PO line item
);

CREATE INDEX IF NOT EXISTS idx_di_delivery_id ON delivery_items(delivery_id);
CREATE INDEX IF NOT EXISTS idx_di_product_id ON delivery_items(product_id);
CREATE INDEX IF NOT EXISTS idx_di_po_item_id ON delivery_items(purchase_order_item_id);

COMMENT ON TABLE delivery_items IS 'Individual items received in a delivery.';
COMMENT ON COLUMN delivery_items.purchase_order_item_id IS 'Links to the specific line item on the purchase order, if applicable.';


-- Supplier Invoices Table
CREATE TABLE IF NOT EXISTS supplier_invoices (
    id SERIAL PRIMARY KEY,
    delivery_id INTEGER REFERENCES deliveries(id) ON DELETE SET NULL,
    purchase_order_id INTEGER REFERENCES purchase_orders(id) ON DELETE SET NULL,
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
    invoice_number VARCHAR(100) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE,
    total_amount DECIMAL(12, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'unpaid', -- 'unpaid', 'paid', 'partially_paid', 'cancelled'
    payment_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_invoice_status CHECK (status IN ('unpaid', 'paid', 'partially_paid', 'cancelled')),
    CONSTRAINT uq_supplier_invoice_number UNIQUE (supplier_id, invoice_number) -- Invoice number should be unique per supplier
);

CREATE TRIGGER update_supplier_invoices_updated_at
BEFORE UPDATE ON supplier_invoices
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE INDEX IF NOT EXISTS idx_si_delivery_id ON supplier_invoices(delivery_id);
CREATE INDEX IF NOT EXISTS idx_si_purchase_order_id ON supplier_invoices(purchase_order_id);
CREATE INDEX IF NOT EXISTS idx_si_supplier_id ON supplier_invoices(supplier_id);
CREATE INDEX IF NOT EXISTS idx_si_status ON supplier_invoices(status);

COMMENT ON TABLE supplier_invoices IS 'Stores supplier invoice details.';
COMMENT ON COLUMN supplier_invoices.invoice_number IS 'Supplier-provided invoice number, unique per supplier.';

-- Module de Vente

-- Sales Table
CREATE TABLE IF NOT EXISTS sales (
    id SERIAL PRIMARY KEY,
    client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
    client_name_occasional VARCHAR(255), -- Used if client_id is NULL
    sale_date DATE NOT NULL,
    total_amount DECIMAL(12, 2) DEFAULT 0.00,
    payment_status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'paid', 'partially_paid', 'refunded'
    payment_type VARCHAR(50) NOT NULL, -- 'immediate', 'deferred'
    due_date DATE, -- Relevant for 'deferred' payment_type
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_sale_client CHECK ((client_id IS NOT NULL AND client_name_occasional IS NULL) OR (client_id IS NULL)), -- Ensure one client type
    CONSTRAINT check_sale_payment_status CHECK (payment_status IN ('pending', 'paid', 'partially_paid', 'refunded', 'cancelled')),
    CONSTRAINT check_sale_payment_type CHECK (payment_type IN ('immediate', 'deferred')),
    CONSTRAINT check_sale_due_date CHECK ((payment_type = 'deferred' AND due_date IS NOT NULL) OR (payment_type = 'immediate'))
);

CREATE TRIGGER update_sales_updated_at
BEFORE UPDATE ON sales
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE INDEX IF NOT EXISTS idx_sales_client_id ON sales(client_id);
CREATE INDEX IF NOT EXISTS idx_sales_payment_status ON sales(payment_status);
CREATE INDEX IF NOT EXISTS idx_sales_payment_type ON sales(payment_type);

COMMENT ON TABLE sales IS 'Stores customer sales records.';
COMMENT ON COLUMN sales.client_name_occasional IS 'Name of the walk-in or occasional client if not a registered client.';
COMMENT ON COLUMN sales.payment_status IS 'Payment status of the sale.';
COMMENT ON COLUMN sales.payment_type IS 'Indicates if payment was immediate or deferred.';
COMMENT ON COLUMN sales.due_date IS 'Due date for deferred payments.';

-- Sale Items Table
CREATE TABLE IF NOT EXISTS sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT, -- Prevent product deletion if sold
    quantity_sold INTEGER NOT NULL CHECK (quantity_sold > 0),
    unit_price DECIMAL(10, 2) NOT NULL, -- Selling price at the time of sale
    sub_total DECIMAL(12, 2) GENERATED ALWAYS AS (quantity_sold * unit_price) STORED
);

CREATE INDEX IF NOT EXISTS idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_product_id ON sale_items(product_id);

COMMENT ON TABLE sale_items IS 'Individual items within a sale.';
COMMENT ON COLUMN sale_items.unit_price IS 'Price of the product at the time of sale.';
COMMENT ON COLUMN sale_items.sub_total IS 'Calculated as quantity_sold * unit_price.';

-- Module de Gestion des Stocks et Mouvements

-- Stock Movements Table
CREATE TABLE IF NOT EXISTS stock_movements (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
    type VARCHAR(50) NOT NULL, -- 'in_delivery', 'out_sale', 'adjustment_in', 'adjustment_out', 'split_in', 'split_out', 'initial_stock'
    quantity INTEGER NOT NULL CHECK (quantity > 0), -- Always positive, type indicates direction
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    related_document_id INTEGER, -- e.g., delivery_items.id, sale_items.id, or null for adjustments
    related_document_type VARCHAR(100), -- e.g., 'delivery_items', 'sale_items', 'stock_adjustments'
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- No updated_at, movements are immutable
);

CREATE INDEX IF NOT EXISTS idx_sm_product_id ON stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_sm_type ON stock_movements(type);
CREATE INDEX IF NOT EXISTS idx_sm_movement_date ON stock_movements(movement_date);
CREATE INDEX IF NOT EXISTS idx_sm_related_doc ON stock_movements(related_document_type, related_document_id);

COMMENT ON TABLE stock_movements IS 'Tracks all movements of stock for each product.';
COMMENT ON COLUMN stock_movements.type IS 'Type of stock movement, e.g., in_delivery, out_sale, adjustment_in, etc.';
COMMENT ON COLUMN stock_movements.quantity IS 'The quantity moved, always positive. The type field indicates direction (in/out).';
COMMENT ON COLUMN stock_movements.related_document_id IS 'ID of the document that triggered this movement (e.g., delivery_item_id, sale_item_id).';
COMMENT ON COLUMN stock_movements.related_document_type IS 'Type of the related document (e.g., ''delivery_items'', ''sale_items'').';
