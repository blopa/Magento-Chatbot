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

    private function setConfigPrefix($chatbotType)
    {
        if (!isset($this->_configPrefix))
        {
            if ($chatbotType == $this->_define->MESSENGER_INT)
                $this->_configPrefix = 'werules_chatbot_messenger';
        }
    }

    private function getChatbotAPIModelBySenderId($senderId)
    {
        if (isset($this->_chatbotAPIModel))
            return $this->_chatbotAPIModel;

        // should never get here
        // because it's already set
        $chatbotAPI = $this->getChatbotAPIBySenderId($senderId);
        $this->setChatbotAPIModel($chatbotAPI);

        return $chatbotAPI;
    }

    private function setChatbotAPIModel($chatbotAPI)
    {
        $this->_chatbotAPIModel = $chatbotAPI;
    }

    private function getChatbotAPIBySenderId($senderId)
    {
        $chatbotAPI = $this->_chatbotAPIFactory->create();
        $chatbotAPI->load($senderId, 'chat_id'); // TODO

        return $chatbotAPI;
    }

    public function createChatbotAPI($chatbotAPI, $message)
    {
        $chatbotAPI->setEnabled($this->_define->ENABLED);
        $chatbotAPI->setChatbotType($message->getChatbotType());
        $chatbotAPI->setChatId($message->getSenderId());
        $chatbotAPI->setConversationState($this->_define->CONVERSATION_STARTED);
        $chatbotAPI->setFallbackQty(0);
        $chatbotAPI->setLastCommandDetails($this->_define->LAST_COMMAND_DETAILS_DEFAULT); // TODO
        $hash = $this->generateRandomHashKey();
        $chatbotAPI->setHashKey($hash);
        $datetime = date('Y-m-d H:i:s');
        $chatbotAPI->setCreatedAt($datetime);
        $chatbotAPI->setUpdatedAt($datetime);
        $chatbotAPI->save();

        return $chatbotAPI;
    }

    private function getTextMessageArray($text)
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define->CONTENT_TEXT,
            'content' => $text,
            'current_command_details' => json_encode(array())
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function getErrorMessage()
    {
        $text = __("Something went wrong, please try again.");
        return $this->getTextMessageArray($text);
    }

    private function getDisabledMessage($message)
    {
        $outgoingMessages = array();
        $text = $this->getConfigValue($this->_configPrefix . '/general/disabled_message');

        if ($text != '')
            $contentObj = $this->getTextMessageArray($text);
        else
            $contentObj = $this->getErrorMessage();

        $outgoingMessage = $this->createOutgoingMessage($message, reset($contentObj)); // TODO reset -> gets first item of array
        if ($outgoingMessage)
            array_push($outgoingMessages, $outgoingMessage);

        return $outgoingMessages;
    }

    private function getDisabledByCustomerMessage($message)
    {
        $outgoingMessages = array();
        $text = __("To chat with me, please enable Messenger on your account chatbot settings.");
        $contentObj = $this->getTextMessageArray($text);
        $outgoingMessage = $this->createOutgoingMessage($message, reset($contentObj)); // TODO reset -> gets first item of array
        if ($outgoingMessage)
            array_push($outgoingMessages, $outgoingMessage);

        return $outgoingMessages;
    }

    public function processIncomingMessage($message)
    {
        $messageQueueMode = $this->getQueueMessageMode();
        if (($messageQueueMode == $this->_define->QUEUE_NONE) && ($message->getStatus() != $this->_define->PROCESSED))
            $message->updateIncomingMessageStatus($this->_define->PROCESSED);
        else if ($message->getStatus() != $this->_define->PROCESSING)
            $message->updateIncomingMessageStatus($this->_define->PROCESSING);

        $this->setConfigPrefix($message->getChatbotType());
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($message->getSenderId());
        $result = array();

        if (!($chatbotAPI->getChatbotapiId()))
        {
            $chatbotAPI = $this->createChatbotAPI($chatbotAPI, $message);
            $welcomeMessage = $this->getWelcomeMessage($message);
            if ($welcomeMessage)
            {
                if ($message->getStatus() != $this->_define->PROCESSED)
                    $message->updateIncomingMessageStatus($this->_define->PROCESSED);

                array_push($result, $welcomeMessage);
                return $result;
            }
        }

        $enabled = $this->getConfigValue($this->_configPrefix . '/general/enable');
        if ($enabled == $this->_define->DISABLED)
            $outgoingMessages = $this->getDisabledMessage($message);
        else if ($chatbotAPI->getEnabled() == $this->_define->DISABLED)
            $outgoingMessages = $this->getDisabledByCustomerMessage($message);
        else
        {
            // all good, let's process the request
            $this->setChatbotAPIModel($chatbotAPI);
            $outgoingMessages = $this->prepareOutgoingMessage($message);
        }

        if ($outgoingMessages)
        {
            foreach ($outgoingMessages as $outgoingMessage)
            {
                array_push($result, $outgoingMessage);
            }
        }

        if (($result) && ($message->getStatus() != $this->_define->PROCESSED))
            $message->updateIncomingMessageStatus($this->_define->PROCESSED);

        return $result;
    }



    private function prepareOutgoingMessage($message)
    {
        $responseContents = $this->processMessageRequest($message);
        $outgoingMessages = array();

        if ($responseContents)
        {
            foreach ($responseContents as $content)
            {
                // first guarantee outgoing message is saved
                $outgoingMessage = $this->createOutgoingMessage($message, $content);
                array_push($outgoingMessages, $outgoingMessage);
            }
        }

        return $outgoingMessages;
    }

    private function getWelcomeMessage($message)
    {
        $outgoingMessage = array();
        $text = $this->getConfigValue($this->_configPrefix . '/general/welcome_message');
        if ($text != '')
        {
            $enableMessageOptions = $this->getConfigValue($this->_configPrefix . '/general/enable_message_options');
            if ($enableMessageOptions == $this->_define->ENABLED)
            {
                $quickReplies = array();
                $welcomeMessageOptions = $this->getWelcomeMessageOptionsData();
                foreach ($welcomeMessageOptions as $optionId => $messageOption)
                {
                    if (count($quickReplies) >= $this->_define->MAX_MESSAGE_ELEMENTS)
                        break;

                    $quickReply = array(
                        'content_type' => 'text', // TODO messenger pattern
                        'title' => $messageOption['option_text'],
                        'payload' => json_encode(array())
                    );
                    array_push($quickReplies, $quickReply);
                }

                $contentObject = new stdClass();
                $contentObject->message = $text;
                $contentObject->quick_replies = $quickReplies;
                $content = json_encode($contentObject);
                $contentType = $this->_define->QUICK_REPLY;
            }
            else
            {
                $contentType = $this->_define->CONTENT_TEXT;
                $content = $text;
            }
            $responseMessage = array(
                'content_type' => $contentType,
                'content' => $content,
                'current_command_details' => json_encode(array())
            );
            $outgoingMessage = $this->createOutgoingMessage($message, $responseMessage);
        }

        return $outgoingMessage;
    }

    public function createOutgoingMessage($message, $content)
    {
        $outgoingMessage = $this->_messageModelFactory;
        $outgoingMessage->setSenderId($message->getSenderId());
        $outgoingMessage->setContent($content['content']);
        $outgoingMessage->setContentType($content['content_type']); // TODO
        $outgoingMessage->setCurrentCommandDetails($content['current_command_details']); // TODO
        $outgoingMessage->setStatus($this->_define->PROCESSING);
        $outgoingMessage->setDirection($this->_define->OUTGOING);
        $outgoingMessage->setChatMessageId($message->getChatMessageId());
        $outgoingMessage->setChatbotType($message->getChatbotType());
        $outgoingMessage->setSentAt(time());
        $datetime = date('Y-m-d H:i:s');
        $outgoingMessage->setCreatedAt($datetime);
        $outgoingMessage->setUpdatedAt($datetime);
        $outgoingMessage->save();

        return $outgoingMessage;
    }

    private function getWelcomeMessageOptionsData()
    {
        $welcomeMessageOptions = array();
        $serializedWelcomeMessageOptions = $this->getConfigValue($this->_configPrefix . '/general/message_options');
        if ($serializedWelcomeMessageOptions)
            $welcomeMessageOptions = $this->_serializer->unserialize($serializedWelcomeMessageOptions);

        return $welcomeMessageOptions;
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