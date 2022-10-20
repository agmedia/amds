ALTER TABLE `oc_product`
    ADD COLUMN `price_ponuda` DECIMAL(15,4) NOT NULL DEFAULT '0.0000' AFTER `price`;