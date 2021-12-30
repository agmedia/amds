ALTER TABLE `oc_order`
ADD COLUMN `luceed_raspis_uid` VARCHAR(255) NULL DEFAULT NULL AFTER `luceed_uid`,
CHANGE COLUMN `order_status_changed` `order_status_changed` DATETIME NULL DEFAULT NULL;