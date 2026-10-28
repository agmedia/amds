CREATE TABLE IF NOT EXISTS `oc_welcome_coupon` (
  `welcome_coupon_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email_hash` char(64) NOT NULL,
  `coupon_id` int(11) unsigned DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  `event_sent_at` datetime DEFAULT NULL,
  `last_error` varchar(160) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`welcome_coupon_id`),
  UNIQUE KEY `uniq_welcome_coupon_email_hash` (`email_hash`),
  UNIQUE KEY `uniq_welcome_coupon_coupon_id` (`coupon_id`),
  UNIQUE KEY `uniq_welcome_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
