-- Run before deploying the coupon allocation code. Change oc_ if using another prefix.
-- Existing orders retain NULL and continue to use their original Luceed export.
SET @coupon_allocation_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_order_total'
       AND COLUMN_NAME = 'coupon_allocation') = 0,
    'ALTER TABLE `oc_order_total` ADD COLUMN `coupon_allocation` MEDIUMTEXT NULL AFTER `sort_order`',
    'SELECT 1'
);
PREPARE coupon_allocation_statement FROM @coupon_allocation_sql;
EXECUTE coupon_allocation_statement;
DEALLOCATE PREPARE coupon_allocation_statement;
