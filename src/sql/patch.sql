/* $Id: patch.sql,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $ */

ALTER TABLE `bill` ADD `status` ENUM('new', 'paid') DEFAULT 'new' NOT NULL;

ALTER TABLE `client` CHANGE `edrpou` `edrpou` VARCHAR(255) NOT NULL

