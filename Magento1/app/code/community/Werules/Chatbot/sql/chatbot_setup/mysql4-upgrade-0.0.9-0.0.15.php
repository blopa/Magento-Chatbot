<?php

$table = 'wr_chatbot';
$installer = $this;
$installer->startSetup(); //db update

$installer->run("
	ALTER TABLE {$this->getTable($table)}
	ADD COLUMN `enable_promotional_messages` smallint(1) NOT NULL default '1' AFTER `is_logged`,
	ADD COLUMN `enable_telegram_admin` smallint(1) NOT NULL default '1' AFTER `last_support_chat`,
	ADD COLUMN `telegram_processing_request` smallint(1) NOT NULL default '0' AFTER `enable_telegram`,
	ADD COLUMN `telegram_fallback_qty` int(10) NOT NULL default '0' AFTER `telegram_support_reply_chat_id`,
	ADD COLUMN `enable_facebook_admin` smallint(1) NOT NULL default '1' AFTER `telegram_fallback_qty`,
	ADD COLUMN `facebook_processing_request` smallint(1) NOT NULL default '0' AFTER `enable_facebook`,
	ADD COLUMN `facebook_fallback_qty` int(10) NOT NULL default '0' AFTER `facebook_support_reply_chat_id`,
	ADD COLUMN `enable_whatsapp_admin` smallint(1) NOT NULL default '1' AFTER `facebook_fallback_qty`,
	ADD COLUMN `whatsapp_processing_request` smallint(1) NOT NULL default '0' AFTER `enable_whatsapp`,
	ADD COLUMN `whatsapp_fallback_qty` int(10) NOT NULL default '0' AFTER `whatsapp_support_reply_chat_id`,
	ADD COLUMN `enable_wechat_admin` smallint(1) NOT NULL default '1' AFTER `whatsapp_fallback_qty`,
	ADD COLUMN `wechat_processing_request` smallint(1) NOT NULL default '0' AFTER `enable_wechat`,
	ADD COLUMN `wechat_fallback_qty` int(10) NOT NULL default '0' AFTER `wechat_support_reply_chat_id`,
	ADD COLUMN `enable_skype_admin` smallint(1) NOT NULL default '1' AFTER `wechat_fallback_qty`,
	ADD COLUMN `enable_skype` smallint(1) NOT NULL default '1' AFTER `enable_skype_admin`,
	ADD COLUMN `skype_processing_request` smallint(1) NOT NULL default '0' AFTER `enable_skype`,
	ADD COLUMN `skype_chat_id` varchar(50) NULL AFTER `skype_processing_request`,
	ADD COLUMN `skype_message_id` varchar(50) NULL AFTER `skype_chat_id`,
	ADD COLUMN `skype_conv_state` int(10) NOT NULL default '0' AFTER `skype_message_id`,
	ADD COLUMN `skype_support_reply_chat_id` varchar(50) NULL AFTER `skype_conv_state`,
	ADD COLUMN `skype_fallback_qty` int(10) NOT NULL default '0' AFTER `skype_support_reply_chat_id`;
	");

$installer->endSetup();
