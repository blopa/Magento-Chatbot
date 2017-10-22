<?php

namespace Werules\Chatbot\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
	/**
	 * @param SchemaSetupInterface $setup
	 * @param ModuleContextInterface $context
	 * @throws \Zend_Db_Exception
	 */

	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();
		/**
		 * Create table 'chatbot_incoming_messages'
		 */

		if (!$setup->getConnection()->isTableExists($setup->getTable('chatbot_incoming_messages'))) {
			$table = $setup->getConnection()
				->newTable($setup->getTable('chatbot_incoming_messages'))
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
					'Message ID'
				)
				->addColumn(
					'message_content',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					500,
					['nullable' => false],
					'Content'
				)
				->addColumn(
					'is_processed',
					\Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
					1,
					['nullable' => false, 'default' => '0'],
					'Status'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					[],
					'Created at'
				)
				->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					[],
					'Updated at'
				)
				->setComment('Incoming Messages Table')
				->setOption('type', 'InnoDB')
				->setOption('charset', 'utf8');

			$setup->getConnection()->createTable($table);
		}
		$setup->endSetup();
	}
}