CREATE TABLE businesses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    penda_business_id VARCHAR(255) NOT NULL UNIQUE,
    digitax_business_id VARCHAR(255) UNIQUE,
    name VARCHAR(255) NOT NULL,
    tpin VARCHAR(10) NOT NULL,
    zra_branch_id VARCHAR(3) NOT NULL,
    digitax_api_key TEXT,
    environment ENUM('sandbox', 'production') DEFAULT 'sandbox',
    is_active BOOLEAN DEFAULT true,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_penda_business (penda_business_id),
    INDEX idx_digitax_business (digitax_business_id)
);
