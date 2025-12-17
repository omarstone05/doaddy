CREATE TABLE stock_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tax_item_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('adjustment', 'purchase', 'sale', 'return') NOT NULL,
    quantity DECIMAL(15, 2) NOT NULL,
    unit_price DECIMAL(15, 2),
    reference_id VARCHAR(255),
    digitax_response JSON,
    sync_status ENUM('pending', 'syncing', 'synced', 'failed') DEFAULT 'pending',
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tax_item_id) REFERENCES tax_items(id) ON DELETE CASCADE,
    INDEX idx_reference (reference_id),
    INDEX idx_sync_status (sync_status)
);
