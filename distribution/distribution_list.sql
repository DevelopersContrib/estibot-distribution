/*Table structure for table `distribution_list` */
/*Stores csv distribution files from estibot*/

CREATE TABLE `distribution_list` (
  `list_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `filename` varchar(100) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `date_uploaded` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`list_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

