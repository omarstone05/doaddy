CREATE TABLE webhook_events (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    processed BOOLEAN DEFAULT false,
    processing_attempts INT DEFAULT 0,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_processed (processed),
    INDEX idx_entity (entity_type, entity_id)
);
