-- Run once before assigning manual priorities (0 = featured, 1 = normal).
-- Save existing values so the normalization can be reversed if needed.
CREATE TABLE oc_product_sort_order_backup_20261022 AS
SELECT product_id, sort_order FROM oc_product;

UPDATE oc_product SET sort_order = 1;

-- Default for products inserted without an explicit priority.
ALTER TABLE oc_product MODIFY sort_order INT NOT NULL DEFAULT 1;
