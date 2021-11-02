ALTER TABLE `oc_product`
ADD COLUMN `imported` INT(5) NULL DEFAULT '0' AFTER `updated`;