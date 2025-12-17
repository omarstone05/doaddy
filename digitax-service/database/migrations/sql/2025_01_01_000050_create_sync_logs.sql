CREATE TABLE sync_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NOT NULL,
    entity_type ENUM('item', 'stock', 'invoice') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed', 'retry') NOT NULL,
    request_payload JSON,
    response_payload JSON,
    error_details TEXT,
    duration_ms INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
