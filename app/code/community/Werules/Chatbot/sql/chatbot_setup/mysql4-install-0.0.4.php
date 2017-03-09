<?php

$table = 'wr_chatbot';
$installer = $this;
$installer->startSetup(); //db installation

$installer->run("
	-- DROP TABLE IF EXISTS {$this->getTable($table)};
	CREATE TABLE {$this->getTable($table)} (
	  `entity_id` int(11) unsigned NOT NULL auto_increment,
	  `customer_id` int(11) NULL,
	  `session_id` varchar(150) NULL,
	  `quote_id` varchar(20) NULL,
	  `hash_key` varchar(150) NULL,
	  `is_admin` smallint(1) NOT NULL default '0',
	  `is_logged` smallint(1) NOT NULL default '0',
	  `enable_support` smallint(1) NOT NULL default '1',
	  `last_support_message_id` varchar(50) NULL,
	  `last_support_chat` varchar(20) NULL,
	  `enable_telegram` smallint(1) NOT NULL default '1',
	  `telegram_chat_id` varchar(50) NULL,
	  `telegram_message_id` varchar(50) NULL,
	  `telegram_conv_state` int(10) NOT NULL default '0',
	  `enable_facebook` smallint(1) NOT NULL default '1',
	  `facebook_chat_id` varchar(50) NULL,
	  `facebook_message_id` varchar(50) NULL,
	  `facebook_conv_state` int(10) NOT NULL default '0',
	  `enable_whatsapp` smallint(1) NOT NULL default '1',
	  `whatsapp_chat_id` varchar(50) NULL,
	  `whatsapp_message_id` varchar(50) NULL,
	  `whatsapp_conv_state` int(10) NOT NULL default '0',
	  `enable_wechat` smallint(1) NOT NULL default '1',
	  `wechat_chat_id` varchar(50) NULL,
	  `wechat_message_id` varchar(50) NULL,
	  `wechat_conv_state` int(10) NOT NULL default '0',
	  `custom_one` varchar(150) NULL,
	  `custom_two` varchar(150) NULL,
	  `custom_three` varchar(150) NULL,
	  `custom_four` varchar(150) NULL,
	  key (entity_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");

$installer->endSetup();
