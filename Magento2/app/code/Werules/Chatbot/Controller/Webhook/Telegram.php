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

namespace Werules\Chatbot\Controller\Webhook;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
class Telegram extends \Magento\Framework\App\Action\Action
{
    protected $_chatbotAPI;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Werules\Chatbot\Model\ChatbotAPI $chatbotAPI)
    {
        $this->_chatbotAPI = $chatbotAPI;
        parent::__construct($context);
    }

    public function execute()
    {
//        $jsonResult = $this->_chatbotAPI->requestHandler();
//
//        $this->getResponse()->representJson(
//            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($jsonResult)
//        );
        return $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
    }
}
