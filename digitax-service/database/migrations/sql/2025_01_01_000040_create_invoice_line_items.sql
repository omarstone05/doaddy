CREATE TABLE invoice_line_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tax_invoice_id BIGINT UNSIGNED NOT NULL,
    tax_item_id BIGINT UNSIGNED NOT NULL,
    description TEXT,
    quantity DECIMAL(15, 2) NOT NULL,
    unit_price DECIMAL(15, 2) NOT NULL,
    tax_rate DECIMAL(5, 2),
    tax_amount DECIMAL(15, 2) NOT NULL,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    line_total DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tax_invoice_id) REFERENCES tax_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (tax_item_id) REFERENCES tax_items(id) ON DELETE RESTRICT
);
