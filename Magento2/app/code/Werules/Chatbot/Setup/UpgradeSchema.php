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
        $setup->endSetup();
    }
}
