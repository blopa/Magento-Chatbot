<?php

namespace Werules\Chatbot\Model;

use Magento\Cron\Exception;
use Magento\Framework\Model\AbstractModel;

class IncomingMessages extends AbstractModel
{
	/**
	 * @var \Magento\Framework\Stdlib\DateTime
	 */
	protected $_dateTime;

	/**
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init(\Werules\Chatbot\Model\ResourceModel\IncomingMessages::class);
	}

}