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

namespace Werules\Chatbot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;
    protected $_messageModel;
    protected $_chatbotAPI;
    protected $_define;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        \Werules\Chatbot\Model\ChatbotAPIFactory $chatbotAPI,
        \Werules\Chatbot\Model\MessageFactory $message
    )
    {
        $this->objectManager = $objectManager;
        $this->storeManager  = $storeManager;
        $this->_messageModel  = $message;
        $this->_chatbotAPI  = $chatbotAPI;
        $this->_define = new \Werules\Chatbot\Helper\Define;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function logger($message) // TODO find a better way to to this
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/werules_chatbot.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(var_export($message, true));
    }

    protected function getJsonResponse($success)
    {
        header_remove('Content-Type'); // TODO
        header('Content-Type: application/json'); // TODO
        if ($success)
            $arr = array("status" => "success", "success" => true);
        else
            $arr = array("status" => "error", "success" => false);
        return json_encode($arr);
    }

    public function getJsonSuccessResponse()
    {
        return $this->getJsonResponse(true);
    }

    public function getJsonErrorResponse()
    {
        return $this->getJsonResponse(false);
    }

    public function processMessage($message_id)
    {
        $message = $this->_messageModel->create();
        $message->load($message_id);

        if ($message->getMessageId())
        {
            if ($message->getDirection() == 0)
                $this->processIncomingMessage($message);
            else //if ($message->getDirection() == 1)
                $this->processOutgoingMessage($message);
        }
    }

    private function processIncomingMessage($message)
    {
        // TODO do something
        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($message->getSenderId(), 'chat_id'); // TODO

        if (!($chatbotAPI->getChatbotapiId()))
        {
            $chatbotAPI->setEnabled($this->_define::DISABLED);
            $chatbotAPI->setChatbotType($message->getChatbotType()); // TODO
            $chatbotAPI->setChatId($message->getSenderId());
            $chatbotAPI->setConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->setFallbackQty(0);
            $datetime = date('Y-m-d H:i:s');
            $chatbotAPI->setCreatedAt($datetime);
            $chatbotAPI->setUpdatedAt($datetime);
            $chatbotAPI->save();
        }

        $this->logger("Message ID -> " . $message->getMessageId());
        $this->logger("Message Content -> " . $message->getContent());
        $this->logger("ChatbotAPI ID -> " . $chatbotAPI->getChatbotapiId());

        $this->prepareOutgoingMessage($message);
    }

    private function prepareOutgoingMessage($message)
    {
        $responseContents = $this->processMessageRequest($message);

        if ($responseContents)
        {
            foreach ($responseContents as $content)
            {
                $outgoingMessage = $this->_messageModel->create();
                $outgoingMessage->setSenderId($message->getSenderId());
                $outgoingMessage->setContent($content);
                $outgoingMessage->setContentType($this->_define::CONTENT_TEXT); // TODO
                $outgoingMessage->setStatus($this->_define::PROCESSING);
                $outgoingMessage->setDirection($this->_define::OUTGOING);
                $outgoingMessage->setChatMessageId($message->getChatMessageId());
                $outgoingMessage->setChatbotType($message->getChatbotType());
                $datetime = date('Y-m-d H:i:s');
                $outgoingMessage->setCreatedAt($datetime);
                $outgoingMessage->setUpdatedAt($datetime);
                $outgoingMessage->save();

                $this->processOutgoingMessage($outgoingMessage->getMessageId());
            }

            $incomingMessage = $this->_messageModel->create();
            $incomingMessage->load($message->getMessageId()); // TODO
            $incomingMessage->setStatus($this->_define::PROCESSED);
            $datetime = date('Y-m-d H:i:s');
            $incomingMessage->setUpdatedAt($datetime);
            $incomingMessage->save();

//        $this->processOutgoingMessage($outgoingMessage);
        }
    }

    private function processOutgoingMessage($message_id)
    {
        $outgoingMessage = $this->_messageModel->create();
        $outgoingMessage->load($message_id);

        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($outgoingMessage->getSenderId(), 'chat_id'); // TODO
        $result = $chatbotAPI->sendMessage($outgoingMessage);

        if ($result)
        {
            $outgoingMessage->setStatus($this->_define::PROCESSED);
            $datetime = date('Y-m-d H:i:s');
            $outgoingMessage->setUpdatedAt($datetime);
            $outgoingMessage->save();
        }

        $this->logger("Outgoing Message ID -> " . $outgoingMessage->getMessageId());
        $this->logger("Outgoing Message Content -> " . $outgoingMessage->getContent());
    }

    private function processMessageRequest($message)
    {
        $content = array();
        if ($message->getContent() == 'foobar')
        {
            array_push($content, 'eggs and spam');
        }
        else if ($message->getContent() == 'flood')
        {
            array_push($content, 'so you want a flood?');
            array_push($content, 'okay then');
            array_push($content, 'here we go');
            array_push($content, 'floooooood');
            array_push($content, 'flooood');
            array_push($content, 'flooood flooood flooood flooood flooood');
            array_push($content, 'flood..!');
        }
        else
        {
            array_push($content, 'hello :D');
        }

        return $content;
    }

//    public function getConfig($code, $storeId = null)
//    {
//        return $this->getConfigValue(self::XML_PATH_CHATBOT . $code, $storeId);
//    }
}