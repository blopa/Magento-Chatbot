<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
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

class Werules_Chatbot_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $storeManager;
    protected $_messageModelFactory;
    protected $_messageModel;
    protected $_chatbotAPIFactory;
    protected $_chatbotUserFactory;
    protected $_serializer;
    protected $_categoryHelper;
    protected $_categoryFactory;
    protected $_categoryCollectionFactory;
    protected $_orderCollectionFactory;
    protected $_productCollection;
    protected $_customerRepositoryInterface;
    protected $_quoteModel;
    protected $_configWriter;
    protected $_imageHelper;
    protected $_stockRegistry;
    protected $_stockFilter;
    protected $_priceHelper;

    // not from construct parameters
    protected $_define;
    protected $_messageQueueMode;
    protected $_configPrefix;
    protected $_commandsList;
    protected $_completeCommandsList;
    protected $_currentCommand;
    protected $_messagePayload;
    protected $_chatbotAPIModel;

    public function __construct()
    {
        $this->_serializer = Mage::helper('core/unserializeArray'); // ->unserialize((string)$serializedValue); // fix magento unserializing bug
//        $this->storeManager = $storeManager;
        $this->_messageModel = Mage::getModel('werules_chatbot/message'); // TODO
        $this->_messageModelFactory = Mage::getModel('werules_chatbot/message'); // TODO
        $this->_chatbotAPIFactory = Mage::getModel('werules_chatbot/chatbotapi'); // TODO
        $this->_chatbotUserFactory = Mage::getModel('werules_chatbot/chatbotuser'); // TODO
        $this->_define = new Werules_Chatbot_Helper_Define;
    }
    public function convertOptions($options)
    {
        $converted = array();
        foreach ($options as $option) {
            if (isset($option['value']) && !is_array($option['value']) &&
                isset($option['label']) && !is_array($option['label'])) {
                $converted[$option['value']] = $option['label'];
            }
        }
        return $converted;
    }

    public function createIncomingMessage($messageObject)
    {
        $this->logger('messageObject -> ');
        $this->logger($messageObject);
        $incomingMessage = $this->_messageModelFactory;
        if (isset($messageObject->senderId))
        {
            $incomingMessage->setSenderId($messageObject->senderId);
            $incomingMessage->setContent($messageObject->content);
            $incomingMessage->setChatbotType($messageObject->chatType);
            $incomingMessage->setContentType($messageObject->contentType);
            $incomingMessage->setCurrentCommandDetails($messageObject->currentCommandDetails);
            $incomingMessage->setStatus($messageObject->status);
            $incomingMessage->setDirection($messageObject->direction);
            $incomingMessage->setMessagePayload($messageObject->messagePayload);
            $incomingMessage->setChatMessageId($messageObject->chatMessageId);
            $incomingMessage->setSentAt($messageObject->sentAt);
            $incomingMessage->setCreatedAt($messageObject->createdAt);
            $incomingMessage->setUpdatedAt($messageObject->updatedAt);
            $incomingMessage->save();
        }

        return $incomingMessage;
    }

    public function getQueueMessageMode()
    {
        if (isset($this->_messageQueueMode))
            return $this->_messageQueueMode;

        $this->_messageQueueMode = $this->getConfigValue('werules_chatbot_general/general/message_queue_mode');
        return $this->_messageQueueMode;
    }

    public function processIncomingMessage($message) // TODO_NOW
    {
        return array();
    }

    public function processOutgoingMessage($outgoingMessage) // TODO_NOW
    {
        return array();
    }

    public function processIncomingMessageQueueBySenderId($senderId) // TODO_NOW
    {
        return array();
    }

    public function processOutgoingMessageQueueBySenderId($senderId) // TODO_NOW
    {
        return array();
    }

    public function logger($text, $file = 'werules_chatbot.log')
    {
        Mage::log($text, null, $file);
    }

    public function getConfigValue($field)
    {
        return Mage::getStoreConfig($field);
    }

    public function getJsonSuccessResponse()
    {
        return $this->getJsonResponse(true);
    }

    public function getJsonErrorResponse()
    {
        return $this->getJsonResponse(false);
    }

    private function getJsonResponse($success)
    {
        header_remove('Content-Type'); // TODO
        header('Content-Type: application/json'); // TODO
        if ($success)
            $result = array('status' => 'success', 'success' => true);
        else
            $result = array('status' => 'error', 'success' => false);
        return json_encode($result);
    }
}