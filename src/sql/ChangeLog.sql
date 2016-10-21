
DROP TABLE IF EXISTS db_version;
CREATE TABLE db_version (
  version varchar(32) NOT NULL default '0.0'
) TYPE=InnoDB;

INSERT INTO `db_version` (`version`) VALUES ('1.0');

ALTER TABLE `client` CHANGE `edrpou` `edrpou` VARCHAR(255) NOT NULL;

DROP TABLE IF EXISTS extract;

CREATE TABLE extract (
  payer_mfo_bank int(11) NOT NULL default '0',
  payer_account int(11) NOT NULL default '0',
  recipient_mfo_bank int(11) NOT NULL default '0',
  recipient_account int(11) NOT NULL default '0',
  peration_type varchar(255) NOT NULL default '',
  sum decimal(20,2) NOT NULL default '0.00',
  payment_type tinyint(2) NOT NULL default '0',
  document_number varchar(255) NOT NULL default '',
  currency_code tinyint(5) NOT NULL default '0',
  document_date date NOT NULL default '0000-00-00',
  document_recv_date date NOT NULL default '0000-00-00',
  payer_description varchar(255) NOT NULL default '',
  recipint_description varchar(255) NOT NULL default '',
  payment_purpose varchar(255) NOT NULL default '',
  additional_properties varchar(255) NOT NULL default '',
  payment_purpose_code varchar(32) NOT NULL default '',
  payer_okpo varchar(255) NOT NULL default '',
  recipient_okpo varchar(255) NOT NULL default '',
  document_id int(11) NOT NULL default '0',
  session_id varchar(255) NOT NULL default '',
  KEY session_id (session_id)
) TYPE=InnoDB;

ALTER TABLE `client` ADD INDEX(`edrpou`);

ALTER TABLE `extract` DROP INDEX `session_id`, ADD INDEX `session_id` (`session_id`,`document_id`);

UPDATE db_version SET version = '1.1';
-- Commited at 2004/05/09 00:02:58 by ingoth

UPDATE db_version SET version = '1.2';
-- Commited at 2004/05/09 00:03:51 by ingoth

UPDATE db_version SET version = '1.3';
-- Commited at 2004/05/09 08:29:45 by ingoth

UPDATE db_version SET version = '1.4';
-- Commited at 2004/05/09 08:30:01 by ingoth


UPDATE db_version SET version = '1.6';
-- Commited at 2004/05/09 08:31:47 by ingoth

ALTER TABLE `client` ADD `blocked` ENUM('y','n') DEFAULT 'n' NOT NULL;

UPDATE db_version SET version = '1.7';
-- Commited at 2004/05/16 11:02:22 by ingoth

ALTER TABLE `service` ADD `short_description` VARCHAR(255) AFTER `description`;

UPDATE db_version SET version = '1.8';
-- Commited at 2004/05/21 07:05:18 by ingoth

CREATE TABLE `notification` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
	`bill_id` INT(11) UNSIGNED DEFAULT '0' NOT NULL, 
	`header` TEXT, 
	`body` TEXT, 
	`footer` TEXT,
	INDEX (`bill_id`)
	) TYPE = InnoDB;

ALTER TABLE `notification` ADD `sub_header1` VARCHAR(255) NOT NULL AFTER `header`, ADD `sub_header2` VARCHAR(255) NOT NULL AFTER `sub_header1`; 

ALTER TABLE `notification` ADD `month_num` INT(11) DEFAULT '0' NOT NULL AFTER `bill_id`, ADD `num` INT(11) DEFAULT '0' NOT NULL AFTER `month_num`;
ALTER TABLE `notification` ADD INDEX (`month_num`); 

UPDATE db_version SET version = '1.9';
-- Commited at 2004/05/21 14:26:20 by ingoth

ALTER TABLE `tariff` ADD `main_currency` ENUM('yes','no') DEFAULT 'no' NOT NULL;

UPDATE db_version SET version = '1.10';
-- Commited at 2004/06/10 12:50:41 by ingoth

CREATE TABLE `voting` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
	`client_id` INT(11) UNSIGNED NOT NULL, 
	`choice` ENUM('y','n') NOT NULL, 
	`notes` VARCHAR(255) NOT NULL,
	INDEX (`client_id`)
	); 

UPDATE db_version SET version = '1.11';
-- Commited at 2004/06/21 14:58:54 by ingoth

ALTER TABLE `voting` type=InnoDB;

