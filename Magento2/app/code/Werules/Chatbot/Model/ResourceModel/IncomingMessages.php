<?php

namespace Werules\Chatbot\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IncomingMessages extends AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('chatbot_incoming_messages', 'id');
	}
}