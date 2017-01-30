<?php

$installer = $this;
$installer->startSetup(); //db installation

$installer->run(" 
	-- DROP TABLE IF EXISTS {$this->getTable('wr_chatbot')};
	CREATE TABLE {$this->getTable('wr_chatbot')} (
	  `entity_id` int(11) unsigned NOT NULL auto_increment,
	  `customer_id` int(11) NULL,
	  `telegram_chat_id` varchar(50) NULL,
	  `telegram_conv_state` int(10) NOT NULL default '0',
	  `facebook_chat_id` varchar(50) NULL,
	  `facebook_conv_state` int(10) NOT NULL default '0',
	  `whatsapp_chat_id` varchar(50) NULL,
	  `whatsapp_conv_state` int(10) NOT NULL default '0',
	  `hash_key` varchar(150) NULL,
	  `is_logged` smallint(1) NOT NULL default '0',
	  key (entity_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");

$installer->endSetup();
