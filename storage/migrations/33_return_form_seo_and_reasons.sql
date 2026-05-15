SET @hr_language_id := (
    SELECT language_id
    FROM oc_language
    WHERE code = 'hr-hr'
    LIMIT 1
);

SET @schema := DATABASE();
SET @old_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'STRICT_TRANS_TABLES', ''), ',,', ',');

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'invoice_number') = 0,
    'ALTER TABLE oc_return ADD COLUMN invoice_number VARCHAR(64) NOT NULL DEFAULT '''' AFTER order_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'invoice_date') = 0,
    'ALTER TABLE oc_return ADD COLUMN invoice_date DATE NULL AFTER invoice_number',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'refund_iban') = 0,
    'ALTER TABLE oc_return ADD COLUMN refund_iban VARCHAR(64) NOT NULL DEFAULT '''' AFTER telephone',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'oc_return' AND COLUMN_NAME = 'return_items') = 0,
    'ALTER TABLE oc_return ADD COLUMN return_items TEXT NULL AFTER quantity',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELETE FROM oc_seo_url
WHERE query = 'account/return/add'
   OR keyword = 'obrazac-za-povrat';

INSERT INTO oc_seo_url (store_id, language_id, query, keyword)
VALUES (0, COALESCE(@hr_language_id, 2), 'account/return/add', 'obrazac-za-povrat');

UPDATE oc_setting
SET value = '1'
WHERE store_id = 0
  AND code = 'config'
  AND `key` = 'config_seo_url';

INSERT INTO oc_return_reason (return_reason_id, language_id, name)
VALUES
    (1, COALESCE(@hr_language_id, 2), 'Preveliko'),
    (2, COALESCE(@hr_language_id, 2), 'Premalo'),
    (3, COALESCE(@hr_language_id, 2), 'Predugo'),
    (4, COALESCE(@hr_language_id, 2), 'Prekratko'),
    (5, COALESCE(@hr_language_id, 2), 'Razlikuje se od proizvoda na slici'),
    (6, COALESCE(@hr_language_id, 2), 'Kvaliteta proizvoda'),
    (7, COALESCE(@hr_language_id, 2), 'Pogrešan proizvod'),
    (8, COALESCE(@hr_language_id, 2), 'Ne stoji mi dobro')
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET SESSION sql_mode = @old_sql_mode;
