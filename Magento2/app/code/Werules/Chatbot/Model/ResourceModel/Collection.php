<?php

namespace Werules\Chatbot\Model\ResourceModel\Contact;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Contact Resource Model Collection
 *
 * @author      Pierre FAY
 */
class Collection extends AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Werules\Chatbot\Model\IncomingMessages', 'Werules\Chatbot\Model\ResourceModel\IncomingMessages');
	}
}