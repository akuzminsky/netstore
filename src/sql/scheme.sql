-- MySQL dump 8.22
--
-- Host: localhost    Database: netflow
---------------------------------------------------------
-- Server version	3.23.56

--
-- Table structure for table 'bill'
--

DROP TABLE IF EXISTS `bill`;
CREATE TABLE `bill` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `contract_id` int(11) unsigned NOT NULL default '0',
  `timestamp` date NOT NULL default '0000-00-00',
  `create_timestamp` date NOT NULL default '0000-00-00',
  `operator` varchar(32) NOT NULL default '',
  `pdf_body` mediumblob,
  PRIMARY KEY  (`id`),
  KEY `contract_id` (`contract_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `bill` DISABLE KEYS */;

--
-- Table structure for table 'bill_item'
--

DROP TABLE IF EXISTS `bill_item`;
CREATE TABLE `bill_item` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `bill_id` int(11) unsigned NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  `measure` varchar(8) NOT NULL default 'шт.',
  `kolvo` int(11) unsigned NOT NULL default '1',
  `price` decimal(20,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`),
  KEY `bill_id` (`bill_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `bill_item` DISABLE KEYS */;

--
-- Table structure for table 'bill_value'
--

DROP TABLE IF EXISTS `bill_value`;
CREATE TABLE `bill_value` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `bill_id` int(11) unsigned NOT NULL default '0',
  `provider` text NOT NULL,
  `payer` varchar(255) NOT NULL default '',
  `base` varchar(255) NOT NULL default '',
  `saldo_without_vat` decimal(20,2) NOT NULL default '0.00',
  `saldo_vat` decimal(20,2) NOT NULL default '0.00',
  `saldo_with_vat` decimal(20,2) NOT NULL default '0.00',
  `in_all_without_vat` decimal(20,2) NOT NULL default '0.00',
  `in_all_vat` decimal(20,2) NOT NULL default '0.00',
  `in_all_with_vat` decimal(20,2) NOT NULL default '0.00',
  `to_order_without_vat` decimal(20,2) NOT NULL default '0.00',
  `to_order_vat` decimal(20,2) NOT NULL default '0.00',
  `to_order_with_vat` decimal(20,2) NOT NULL default '0.00',
  `to_order_str` varchar(255) NOT NULL default '',
  `operator_name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `bill_id` (`bill_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `bill_value` DISABLE KEYS */;

--
-- Table structure for table 'charge'
--

DROP TABLE IF EXISTS `charge`;
CREATE TABLE `charge` (
  `id` int(11) NOT NULL auto_increment,
  `service_id` int(11) NOT NULL default '0',
  `timestamp` date NOT NULL default '0000-00-00',
  `value` decimal(20,2) NOT NULL default '0.00',
  `vat` decimal(20,2) NOT NULL default '0.00',
  `value_without_vat` decimal(20,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`),
  KEY `service_id` (`service_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `charge` DISABLE KEYS */;

--
-- Table structure for table 'client'
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE `client` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(16) NOT NULL default '',
  `manager_id` varchar(64) default NULL,
  `full_name` varchar(255) NOT NULL default '',
  `short_name` varchar(255) NOT NULL default '',
  `edrpou` varchar(64) NOT NULL default '',
  `tax_number` varchar(64) NOT NULL default '',
  `licence_number` varchar(64) NOT NULL default '',
  `tac_status` enum('y','n') NOT NULL default 'y',
  `description` varchar(255) NOT NULL default '',
  `person` varchar(255) default NULL,
  `director` varchar(128) default NULL,
  `phone` varchar(255) default NULL,
  `fax` varchar(64) default NULL,
  `email` varchar(255) default NULL,
  `bank_name` varchar(255) NOT NULL default '',
  `mfo` varchar(32) default NULL,
  `account` varchar(64) default NULL,
  `phys_zip` varchar(16) NOT NULL default '',
  `phys_addr_1` varchar(64) NOT NULL default '',
  `phys_addr_2` varchar(64) NOT NULL default '',
  `phys_addr_3` varchar(64) NOT NULL default '',
  `jur_zip` varchar(16) NOT NULL default '',
  `jur_addr_1` varchar(64) NOT NULL default '',
  `jur_addr_2` varchar(64) NOT NULL default '',
  `jur_addr_3` varchar(64) NOT NULL default '',
  `port` varchar(32) default NULL,
  `notes` mediumtext,
  `vpn` enum('y','n') NOT NULL default 'n',
  `activation_time` date NOT NULL default '0000-00-00',
  `active` enum('yes','no') NOT NULL default 'yes',
  `inactivation_time` date default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `description` (`description`),
  KEY `manager_id` (`manager_id`),
  KEY `activation_time` (`activation_time`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `client` DISABLE KEYS */;

--
-- Table structure for table 'client_cluster'
--

DROP TABLE IF EXISTS `client_cluster`;
CREATE TABLE `client_cluster` (
  `id` int(11) NOT NULL auto_increment,
  `client_id` int(11) NOT NULL default '0',
  `cluster_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `client_id` (`client_id`,`cluster_id`),
  KEY `cluster_id` (`cluster_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `client_cluster` DISABLE KEYS */;

--
-- Table structure for table 'client_interface'
--

DROP TABLE IF EXISTS `client_interface`;
CREATE TABLE `client_interface` (
  `id` int(11) NOT NULL auto_increment,
  `client_id` int(11) NOT NULL default '0',
  `interface_id` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cl_id` (`client_id`,`interface_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `client_interface` DISABLE KEYS */;

--
-- Table structure for table 'client_network'
--

DROP TABLE IF EXISTS `client_network`;
CREATE TABLE `client_network` (
  `id` int(11) NOT NULL auto_increment,
  `client_id` int(11) NOT NULL default '0',
  `network` int(11) unsigned NOT NULL default '0',
  `netmask` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `network` (`network`),
  KEY `client_id` (`client_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `client_network` DISABLE KEYS */;

--
-- Table structure for table 'cluster'
--

DROP TABLE IF EXISTS `cluster`;
CREATE TABLE `cluster` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `description` varchar(255) default NULL,
  `equipment` varchar(255) default NULL,
  `network` varchar(255) default NULL,
  `gateway` varchar(255) default NULL,
  `switch` varchar(255) default NULL,
  `scheme` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `description` (`description`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `cluster` DISABLE KEYS */;

--
-- Table structure for table 'config'
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `id` int(11) NOT NULL auto_increment,
  `operator_id` int(11) NOT NULL default '0',
  `attribute` varchar(32) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `operator_id` (`operator_id`),
  KEY `attribute` (`attribute`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `config` DISABLE KEYS */;

--
-- Table structure for table 'contract'
--

DROP TABLE IF EXISTS `contract`;
CREATE TABLE `contract` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `client_id` int(11) unsigned NOT NULL default '0',
  `c_type` varchar(16) NOT NULL default '',
  `c_number` varchar(64) NOT NULL default '',
  `description` varchar(255) default 'Догов╕р про надання послуг',
  `start_time` date NOT NULL default '0000-00-00',
  `expire_time` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`),
  KEY `client_id` (`client_id`),
  KEY `c_number` (`c_type`,`c_number`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `contract` DISABLE KEYS */;

--
-- Table structure for table 'feeding'
--

DROP TABLE IF EXISTS `feeding`;
CREATE TABLE `feeding` (
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  UNIQUE KEY `timestamp` (`timestamp`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `feeding` DISABLE KEYS */;

--
-- Table structure for table 'filter'
--

DROP TABLE IF EXISTS `filter`;
CREATE TABLE `filter` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `client_id` int(11) unsigned NOT NULL default '0',
  `starttimestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `stoptimestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `description` varchar(255) NOT NULL default '',
  `inverse` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `client_id` (`client_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `filter` DISABLE KEYS */;

--
-- Table structure for table 'filter_action'
--

DROP TABLE IF EXISTS `filter_action`;
CREATE TABLE `filter_action` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(11) unsigned NOT NULL default '0',
  `limit` bigint(20) unsigned default NULL,
  `handler` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `filter_id` (`filter_id`,`handler`),
  KEY `handler` (`handler`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `filter_action` DISABLE KEYS */;

--
-- Table structure for table 'filter_counter'
--

DROP TABLE IF EXISTS `filter_counter`;
CREATE TABLE `filter_counter` (
  `id` int(11) NOT NULL auto_increment,
  `filter_id` int(11) NOT NULL default '0',
  `incoming` bigint(20) NOT NULL default '0',
  `outcoming` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `filter_id` (`filter_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `filter_counter` DISABLE KEYS */;

--
-- Table structure for table 'filter_counter_snapshot'
--

DROP TABLE IF EXISTS `filter_counter_snapshot`;
CREATE TABLE `filter_counter_snapshot` (
  `id` int(11) NOT NULL auto_increment,
  `filter_id` int(11) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `incoming` bigint(20) NOT NULL default '0',
  `outcoming` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `filter_id` (`filter_id`),
  KEY `timestamp` (`timestamp`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `filter_counter_snapshot` DISABLE KEYS */;

--
-- Table structure for table 'filter_definition'
--

DROP TABLE IF EXISTS `filter_definition`;
CREATE TABLE `filter_definition` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(11) unsigned NOT NULL default '0',
  `timerange` varchar(255) NOT NULL default '',
  `proto` smallint(11) unsigned NOT NULL default '0',
  `in_if` smallint(11) unsigned NOT NULL default '0',
  `out_if` smallint(11) unsigned NOT NULL default '0',
  `src_addr` int(11) unsigned NOT NULL default '0',
  `src_mask` int(11) unsigned NOT NULL default '0',
  `dst_addr` int(11) unsigned NOT NULL default '0',
  `dst_mask` int(11) unsigned NOT NULL default '0',
  `src_port` smallint(11) unsigned NOT NULL default '0',
  `dst_port` smallint(11) unsigned NOT NULL default '0',
  `src_as` smallint(11) unsigned NOT NULL default '0',
  `dst_as` smallint(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `filter_id` (`filter_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `filter_definition` DISABLE KEYS */;

--
-- Table structure for table 'filter_handler'
--

DROP TABLE IF EXISTS `filter_handler`;
CREATE TABLE `filter_handler` (
  `handler` varchar(32) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`handler`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `filter_handler` DISABLE KEYS */;

--
-- Table structure for table 'interfaces'
--

DROP TABLE IF EXISTS `interfaces`;
CREATE TABLE `interfaces` (
  `if_id` mediumint(8) unsigned NOT NULL auto_increment,
  `router_id` tinyint(3) unsigned NOT NULL default '0',
  `description` char(255) NOT NULL default '',
  `type` enum('Internal','External') default 'Internal',
  PRIMARY KEY  (`if_id`),
  UNIQUE KEY `router_id` (`router_id`,`description`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `interfaces` DISABLE KEYS */;

--
-- Table structure for table 'locked_client'
--

DROP TABLE IF EXISTS `locked_client`;
CREATE TABLE `locked_client` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `client_id` int(11) unsigned NOT NULL default '0',
  `radreply_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `client_id` (`client_id`),
  KEY `radreply_id` (`radreply_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `locked_client` DISABLE KEYS */;

--
-- Table structure for table 'maillist'
--

DROP TABLE IF EXISTS `maillist`;
CREATE TABLE `maillist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `client_id` int(11) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `maillist` DISABLE KEYS */;

--
-- Table structure for table 'order_report'
--

DROP TABLE IF EXISTS `order_report`;
CREATE TABLE `order_report` (
  `id` int(11) NOT NULL auto_increment,
  `client_id` int(11) NOT NULL default '0',
  `report_type` enum('daily','hourly','flows') NOT NULL default 'daily',
  `starttimestamp` datetime default NULL,
  `stoptimestamp` datetime default NULL,
  `result_email` varchar(64) default NULL,
  `notify_email` varchar(64) default NULL,
  `publish` enum('yes','no') NOT NULL default 'no',
  `cell_operator` varchar(16) NOT NULL default '',
  `cell_phone` varchar(16) NOT NULL default '',
  `arch_type` enum('zip','rar','gz') NOT NULL default 'zip',
  `resolve_ip` enum('yes','no') NOT NULL default 'no',
  `inprogress` enum('yes','no') NOT NULL default 'no',
  `finished` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`id`),
  KEY `finished` (`finished`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `order_report` DISABLE KEYS */;

--
-- Table structure for table 'payment'
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
  `id` int(11) NOT NULL auto_increment,
  `contract_id` int(11) unsigned NOT NULL default '0',
  `timestamp` date NOT NULL default '0000-00-00',
  `value` decimal(20,2) NOT NULL default '0.00',
  `vat` decimal(20,2) NOT NULL default '0.00',
  `value_without_vat` decimal(20,2) NOT NULL default '0.00',
  `cash` enum('yes','no') NOT NULL default 'no',
  `operator` varchar(64) NOT NULL default '',
  `notice` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `contract_id` (`contract_id`),
  KEY `timestamp` (`timestamp`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `payment` DISABLE KEYS */;

--
-- Table structure for table 'permlevel'
--

DROP TABLE IF EXISTS `permlevel`;
CREATE TABLE `permlevel` (
  `id` int(11) NOT NULL auto_increment,
  `level` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `level` (`level`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `permlevel` DISABLE KEYS */;

--
-- Table structure for table 'personal_account'
--

DROP TABLE IF EXISTS `personal_account`;
CREATE TABLE `personal_account` (
  `id` int(11) NOT NULL auto_increment,
  `client_id` int(11) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `value` decimal(12,4) NOT NULL default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `client_id` (`client_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `personal_account` DISABLE KEYS */;

--
-- Table structure for table 'rate'
--

DROP TABLE IF EXISTS `rate`;
CREATE TABLE `rate` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `date` date NOT NULL default '0000-00-00',
  `rate` double NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `date` (`date`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `rate` DISABLE KEYS */;

--
-- Table structure for table 'register'
--

DROP TABLE IF EXISTS `register`;
CREATE TABLE `register` (
  `NUMREC` varchar(200) NOT NULL default '',
  `CLIENT_ID` varchar(200) NOT NULL default '',
  `NAME` varchar(200) NOT NULL default '',
  `CONTRACT` varchar(200) NOT NULL default '',
  `F0` varchar(200) NOT NULL default '',
  `F1` varchar(200) NOT NULL default '',
  `F2` varchar(200) NOT NULL default '',
  `F3` varchar(200) NOT NULL default '',
  `F4` varchar(200) NOT NULL default '',
  `F5` varchar(200) NOT NULL default '',
  `F6` varchar(200) NOT NULL default '',
  `F7` varchar(200) NOT NULL default '',
  `F8` varchar(200) NOT NULL default '',
  `F9` varchar(200) NOT NULL default '',
  `F10` varchar(200) NOT NULL default '',
  `F11` varchar(200) NOT NULL default '',
  `F12` varchar(200) NOT NULL default '',
  `F13` varchar(200) NOT NULL default '',
  `F14` varchar(200) NOT NULL default '',
  `F15` varchar(200) NOT NULL default '',
  `WITHOUTTAC` varchar(200) NOT NULL default '',
  `WITHTAC` varchar(200) NOT NULL default ''
) TYPE=InnoDB;

/*!40000 ALTER TABLE `register` DISABLE KEYS */;

--
-- Table structure for table 'report'
--

DROP TABLE IF EXISTS `report`;
CREATE TABLE `report` (
  `id` int(11) NOT NULL default '0',
  `report_body` mediumtext,
  PRIMARY KEY  (`id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `report` DISABLE KEYS */;

--
-- Table structure for table 'routers'
--

DROP TABLE IF EXISTS `routers`;
CREATE TABLE `routers` (
  `router_id` tinyint(3) unsigned NOT NULL auto_increment,
  `hostname` char(255) NOT NULL default '',
  `last_file_offset` int(11) NOT NULL default '0',
  `last_time_access` timestamp(14) NOT NULL,
  PRIMARY KEY  (`router_id`),
  UNIQUE KEY `hostname` (`hostname`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `routers` DISABLE KEYS */;

--
-- Table structure for table 'service'
--

DROP TABLE IF EXISTS `service`;
CREATE TABLE `service` (
  `id` int(11) NOT NULL auto_increment,
  `description` varchar(255) NOT NULL default '---',
  `contract_id` int(11) NOT NULL default '0',
  `service_type_id` int(11) NOT NULL default '0',
  `tariff_id` int(11) NOT NULL default '0',
  `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `expire_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `cash` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`id`),
  KEY `service_type_id` (`service_type_id`),
  KEY `tariff_id` (`tariff_id`),
  KEY `contract_id` (`contract_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `service` DISABLE KEYS */;

--
-- Table structure for table 'service_type'
--

DROP TABLE IF EXISTS `service_type`;
CREATE TABLE `service_type` (
  `id` int(11) NOT NULL auto_increment,
  `service_type` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `service_type` DISABLE KEYS */;

--
-- Table structure for table 'tariff'
--

DROP TABLE IF EXISTS `tariff`;
CREATE TABLE `tariff` (
  `id` int(11) NOT NULL auto_increment,
  `monthlypayment` enum('yes','no') NOT NULL default 'no',
  `tariff` mediumtext,
  PRIMARY KEY  (`id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `tariff` DISABLE KEYS */;

--
-- Table structure for table 'traffic_cur'
--

DROP TABLE IF EXISTS `traffic_cur`;
CREATE TABLE `traffic_cur` (
  `id` int(4) NOT NULL auto_increment,
  `client_id` int(11) NOT NULL default '0',
  `incoming` bigint(20) NOT NULL default '0',
  `outcoming` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `client_id` (`client_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `traffic_cur` DISABLE KEYS */;

--
-- Table structure for table 'traffic_snapshot'
--

DROP TABLE IF EXISTS `traffic_snapshot`;
CREATE TABLE `traffic_snapshot` (
  `id` int(4) NOT NULL auto_increment,
  `client_id` int(4) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `incoming` bigint(20) NOT NULL default '0',
  `outcoming` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `client_id` (`client_id`),
  KEY `timestamp` (`timestamp`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `traffic_snapshot` DISABLE KEYS */;

--
-- Table structure for table 'userlevel'
--

DROP TABLE IF EXISTS `userlevel`;
CREATE TABLE `userlevel` (
  `level_id` int(11) NOT NULL default '0',
  `user` varchar(64) NOT NULL default '',
  `superpasswd` varchar(255) default NULL,
  `name` varchar(255) NOT NULL default '',
  `phone` varchar(64) NOT NULL default '',
  UNIQUE KEY `user` (`user`),
  KEY `level_id` (`level_id`)
) TYPE=InnoDB;

/*!40000 ALTER TABLE `userlevel` DISABLE KEYS */;

