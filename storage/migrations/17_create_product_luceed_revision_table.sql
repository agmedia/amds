CREATE TABLE `oc_product_luceed_revision` (
`uid` varchar(191) NOT NULL,
`sku` varchar(191) NULL DEFAULT NULL,
`name` varchar(191) NULL DEFAULT NULL,
`has_image` tinyint(1) NOT NULL DEFAULT '0',
`has_description` tinyint(1) NOT NULL DEFAULT '0',
`resolved` tinyint(1) NOT NULL DEFAULT '0',
`data` longtext NULL DEFAULT NULL,
`date_added` datetime NOT NULL,
`date_modified` datetime NOT NULL,
PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;