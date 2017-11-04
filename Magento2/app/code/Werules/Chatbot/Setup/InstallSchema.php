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

namespace Werules\Chatbot\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $table_werules_chatbot_message = $setup->getConnection()->newTable($setup->getTable('werules_chatbot_message'));


        $table_werules_chatbot_message->addColumn(
            'message_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
            'Entity ID'
        );



        $table_werules_chatbot_message->addColumn(
            'sender_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Sender ID'
        );



        $table_werules_chatbot_message->addColumn(
            'content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => False],
            'Message Content'
        );



        $table_werules_chatbot_message->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => False],
            'Status'
        );



        $table_werules_chatbot_message->addColumn(
            'direction',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => False],
            'Direction (incoming or outgoing)'
        );



        $table_werules_chatbot_message->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => False],
            'Created At'
        );



        $table_werules_chatbot_message->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Updated At'
        );


        $table_werules_chatbot_chatbotuser = $setup->getConnection()->newTable($setup->getTable('werules_chatbot_chatbotuser'));


        $table_werules_chatbot_chatbotuser->addColumn(
            'chatbotuser_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
            'Entity ID'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Customer ID'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'hash_key',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => False],
            'Hash Key'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Quote ID'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'session_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Session ID'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'enable_promotional_messages',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['default' => False,'nullable' => False],
            'Enable Promotional Messages'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'enable_support',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['default' => True,'nullable' => False],
            'Enable Support?'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'logged',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['default' => False,'nullable' => False],
            'Is Logged'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'admin',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['default' => False,'nullable' => False],
            'Is Admin'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => False],
            'Created At'
        );



        $table_werules_chatbot_chatbotuser->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Updated At'
        );


        $table_werules_chatbot_chatbotapi = $setup->getConnection()->newTable($setup->getTable('werules_chatbot_chatbotapi'));


        $table_werules_chatbot_chatbotapi->addColumn(
            'chatbotapi_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
            'Entity ID'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'enabled',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['default' => True,'nullable' => False],
            'Enabled?'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'chatbot_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => False],
            'Chatbot Type'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'chat_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => False],
            'Chat ID'
        );



        $table_werules_chatbot_message->addColumn(
            'chat_message_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => False],
            'Chat Message ID'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'conversation_state',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => False],
            'Conversation State'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'fallback_qty',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['default' => '0','nullable' => False],
            'Fallback Quantity'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => False],
            'Created At'
        );



        $table_werules_chatbot_chatbotapi->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Updated At'
        );


        $setup->getConnection()->createTable($table_werules_chatbot_chatbotapi);

        $setup->getConnection()->createTable($table_werules_chatbot_chatbotuser);

        $setup->getConnection()->createTable($table_werules_chatbot_message);

        $setup->endSetup();
    }
}
