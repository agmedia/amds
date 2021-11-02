CREATE TABLE `oc_product_luceed_revision_data` (
`id` bigint(20) NOT NULL AUTO_INCREMENT,
`last_revision_date` datetime NOT NULL,
`data` longtext NULL DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;