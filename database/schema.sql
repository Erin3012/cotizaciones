-- Base de datos independiente: qlccl_cotizaciones
-- Ejecutar después de seleccionar la base creada en cPanel.

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin') NOT NULL DEFAULT 'admin',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  rut VARCHAR(20) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  address VARCHAR(255) NOT NULL,
  contact_name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clients_rut (rut),
  INDEX idx_clients_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_number VARCHAR(30) NOT NULL UNIQUE,
  client_id BIGINT UNSIGNED NOT NULL,
  service_product VARCHAR(180) NOT NULL,
  technical_description TEXT NOT NULL,
  material VARCHAR(180) NULL,
  quantity DECIMAL(12,3) NULL,
  unit VARCHAR(40) NULL,
  measurements VARCHAR(255) NULL,
  required_deadline DATE NULL,
  observations TEXT NULL,
  status ENUM('received','review','preparation','sent','accepted','rejected','expired') NOT NULL DEFAULT 'received',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
  INDEX idx_requests_status_created (status, created_at),
  INDEX idx_requests_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  relative_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE CASCADE,
  INDEX idx_attachments_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quote_number VARCHAR(30) NOT NULL UNIQUE,
  request_id BIGINT UNSIGNED NOT NULL,
  issue_date DATE NOT NULL,
  expiry_date DATE NOT NULL,
  attention_name VARCHAR(150) NULL,
  client_phone VARCHAR(60) NULL,
  client_email VARCHAR(190) NULL,
  delivery_location VARCHAR(255) NULL,
  delivery_term VARCHAR(180) NULL,
  payment_method VARCHAR(180) NULL,
  status ENUM('received','review','preparation','sent','accepted','rejected','expired') NOT NULL DEFAULT 'preparation',
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 19.00,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES quote_requests(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_quotes_status_date (status, issue_date),
  INDEX idx_quotes_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quote_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
  unit VARCHAR(40) NOT NULL,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  discount_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  line_subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  INDEX idx_items_quote (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('request','quote') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  from_status VARCHAR(30) NULL,
  to_status VARCHAR(30) NOT NULL,
  comment TEXT NULL,
  user_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_history_entity (entity_type, entity_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  payload_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
