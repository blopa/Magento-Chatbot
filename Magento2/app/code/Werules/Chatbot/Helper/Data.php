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
    protected $_configPrefix;

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
        $this->_configPrefix = 'werules_chatbot_messenger';
        $messageContent = $message->getContent();
        $responseContent = array();
//        if ($messageContent == 'foobar')
//        {
//            array_push($content, 'eggs and spam');
//        }
//        else if ($messageContent == 'flood')
//        {
//            array_push($content, 'so you want a flood?');
//            array_push($content, 'okay then');
//            array_push($content, 'here we go');
//            array_push($content, 'floooooood');
//            array_push($content, 'flooood');
//            array_push($content, 'flooood flooood flooood flooood flooood');
//            array_push($content, 'flood..!');
//        }
//        else
//        {
//            array_push($content, 'hello :D');
//        }
//
//        return $content;

        $commandResponses = $this->handleCommands($messageContent);
        if ($commandResponses)
        {
            foreach ($commandResponses as $commandResponse)
            {
                array_push($responseContent, $commandResponse);
            }
        }

        return $responseContent;
    }

    private function handleCommands($messageContent)
    {
        $serializedCommands = $this->getConfigValue($this->_configPrefix . '/general/commands_list');
        $commandsList = unserialize($serializedCommands);
        $result = false;
        if (is_array($commandsList))
        {
            foreach($commandsList as $command)
            {
                if ($messageContent == $command['command_code'])
                {
                    if ($command['command_id'] == $this->_define::START_COMMAND_ID)
                        $result = $this->processStartCommand();
                    else if ($command['command_id'] == $this->_define::LIST_CATEGORIES_COMMAND_ID)
                        $result = $this->processListCategoriesCommand();
                    else if ($command['command_id'] == $this->_define::SEARCH_COMMAND_ID)
                        $result = $this->processSearchCommand();
                    else if ($command['command_id'] == $this->_define::LOGIN_COMMAND_ID)
                        $result = $this->processLoginCommand();
                    else if ($command['command_id'] == $this->_define::LIST_ORDERS_COMMAND_ID)
                        $result = $this->processListOrdersCommand();
                    else if ($command['command_id'] == $this->_define::REORDER_COMMAND_ID)
                        $result = $this->processReorderCommand();
                    else if ($command['command_id'] == $this->_define::ADD_TO_CART_COMMAND_ID)
                        $result = $this->processAddToCartCommand();
                    else if ($command['command_id'] == $this->_define::CHECKOUT_COMMAND_ID)
                        $result = $this->processCheckoutCommand();
                    else if ($command['command_id'] == $this->_define::CLEAR_CART_COMMAND_ID)
                        $result = $this->processClearCartCommand();
                    else if ($command['command_id'] == $this->_define::TRACK_ORDER_COMMAND_ID)
                        $result = $this->processTrackOrderCommand();
                    else if ($command['command_id'] == $this->_define::SUPPORT_COMMAND_ID)
                        $result = $this->processSupportCommand();
                    else if ($command['command_id'] == $this->_define::SEND_EMAIL_COMMAND_ID)
                        $result = $this->processSendEmailCommand();
                    else if ($command['command_id'] == $this->_define::CANCEL_COMMAND_ID)
                        $result = $this->processCancelCommand();
                    else if ($command['command_id'] == $this->_define::HELP_COMMAND_ID)
                        $result = $this->processHelpCommand();
                    else if ($command['command_id'] == $this->_define::ABOUT_COMMAND_ID)
                        $result = $this->processAboutCommand();
                    else if ($command['command_id'] == $this->_define::LOGOUT_COMMAND_ID)
                        $result = $this->processLogoutCommand();
                    else if ($command['command_id'] == $this->_define::REGISTER_COMMAND_ID)
                        $result = $this->processRegisterCommand();
                    break;
                }
            }
        }

        return $result;
    }

    private function processStartCommand()
    {
        $result = array();
        array_push($result, 'you just sent the START command!');
        return $result;
    }

    private function processListCategoriesCommand()
    {
        $result = array();
        array_push($result, 'you just sent the LIST_CATEGORIES command!');
        return $result;
    }

    private function processSearchCommand()
    {
        $result = array();
        array_push($result, 'you just sent the SEARCH command!');
        return $result;
    }

    private function processLoginCommand()
    {
        $result = array();
        array_push($result, 'you just sent the LOGIN command!');
        return $result;
    }

    private function processListOrdersCommand()
    {
        $result = array();
        array_push($result, 'you just sent the LIST_ORDERS command!');
        return $result;
    }

    private function processReorderCommand()
    {
        $result = array();
        array_push($result, 'you just sent the REORDER command!');
        return $result;
    }

    private function processAddToCartCommand()
    {
        $result = array();
        array_push($result, 'you just sent the ADD_TO_CART command!');
        return $result;
    }

    private function processCheckoutCommand()
    {
        $result = array();
        array_push($result, 'you just sent the CHECKOUT command!');
        return $result;
    }

    private function processClearCartCommand()
    {
        $result = array();
        array_push($result, 'you just sent the CLEAR_CART command!');
        return $result;
    }

    private function processTrackOrderCommand()
    {
        $result = array();
        array_push($result, 'you just sent the TRACK_ORDER command!');
        return $result;
    }

    private function processSupportCommand()
    {
        $result = array();
        array_push($result, 'you just sent the SUPPORT command!');
        return $result;
    }

    private function processSendEmailCommand()
    {
        $result = array();
        array_push($result, 'you just sent the SEND_EMAIL command!');
        return $result;
    }

    private function processCancelCommand()
    {
        $result = array();
        array_push($result, 'you just sent the CANCEL command!');
        return $result;
    }

    private function processHelpCommand()
    {
        $result = array();
        array_push($result, 'you just sent the HELP command!');
        return $result;
    }

    private function processAboutCommand()
    {
        $result = array();
        array_push($result, 'you just sent the ABOUT command!');
        return $result;
    }

    private function processLogoutCommand()
    {
        $result = array();
        array_push($result, 'you just sent the LOGOUT command!');
        return $result;
    }

    private function processRegisterCommand()
    {
        $result = array();
        array_push($result, 'you just sent the REGISTER command!');
        return $result;
    }

//    public function getConfig($code, $storeId = null)
//    {
//        return $this->getConfigValue(self::XML_PATH_CHATBOT . $code, $storeId);
//    }
}