--============================================================
-- Mugwe Fish Pond Aquaculture Management System
-- Database Schema v2.0
-- Busolwe Town Council, Butaleja District, Uganda
-- ============================================================

CREATE DATABASE IF NOT EXISTS aquaculture_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aquaculture_system;

-- Users Table (Admin, Farmer, Vet)
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','farmer','vet') NOT NULL,
    phone       VARCHAR(20),
    is_active   TINYINT(1) DEFAULT 1,
    last_login  DATETIME DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
);

-- Ponds Table
CREATE TABLE IF NOT EXISTS ponds (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    location    VARCHAR(255),
    size        DECIMAL(10,2) COMMENT 'Size in square metres',
    depth       DECIMAL(5,2)  COMMENT 'Depth in metres',
    pond_type   ENUM('earthen','lined','concrete','cage') DEFAULT 'earthen',
    status      ENUM('active','inactive','under_maintenance') DEFAULT 'active',
    farmer_id   INT,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_farmer (farmer_id),
    INDEX idx_status (status)
);

-- Water Quality Records
CREATE TABLE IF NOT EXISTS water_quality (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pond_id             INT NOT NULL,
    water_temp          DECIMAL(4,2) COMMENT 'Temperature in Celsius',
    ph_level            DECIMAL(4,2) COMMENT 'pH 0-14',
    dissolved_oxygen    DECIMAL(5,2) COMMENT 'DO in mg/L',
    ammonia             DECIMAL(5,3) DEFAULT NULL,
    turbidity           VARCHAR(20)  DEFAULT NULL,
    recorded_by         INT DEFAULT NULL,
    notes               TEXT,
    recorded_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pond (pond_id),
    INDEX idx_date (recorded_at)
);

-- Fish Stocks
CREATE TABLE IF NOT EXISTS fish_stocks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pond_id         INT NOT NULL,
    species         VARCHAR(50) NOT NULL,
    quantity        INT NOT NULL DEFAULT 0,
    avg_weight      DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Average weight in kg',
    stocking_date   DATE NOT NULL,
    source          VARCHAR(100) DEFAULT NULL,
    cost_per_fish   DECIMAL(10,2) DEFAULT 0.00,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    INDEX idx_pond (pond_id)
);

-- Feed Records
CREATE TABLE IF NOT EXISTS feed_records (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pond_id     INT NOT NULL,
    feed_type   VARCHAR(100) NOT NULL,
    quantity    DECIMAL(10,2) NOT NULL COMMENT 'Quantity in kg',
    cost        DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Cost in UGX',
    feed_date   DATE NOT NULL,
    fed_by      INT DEFAULT NULL,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (fed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pond (pond_id),
    INDEX idx_date (feed_date)
);

-- Harvest Records
CREATE TABLE IF NOT EXISTS harvest_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pond_id         INT NOT NULL,
    quantity        INT NOT NULL COMMENT 'Number of fish harvested',
    avg_weight      DECIMAL(6,2) DEFAULT 0.00 COMMENT 'Average weight in kg',
    total_weight    DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total weight in kg',
    sale_price      DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Price per kg in UGX',
    total_revenue   DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Total revenue in UGX',
    buyer_name      VARCHAR(100) DEFAULT NULL,
    harvest_date    DATE NOT NULL,
    harvested_by    INT DEFAULT NULL,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (harvested_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pond (pond_id),
    INDEX idx_date (harvest_date)
);

-- Health Records (Vet module)
CREATE TABLE IF NOT EXISTS health_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pond_id         INT NOT NULL,
    vet_id          INT DEFAULT NULL,
    visit_date      DATE NOT NULL,
    diagnosis       TEXT NOT NULL,
    treatment       TEXT,
    medication      VARCHAR(200) DEFAULT NULL,
    severity        ENUM('normal','mild','moderate','severe','critical') DEFAULT 'normal',
    follow_up_date  DATE DEFAULT NULL,
    status          ENUM('open','resolved','monitoring') DEFAULT 'open',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (vet_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pond (pond_id),
    INDEX idx_severity (severity),
    INDEX idx_status (status)
);

-- Mortality Records (Vet module)
CREATE TABLE IF NOT EXISTS mortality_records (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pond_id          INT NOT NULL,
    reported_by      INT DEFAULT NULL,
    mortality_date   DATE NOT NULL,
    count            INT NOT NULL DEFAULT 0,
    estimated_weight DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Estimated total weight in kg',
    cause            VARCHAR(200) DEFAULT NULL,
    probable_reason  ENUM('disease','poor_water_quality','predation','oxygen_depletion','unknown','other') DEFAULT 'unknown',
    action_taken     TEXT,
    loss_value       DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Estimated loss in UGX',
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pond (pond_id),
    INDEX idx_date (mortality_date)
);

-- Vet Recommendations
CREATE TABLE IF NOT EXISTS vet_recommendations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pond_id         INT NOT NULL,
    vet_id          INT DEFAULT NULL,
    farmer_id       INT DEFAULT NULL,
    recommendation  TEXT NOT NULL,
    priority        ENUM('low','medium','high','urgent') DEFAULT 'medium',
    category        ENUM('feeding','water_quality','health','stocking','general') DEFAULT 'general',
    status          ENUM('pending','acknowledged','completed','dismissed') DEFAULT 'pending',
    due_date        DATE DEFAULT NULL,
    completed_at    DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (vet_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pond (pond_id),
    INDEX idx_priority (priority),
    INDEX idx_status (status)
);

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(100) NOT NULL,
    token       VARCHAR(6) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used        TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY  unique_token (token),
    INDEX idx_email (email)
);

-- System Activity Logs
CREATE TABLE IF NOT EXISTS system_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT 0,
    action      VARCHAR(100) NOT NULL,
    details     TEXT,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
);

-- ============================================================
-- SEED DATA: Default Admin Account
-- Login: admin / password  (CHANGE IMMEDIATELY after setup!)
-- ============================================================
INSERT INTO users (username, email, password, role, phone) VALUES
('admin', 'admin@mugwefishpond.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+256741733671')
ON DUPLICATE KEY UPDATE id = id;
