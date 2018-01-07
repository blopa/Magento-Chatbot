<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
 * 
 * This file is part of Werules/Chatbot.
 * 
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * MIT License for more details.
 * 
 * You should have received a copy of the MIT License
 * along with this program. If not, see <https://opensource.org/licenses/MIT>.
 */

namespace Werules\Chatbot\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "1.0.2", "<")) {
        //Your upgrade script
            // Get module table
            $tableName = $setup->getTable('werules_chatbot_chatbotapi');
            $connection = $setup->getConnection();

            // Check if the table werules_chatbot_chatbotapi already exists
            if ($connection->isTableExists($tableName) == true)
            {
                $connection->addColumn(
                    $tableName,
                    'last_command_details',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => null,
                        'comment' => 'Last Command Details',
                        'nullable' => true
                    )
                );
            }
        }
//        $setup->endSetup();

//        $setup->startSetup();
        if (version_compare($context->getVersion(), "1.0.3", "<")) {

            //Your upgrade script
            // Get module table
            $tableName = $setup->getTable('werules_chatbot_message');
            $connection = $setup->getConnection();

            // Check if the table werules_chatbot_message already exists
            if ($connection->isTableExists($tableName) == true)
            {
                $connection->addColumn(
                    $tableName,
                    'sent_at',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        'length' => null,
                        'comment' => 'Sent At',
                        'nullable' => true
                    )
                );
                $connection->addColumn(
                    $tableName,
                    'current_command_details',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => null,
                        'comment' => 'Current Command Details',
                        'nullable' => false
                    )
                );
            }
        }
//        $setup->endSetup();

//        $setup->startSetup();
        if (version_compare($context->getVersion(), "1.0.5", "<")) {
            $installer = $setup;
            $installer->startSetup();

            $table = $installer->getConnection()
                ->newTable($installer->getTable('werules_chatbot_promotionalmessages'));

            $table->addColumn(
                'promotionalmessages_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
                'Entity ID'
            );
            $table->addColumn(
                'content',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                640,
                ['nullable' => False],
                'Message Content'
            );
            $table->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Created At'
            );
            $table->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Updated At'
            );
            $table->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['default' => 0,'nullable' => False],
                'Status'
            );

            $installer->getConnection()->createTable($table);
            $installer->endSetup();
        }
        $setup->endSetup();
    }
}
