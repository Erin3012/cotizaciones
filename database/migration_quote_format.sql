-- Ejecutar una vez sobre instalaciones existentes.
ALTER TABLE quotes
  ADD COLUMN attention_name VARCHAR(150) NULL AFTER expiry_date,
  ADD COLUMN client_phone VARCHAR(60) NULL AFTER attention_name,
  ADD COLUMN client_email VARCHAR(190) NULL AFTER client_phone,
  ADD COLUMN delivery_location VARCHAR(255) NULL AFTER client_email,
  ADD COLUMN delivery_term VARCHAR(180) NULL AFTER delivery_location,
  ADD COLUMN payment_method VARCHAR(180) NULL AFTER delivery_term;
