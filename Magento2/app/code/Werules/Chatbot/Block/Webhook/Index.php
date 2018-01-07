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

namespace Werules\Chatbot\Block\Webhook;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_helper;
    protected $_chatbotAPI;
    protected $_messageModel;
    protected $_objectManager;
    protected $_define;
    protected $_request;
//    protected $_cronWorker;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
//        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Werules\Chatbot\Helper\Data $helperData,
        \Werules\Chatbot\Model\ChatbotAPI $chatbotAPI,
        \Magento\Framework\App\Request\Http $request,
        \Werules\Chatbot\Model\MessageFactory $message,
        array $data = array()
//        \Werules\Chatbot\Cron\Worker $cronWorker
    )
    {
        $this->_helper = $helperData;
        $this->_chatbotAPI = $chatbotAPI;
        $this->_request = $request;
        $this->_messageModel = $message;
        $this->_objectManager = $objectManager;
        $this->_define = new \Werules\Chatbot\Helper\Define;
//        $this->_cronWorker = $cronWorker;
        parent::__construct($context, $data);
    }

    protected function createMessageObject($apiModel){} // TODO
    protected function processRequest(){} // TODO

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
        if ($enabled != $this->_define::ENABLED)
            return $this->getJsonErrorResponse();

        $correctKey = $this->checkEndpointSecretKey();
        if ($correctKey)
        {
//            $messageObject = $this->createMessageObject();
//            $result = $this->messageHandler($messageObject);
            $result = $this->processRequest();
        }
        else
            $result = $this->getJsonErrorResponse();

        return $result;
    }

    protected function messageHandler($messageObject)
    {
//        $messageModel = $this->_messageModel->create();
//        $messageModel->setSenderId($messageObject->senderId);
//        $messageModel->setContent($messageObject->content);
//        $messageModel->setChatbotType($messageObject->chatType);
//        $messageModel->setContentType($messageObject->contentType);
//        $messageModel->setStatus($messageObject->status);
//        $messageModel->setDirection($messageObject->direction);
//        $messageModel->setMessagePayload($messageObject->messagePayload);
//        $messageModel->setChatMessageId($messageObject->chatMessageId);
//        $messageModel->setCreatedAt($messageObject->createdAt);
//        $messageModel->setUpdatedAt($messageObject->updatedAt);

//        try {
//            $messageModel->save();
//        } catch (\Magento\Framework\Exception\LocalizedException $e) {
//            return $this->_helper->getJsonErrorResponse();
//        }
        $messageModel = $this->_helper->createIncomingMessage($messageObject);
        if ($messageModel->getMessageId())
        {
            $messageQueueMode = $this->_helper->getQueueMessageMode();
            if (($messageQueueMode == $this->_define::QUEUE_NONE) || ($messageQueueMode == $this->_define::QUEUE_NON_RESTRICTIVE))
            {
                $outgoingMessages = $this->_helper->processIncomingMessage($messageModel);
                foreach ($outgoingMessages as $outgoingMessage)
                {
                    $result = $this->_helper->processOutgoingMessage($outgoingMessage);
                }
            }
            else if (($messageQueueMode == $this->_define::QUEUE_RESTRICTIVE) || ($messageQueueMode == $this->_define::QUEUE_SIMPLE_RESTRICTIVE))
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
        $postLog = ($this->getConfigValue('werules_chatbot_general/general/enable_post_log') == $this->_define::ENABLED);
        if ($postLog)
            $this->_helper->logger($data, $file);
    }

//    public function getDefine()
//    {
//        return $this->_define;
//    }
}
