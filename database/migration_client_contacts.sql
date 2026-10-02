-- Ejecutar una sola vez en qlccl_cotizaciones, después de un respaldo.
-- Antes, revisar duplicados de RUT según las instrucciones en CONTACTOS.md.
ALTER TABLE clients ADD COLUMN rut_key VARCHAR(20)
 GENERATED ALWAYS AS (REPLACE(REPLACE(REPLACE(UPPER(rut),'.',''),'-',''),' ','')) STORED UNIQUE;
CREATE TABLE IF NOT EXISTS client_contacts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(150) NOT NULL,
 area VARCHAR(150) NULL,
 email VARCHAR(190) NOT NULL DEFAULT '',
 phone VARCHAR(60) NOT NULL DEFAULT '',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
 INDEX idx_contacts_client (client_id,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE quotes ADD COLUMN contact_id BIGINT UNSIGNED NULL,
 ADD COLUMN contact_area VARCHAR(150) NULL,
 ADD CONSTRAINT fk_quotes_contact FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL;
INSERT INTO client_contacts (client_id,name,email,phone)
 SELECT c.id,c.contact_name,c.email,c.phone FROM clients c
 WHERE TRIM(c.contact_name)<>'' AND NOT EXISTS (SELECT 1 FROM client_contacts cc WHERE cc.client_id=c.id);
-- Congelar los datos históricos que antes dependían de la ficha del cliente.
UPDATE quotes q JOIN quote_requests r ON r.id=q.request_id JOIN clients c ON c.id=r.client_id
 SET q.attention_name=COALESCE(q.attention_name,c.contact_name),
 q.client_email=COALESCE(q.client_email,c.email),q.client_phone=COALESCE(q.client_phone,c.phone);
