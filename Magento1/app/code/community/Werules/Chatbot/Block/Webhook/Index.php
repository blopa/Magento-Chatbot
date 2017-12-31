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

class Werules_Chatbot_Block_Webhook_Index extends Mage_Core_Block_Template
{
    protected $_helper;
    protected $_chatbotAPI;
    protected $_messageModel;
    protected $_request;
    protected $_define;
//    protected $_cronWorker;

    public function _construct()
    {
        $this->_helper = Mage::helper('werules_chatbot');
        $this->_chatbotAPI = Mage::getModel('werules_chatbot/chatbotapi');
        $this->_messageModel = Mage::getModel('werules_chatbot/message');
        $this->_request = $this->getRequest();
        $this->_define = new Werules_Chatbot_Helper_Define;
    }

    protected function createMessageObject($apiModel){} // TODO
    public function processRequest(){} // TODO

    protected function checkEndpointSecretKey()
    {
        $urlKey = $this->_request->getParam('endpoint');
        $customKey = $this->getConfigValue('werules_chatbot_general/general/custom_key');
        if ($urlKey == $customKey)
            return true;

        return false;
    }

    public function requestHandler()
    {
        $enabled = $this->getConfigValue('werules_chatbot_general/general/enable');
        if ($enabled != $this->_define->ENABLED)
            return $this->getJsonErrorResponse();

        $correctKey = $this->checkEndpointSecretKey();
        if ($correctKey)
            $result = $this->processRequest();
        else
            $result = $this->getJsonErrorResponse();

        return $result;
    }

    protected function messageHandler($messageObject)
    {
        $messageModel = $this->_helper->createIncomingMessage($messageObject);
        if ($messageModel->getMessageId())
        {
            $messageQueueMode = $this->_helper->getQueueMessageMode();
            if (($messageQueueMode == $this->_define->QUEUE_NONE) || ($messageQueueMode == $this->_define->QUEUE_NON_RESTRICTIVE))
            {
                $outgoingMessages = $this->_helper->processIncomingMessage($messageModel);
                foreach ($outgoingMessages as $outgoingMessage)
                {
                    $result = $this->_helper->processOutgoingMessage($outgoingMessage);
                }
            }
            else if (($messageQueueMode == $this->_define->QUEUE_RESTRICTIVE) || ($messageQueueMode == $this->_define->QUEUE_SIMPLE_RESTRICTIVE))
            {
                $result = $this->_helper->processIncomingMessageQueueBySenderId($messageModel->getSenderId());
                if ($result)
                    $result = $this->_helper->processOutgoingMessageQueueBySenderId($messageModel->getSenderId());
            }
        }
        else
            return $this->_helper->getJsonErrorResponse();

        return $this->getJsonSuccessResponse();
    }

    public function getConfigValue($code)
    {
        return $this->_helper->getConfigValue($code);
    }

    protected function getJsonErrorResponse()
    {
        return $this->_helper->getJsonErrorResponse();
    }

    protected function getJsonSuccessResponse()
    {
        return $this->_helper->getJsonSuccessResponse();
    }

    protected function logPostData($data, $file = 'werules_chatbot.log')
    {
        $postLog = ($this->getConfigValue('werules_chatbot_general/general/enable_post_log') == $this->_define->ENABLED);
        if ($postLog)
            $this->_helper->logger($data, $file);
    }
}