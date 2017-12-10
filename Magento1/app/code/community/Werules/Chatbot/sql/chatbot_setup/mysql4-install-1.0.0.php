<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2017
 *
 * This file is part of Werules/Chatbot.
 *
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

$table = 'wr_chatbot';
$installer = $this;
$installer->startSetup(); //db installation

$installer->run("
    -- DROP TABLE IF EXISTS {$this->getTable($table)};
    CREATE TABLE {$this->getTable($table)} (
      `entity_id` int(11) unsigned NOT NULL auto_increment,
      `created_at` timestamp NULL,
      `updated_at` timestamp NULL,
      `customer_id` int(11) NULL,
      `session_id` varchar(150) NULL,
      `quote_id` varchar(20) NULL,
      `hash_key` varchar(150) NULL,
      `is_admin` smallint(1) NOT NULL default '0',
      `is_logged` smallint(1) NOT NULL default '0',
      `enable_promotional_messages` smallint(1) NOT NULL default '1',
      `enable_support` smallint(1) NOT NULL default '1',
      `last_support_message_id` varchar(50) NULL,
      `last_support_chat` varchar(20) NULL,
      `enable_telegram_admin` smallint(1) NOT NULL default '1',
      `enable_telegram` smallint(1) NOT NULL default '1',
      `telegram_processing_request` smallint(1) NOT NULL default '0',
      `telegram_chat_id` varchar(50) NULL,
      `telegram_message_id` varchar(50) NULL,
      `telegram_conv_state` int(10) NOT NULL default '0',
      `telegram_support_reply_chat_id` varchar(50) NULL,
      `telegram_fallback_qty` int(10) NOT NULL default '0',
      `enable_facebook_admin` smallint(1) NOT NULL default '1',
      `enable_facebook` smallint(1) NOT NULL default '1',
      `facebook_processing_request` smallint(1) NOT NULL default '0',
      `facebook_chat_id` varchar(50) NULL,
      `facebook_message_id` varchar(50) NULL,
      `facebook_conv_state` int(10) NOT NULL default '0',
      `facebook_support_reply_chat_id` varchar(50) NULL,
      `facebook_fallback_qty` int(10) NOT NULL default '0',
      `enable_whatsapp_admin` smallint(1) NOT NULL default '1',
      `enable_whatsapp` smallint(1) NOT NULL default '1',
      `whatsapp_processing_request` smallint(1) NOT NULL default '0',
      `whatsapp_chat_id` varchar(50) NULL,
      `whatsapp_message_id` varchar(50) NULL,
      `whatsapp_conv_state` int(10) NOT NULL default '0',
      `whatsapp_support_reply_chat_id` varchar(50) NULL,
      `whatsapp_fallback_qty` int(10) NOT NULL default '0',
      `enable_wechat_admin` smallint(1) NOT NULL default '1',
      `enable_wechat` smallint(1) NOT NULL default '1',
      `wechat_processing_request` smallint(1) NOT NULL default '0',
      `wechat_chat_id` varchar(50) NULL,
      `wechat_message_id` varchar(50) NULL,
      `wechat_conv_state` int(10) NOT NULL default '0',
      `wechat_support_reply_chat_id` varchar(50) NULL,
      `wechat_fallback_qty` int(10) NOT NULL default '0',
      `enable_skype_admin` smallint(1) NOT NULL default '1',
      `enable_skype` smallint(1) NOT NULL default '1',
      `skype_processing_request` smallint(1) NOT NULL default '0',
      `skype_chat_id` varchar(50) NULL,
      `skype_message_id` varchar(50) NULL,
      `skype_conv_state` int(10) NOT NULL default '0',
      `skype_support_reply_chat_id` varchar(50) NULL,
      `skype_fallback_qty` int(10) NOT NULL default '0',
      PRIMARY KEY (entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

$installer->endSetup();
