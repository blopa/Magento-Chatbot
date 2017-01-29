<?php

$installer = $this;
$installer->startSetup(); //db installation

$installer->run(" 
	-- DROP TABLE IF EXISTS {$this->getTable('wr_chatbot')};
	CREATE TABLE {$this->getTable('wr_chatbot')} (
	  `entity_id` int(11) unsigned NOT NULL auto_increment,
	  `customer_id` int(11) NULL,
	  `nickname` varchar(120) NULL,
	  `profile_img` varchar(255) NULL,
	  `level` varchar(20) NULL,
	  `show_level` smallint(1) NOT NULL default '0',
	  `show_birthday` smallint(1) NOT NULL default '0',
	  `show_fav` smallint(1) NOT NULL default '0',
	  `last_bought` smallint(1) NOT NULL default '0',
	  `status` smallint(1) NOT NULL default '0',
	  key (entity_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");

$installer->endSetup();