CREATE TABLE `stwa` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `contract_id` int(11) unsigned NOT NULL default '0',
  `create_time` date NOT NULL default '0000-00-00',
  `starttime` date NOT NULL default '0000-00-00',
  `stoptime` date NOT NULL default '0000-00-00',
  `header` text NOT NULL,
  `body` text NOT NULL,
  `footer1` text NOT NULL,
  `footer2` text NOT NULL,
  `value_without_vat` decimal(20,2) NOT NULL default '0.00',
  `vat` decimal(20,2) NOT NULL default '0.00',
  `value_with_vat` decimal(20,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`),
  KEY `contract_id` (`contract_id`)
) TYPE=InnoDB;

UPDATE db_version SET version = '1.12';
-- Commited at 2004/07/04 22:32:32 by ingoth

ALTER TABLE `cluster` ADD `creating_time` DATE NOT NULL ,
ADD `closing_time` DATE NOT NULL ;

UPDATE `cluster` SET `creating_time` = '2002-09-01';

UPDATE db_version SET version = '1.13';
-- Commited at 2004/07/27 10:29:13 by ingoth

ALTER TABLE `client` ADD `blocking_time` DATE;

UPDATE `client` SET `blocking_time` = NOW() WHERE `blocked` = 'y';


UPDATE db_version SET version = '1.14';
-- Commited at 2004/10/30 14:34:01 by ingoth

CREATE TABLE `counter` (
	`variable` VARCHAR( 255 ) NOT NULL ,
	`value` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL ,
	PRIMARY KEY ( `variable` ) 
	) TYPE = InnoDB;

INSERT INTO `counter` ( `variable` , `value` ) 
	VALUES (
	'bill_num', '1');

INSERT INTO `counter` ( `variable` , `value` ) 
	VALUES (
	'stwa_num', '1');

ALTER TABLE `bill` ADD `bill_num` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `id` ;

UPDATE `bill` SET `bill_num` = `id`;

ALTER TABLE `stwa` ADD `stwa_num` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `id` ;

UPDATE `stwa` SET `stwa_num` = `id`;





UPDATE db_version SET version = '1.15';
-- Commited at 2005/01/02 19:16:02 by ingoth

UPDATE `client` SET `inactivation_time` = NULL WHERE `inactivation_time` = 0;

ALTER TABLE `client` DROP `active`;


UPDATE db_version SET version = '1.16';
-- Commited at 2005/03/13 12:56:14 by ingoth

ALTER TABLE `client` ADD `save_flows` ENUM( 'yes', 'no' ) DEFAULT 'yes' NOT NULL ;


UPDATE db_version SET version = '1.17';
-- Commited at 2005/03/20 19:13:57 by ingoth

ALTER TABLE `client` ADD `unlimited` ENUM( 'yes', 'no' ) DEFAULT 'no' NOT NULL ;

ALTER TABLE `client` ADD `connection_speed` INT( 11 ) UNSIGNED DEFAULT NULL ;



UPDATE db_version SET version = '1.18';
-- Commited at 2005/05/01 14:41:16 by ingoth

CREATE TABLE `log_event` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `session_id` varchar(255) NOT NULL default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `script` varchar(255) NOT NULL default '',
  `operator` varchar(255) NOT NULL default '',
  `client_id` int(11) NOT NULL default '0',
  `table` varchar(255) NOT NULL default '',
  `tkey` int(11) NOT NULL default '0',
  `action_type` enum('view','add','update','delete') NOT NULL default 'view',
  `details` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `operator` (`operator`),
  KEY `client_id` (`client_id`),
  KEY `session_id` (`session_id`)
) TYPE=InnoDB PACK_KEYS=0 AUTO_INCREMENT=191 ;



UPDATE db_version SET version = '1.19';
-- Commited at 2005/07/30 12:49:40 by ingoth

ALTER TABLE `client` CHANGE `activation_time` `activation_time` DATE DEFAULT NULL;

UPDATE db_version SET version = '1.20';
-- Commited at 2005/07/30 15:56:26 by ingoth

ALTER TABLE `client` ADD `has_equipment` ENUM( 'yes', 'no' ) DEFAULT 'no' NOT NULL AFTER `unlimited` ;


UPDATE db_version SET version = '$Revision: 1.7 $';
-- Commited at $Date: 2011/03/01 17:58:47 $ by $Author: ingoth $

ALTER TABLE `client` MODIFY `activation_time` DATE NOT NULL DEFAULT '0000-00-00';

UPDATE db_version SET version = '$Revision: 1.7 $';
-- Commited at $Date: 2011/03/01 17:58:47 $ by $Author: ingoth $

CREATE TABLE `drweb_avd_customer` (
	  `id` varchar(255) NOT NULL,
	  `client_id` int(10) unsigned NOT NULL default '0',
	  `service_id` int(10) unsigned NOT NULL,
	  `pwd` varchar(255) NOT NULL,
	  `pgrp` varchar(255) NOT NULL,
	  `desc` varchar(255) NOT NULL,
	  `access_date` date NOT NULL default '0000-00-00',
	  `from_block_date` date NOT NULL default '0000-00-00',
	  `till_block_date` date NOT NULL default '0000-00-00',
	  `url` varchar(512) NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `client_id` (`client_id`),
	  KEY `pgrp` (`pgrp`),
	  CONSTRAINT `drweb_avd_customer_ibfk_1` FOREIGN KEY (`pgrp`) REFERENCES `drweb_avd_group` (`group_id`)
) ENGINE=InnoDB;
CREATE TABLE `drweb_avd_group` (
	  `group_id` varchar(255) NOT NULL,
	  `name` varchar(255) NOT NULL,
	  `description` varchar(255) NOT NULL,
	  `cost` decimal(20,2) NOT NULL default '0.00',
	  PRIMARY KEY  (`group_id`)
) ENGINE=InnoDB;

UPDATE db_version SET version = '$Revision: 1.7 $';
-- Commited at $Date: 2011/03/01 17:58:47 $ by $Author: ingoth $

UPDATE db_version SET version = '1.21';
ALTER TABLE `client` ADD `natural_person` ENUM( 'yes', 'no' ) DEFAULT 'no' NOT NULL AFTER `tac_status` ;

UPDATE db_version SET version = '$Revision: 1.7 $';
-- Commited at $Date: 2011/03/01 17:58:47 $ by $Author: ingoth $

alter table filter add `hidden` enum('yes', 'no') default 'no';

UPDATE db_version SET version = '$Revision: 1.7 $';
-- Commited at $Date: 2011/03/01 17:58:47 $ by $Author: ingoth $
