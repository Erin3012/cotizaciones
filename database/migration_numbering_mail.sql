-- Importar una vez sobre qlccl_cotizaciones previamente respaldada.
CREATE TABLE IF NOT EXISTS number_counters (
 prefix VARCHAR(3) NOT NULL,
 number_year SMALLINT UNSIGNED NOT NULL,
 last_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(prefix,number_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO number_counters(prefix,number_year,last_value)
SELECT LEFT(number,3),CAST(SUBSTRING(number,5,4) AS UNSIGNED),MAX(CAST(SUBSTRING_INDEX(number,'-',-1) AS UNSIGNED))
FROM (
 SELECT quote_number AS number FROM quotes
 UNION ALL SELECT request_number FROM quote_requests
 UNION ALL SELECT JSON_UNQUOTE(JSON_EXTRACT(payload_json,'$.quote_number')) FROM audit_logs WHERE payload_json IS NOT NULL
 UNION ALL SELECT JSON_UNQUOTE(JSON_EXTRACT(payload_json,'$.request_number')) FROM audit_logs WHERE payload_json IS NOT NULL
) AS existing_numbers
WHERE number REGEXP '^(COT|SOL)-[0-9]{4}-[0-9]+$'
GROUP BY LEFT(number,3),SUBSTRING(number,5,4)
ON DUPLICATE KEY UPDATE last_value=GREATEST(last_value,VALUES(last_value));

CREATE TABLE IF NOT EXISTS quote_email_drafts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quote_id BIGINT UNSIGNED NULL,
 quote_number VARCHAR(30) NOT NULL,
 content_hash CHAR(64) NOT NULL,
 quote_hash CHAR(64) NOT NULL,
 message_id VARCHAR(190) NOT NULL UNIQUE,
 mailbox_uid BIGINT UNSIGNED NULL,
 completed TINYINT(1) NOT NULL DEFAULT 0,
 attempted TINYINT(1) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY idx_draft_version(quote_id,content_hash),
 FOREIGN KEY(quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
