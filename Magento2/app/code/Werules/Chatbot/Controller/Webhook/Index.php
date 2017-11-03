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

namespace Werules\Chatbot\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
	/**
	 * Dispatch request
	 *
	 * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
	 * @throws \Magento\Framework\Exception\NotFoundException
	*/
	protected $_resultFactory;

	public function __construct(Context $context)
	{
		parent::__construct($context);
	}

	public function execute()
	{
//		$message = $this->_objectManager->create('Werules\Chatbot\Model\IncomingMessages');
//		$message->setMessageContent('Message 1');
//		$message->save();
//
//		$message = $this->_objectManager->create('Werules\Chatbot\Model\IncomingMessages');
//		$message->setMessageContent('Message 2');
//		$message->save();
//
//		$message = $this->_objectManager->create('Werules\Chatbot\Model\IncomingMessages');
//		$message->setMessageContent('Message 3');
//		$message->save();

		return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
	}
}