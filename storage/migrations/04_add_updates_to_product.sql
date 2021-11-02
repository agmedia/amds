ALTER TABLE `oc_product`
ADD COLUMN `updated` INT(5) NULL DEFAULT '0' AFTER `viewed`;