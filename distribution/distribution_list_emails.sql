/*Table structure for table `distribution_list_emails` */
/*Stores parse results*/
DROP TABLE IF EXISTS `distribution_list_emails`;

CREATE TABLE `distribution_list_emails` (
  `email_id` bigint(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(200) DEFAULT NULL,
  `owner` varchar(200) DEFAULT NULL,
  `domains` varchar(200) DEFAULT NULL,
  `list_id` bigint(11) DEFAULT NULL,
  PRIMARY KEY (`email_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
