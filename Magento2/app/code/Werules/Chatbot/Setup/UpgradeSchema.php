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

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

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
        if (version_compare($context->getVersion(), "1.0.1", "<")) {
        //Your upgrade script
            // Get module table
//            $tableName = $setup->getTable('table_name');
//
//            // Check if the table already exists
//            if ($setup->getConnection()->isTableExists($tableName) == true) {
//                // Declare data
//                $columns = [
//                    'imagename' => [
//                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                        'nullable' => false,
//                        'comment' => 'image name',
//                    ],
//                ];
//
//                $connection = $setup->getConnection();
//                foreach ($columns as $name => $definition) {
//                    $connection->addColumn($tableName, $name, $definition);
//                }
//
//            }
        }
        $setup->endSetup();
    }
}
