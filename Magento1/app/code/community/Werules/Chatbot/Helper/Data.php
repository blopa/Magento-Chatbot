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

    private function setHelperMessageAttributes($message)
    {
        $this->setCurrentMessagePayload($message->getMessagePayload());
        $this->setCurrentCommand($message->getContent()); // ignore output
        $this->prepareCommandsList();
    }

    private function prepareCommandsList()
    {
        if (isset($this->_commandsList) && isset($this->_completeCommandsList))
            return true;

        $serializedCommands = $this->getConfigValue($this->_configPrefix . '/general/commands_list');
        $commands = $this->_serializer->unserialize($serializedCommands);
        if (!($commands))
            return false;

        $commandsList = array();
        $completeCommandsList = array();
        foreach ($commands as $command)
        {
            $commandId = $command['command_id'];
            $completeCommandsList[$commandId] = array(
                'command_code' => $command['command_code'],
                'command_alias_list' => explode(',', $command['command_alias_list'])
            );

            if ($command['enable_command'] == $this->_define->ENABLED)
                $commandsList[$commandId] = $completeCommandsList[$commandId];
        }
        $this->setCommandsList($commandsList);
        $this->setCompleteCommandsList($completeCommandsList);

        return true;
    }

    private function setCommandsList($commandsList)
    {
        $this->_commandsList = $commandsList;
    }

    private function setCompleteCommandsList($completeCommandsList)
    {
        $this->_completeCommandsList = $completeCommandsList;
    }

    private function setCurrentCommand($messageContent)
    {
        if (!isset($this->_commandsList))
            $this->_commandsList = $this->getCommandsList();

        foreach ($this->_commandsList as $key => $command)
        {
            if (strtolower($messageContent) == strtolower($command['command_code'])) // TODO add configuration for this?
            {
                $this->_currentCommand = $key;
                return $key;
            }
        }

        return false;
    }

    private function getCommandsList()
    {
        if (isset($this->_commandsList))
            return $this->_commandsList;

        // should never get here
        $this->prepareCommandsList();
        return $this->_commandsList;
    }

    private function setCurrentMessagePayload($messagePayload)
    {
        if (!isset($this->_messagePayload))
        {
            if ($messagePayload)
            {
                $this->_messagePayload = json_decode($messagePayload);
                return true;
            }
        }

        return false;
    }

    private function getCurrentCommand($messageContent)
    {
        if (isset($this->_currentCommand))
            return $this->_currentCommand;

        return $this->setCurrentCommand($messageContent);
    }

    private function checkCancelCommand($command)
    {
        $result = array();
        if ($command == $this->_define->CANCEL_COMMAND_ID)
            $result = $this->processCancelCommand();

        return $result;
    }

    private function processCancelCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define->CONTENT_TEXT,
            'content' => __("Ok, canceled."),
            'current_command_details' => json_encode(array(
                'conversation_state' => $this->_define->CONVERSATION_STARTED,
                'command_text' => $this->getCommandText($this->_define->CANCEL_COMMAND_ID)
            ))
        );
        array_push($result, $responseMessage);

        return $result;
    }

    private function getCurrentMessagePayload()
    {
        if (isset($this->_messagePayload))
            return $this->_messagePayload;

        return false;
    }

    private function listProductsFromCategory($messageContent, $messagePayload, $senderId) // TODO_NOW
    {
        return array();
    }

    private function listProductsFromSearch($messageContent, $senderId) // TODO_NOW
    {
        return array();
    }

    private function sendEmailFromMessage($text) // TODO_NOW
    {
        return array();
    }

    private function listOrderDetailsFromOrderId($orderId, $senderId) // TODO_NOW
    {
        return array();
    }

    private function processReorderCommand($orderId, $senderId) // TODO_NOW
    {
        return array();
    }

    public function processListOrdersCommand($senderId) // TODO_NOW
    {
        return array();
    }

    private function processListCategoriesCommand() // TODO_NOW
    {
        return array();
    }

    private function processSupportCommand() // TODO_NOW
    {
        return array();
    }

    private function processSendEmailCommand() // TODO_NOW
    {
        return array();
    }

    private function processHelpCommand() // TODO_NOW
    {
        return array();
    }

    private function processAboutCommand() // TODO_NOW
    {
        $result = array();
        $text = $this->getConfigValue($this->_configPrefix . '/general/about_message');
        if ($text)
        {
            $responseMessage = array(
                'content_type' => $this->_define->CONTENT_TEXT,
                'content' => $text,
                'current_command_details' => json_encode(array(
                    'command_text' => $this->getCommandText($this->_define->ABOUT_COMMAND_ID)
                ))
            );
            array_push($result, $responseMessage);
        }
        else
            $result = $this->getErrorMessage();

        return $result;
    }

    private function processRegisterCommand() // TODO_NOW
    {
        return array();
    }

    private function processLogoutCommand($senderId) // TODO_NOW
    {
        return array();
    }

    private function processSearchCommand() // TODO_NOW
    {
        return array();
    }

    private function processTrackOrderCommand() // TODO_NOW
    {
        return array();
    }

    private function processLoginCommand($senderId) // TODO_NOW
    {
        return array();
    }

    private function processClearCartCommand($senderId) // TODO_NOW
    {
        return array();
    }

    private function processAddToCartCommand($senderId, $payload) // TODO_NOW
    {
        return array();
    }

    private function processCheckoutCommand($senderId) // TODO_NOW
    {
        return array();
    }

    public function processOutgoingMessage($outgoingMessage) // TODO_NOW
    {
        $result = true;
        $messageQueueMode = $this->getQueueMessageMode();
        if ($messageQueueMode == $this->_define->QUEUE_NONE && ($outgoingMessage->getStatus() != $this->_define->PROCESSED))
            $outgoingMessage->updateOutgoingMessageStatus($this->_define->PROCESSED);
        else if ($outgoingMessage->getStatus() != $this->_define->PROCESSING)
            $outgoingMessage->updateOutgoingMessageStatus($this->_define->PROCESSING);

        $chatbotAPI = $this->getChatbotAPIModelBySenderId($outgoingMessage->getSenderId());

        if ($outgoingMessage->getContentType() == $this->_define->CONTENT_TEXT)
            $result = $chatbotAPI->sendMessage($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define->QUICK_REPLY)
            $result = $chatbotAPI->sendQuickReply($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define->IMAGE_WITH_OPTIONS)
            $result = $chatbotAPI->sendImageWithOptions($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define->RECEIPT_LAYOUT)
            $result = $chatbotAPI->sendReceiptList($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define->TEXT_WITH_OPTIONS) // LIST_WITH_IMAGE
            $result = $chatbotAPI->sendMessageWithOptions($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define->NO_REPLY_MESSAGE)
            $result = true;

        if ($result)
        {
            if ($outgoingMessage->getCurrentCommandDetails())
            {
                $currentCommandDetails = json_decode($outgoingMessage->getCurrentCommandDetails());

                if (isset($currentCommandDetails->conversation_state))
                {
                    if ($chatbotAPI->getConversationState() != $currentCommandDetails->conversation_state)
                        $chatbotAPI->updateConversationState($currentCommandDetails->conversation_state);
                }

                if (isset($currentCommandDetails->list_more_conversation_state))
                    $lastConversationState = $currentCommandDetails->list_more_conversation_state;
                else
                    $lastConversationState = null;

                if (isset($currentCommandDetails->listed_quantity))
                    $lastListedQuantity = $currentCommandDetails->listed_quantity;
                else
                    $lastListedQuantity = null;

                if (isset($currentCommandDetails->command_text))
                    $lastCommandText = $currentCommandDetails->command_text;
                else
                    $lastCommandText = null;

                if (isset($currentCommandDetails->command_parameter))
                    $lastCommandParameter = $currentCommandDetails->command_parameter;
                else
                    $lastCommandParameter = null;

                if ($currentCommandDetails)
                    $chatbotAPI->setChatbotAPILastCommandDetails($lastCommandText, $lastListedQuantity, $lastConversationState, $lastCommandParameter);
            }

            if ($outgoingMessage->getStatus() != $this->_define->PROCESSED)
                $outgoingMessage->updateOutgoingMessageStatus($this->_define->PROCESSED);

            $outgoingMessage->updateSentAt(time());
        }

        return $result;
    }

    private function processStartCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define->CONTENT_TEXT,
            'content' => 'you just sent the START command!', // TODO
            'current_command_details' => json_encode(array(
                'command_text' => $this->getCommandText($this->_define->START_COMMAND_ID)
            ))
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processCommands($messageContent, $senderId, $commandCode = '', $payload = false) // TODO_NOW
    {
        $result = array();
        if (!$commandCode)
            $commandCode = $this->getCurrentCommand($messageContent);

        if (!$payload)
            $payload = $this->getCurrentMessagePayload();

        if ($commandCode)
        {
            if ($commandCode == $this->_define->START_COMMAND_ID)
                $result = $this->processStartCommand();
            else if ($commandCode == $this->_define->LIST_CATEGORIES_COMMAND_ID) // changes conversation state
                $result = $this->processListCategoriesCommand();
            else if ($commandCode == $this->_define->SEARCH_COMMAND_ID) // changes conversation state
                $result = $this->processSearchCommand();
            else if ($commandCode == $this->_define->LOGIN_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->NOT_LOGGED)
                    $result = $this->processLoginCommand($senderId);
                else
                {
                    $text = __("You are already logged.");
                    $result = $this->getTextMessageArray($text);
                }
            }
            else if ($commandCode == $this->_define->LIST_ORDERS_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
                    $result = $this->processListOrdersCommand($senderId);
                else
                    $result = $this->getNotLoggedMessage();
            }
//            else if ($commandCode == $this->_define->REORDER_COMMAND_ID)
//            {
//                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
//                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
//                    $result = $this->processReorderCommand($payload, $senderId);
//                else
//                    $result = $this->getNotLoggedMessage();
//            }
            else if ($commandCode == $this->_define->ADD_TO_CART_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
                {
                    if ($payload)
                        $result = $this->processAddToCartCommand($senderId, $payload);
                    else
                        $result = $this->getErrorMessage();
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($commandCode == $this->_define->CHECKOUT_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
                    $result = $this->processCheckoutCommand($senderId);
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($commandCode == $this->_define->CLEAR_CART_COMMAND_ID)
                $result = $this->processClearCartCommand($senderId);
            else if ($commandCode == $this->_define->TRACK_ORDER_COMMAND_ID) // changes conversation state
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
                    $result = $this->processTrackOrderCommand();
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($commandCode == $this->_define->SUPPORT_COMMAND_ID)
                $result = $this->processSupportCommand();
            else if ($commandCode == $this->_define->SEND_EMAIL_COMMAND_ID) // changes conversation state
                $result = $this->processSendEmailCommand();
            else if ($commandCode == $this->_define->CANCEL_COMMAND_ID)
                $result = $this->processCancelCommand();
            else if ($commandCode == $this->_define->HELP_COMMAND_ID)
                $result = $this->processHelpCommand();
            else if ($commandCode == $this->_define->ABOUT_COMMAND_ID)
                $result = $this->processAboutCommand();
            else if ($commandCode == $this->_define->LOGOUT_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
                {
                    $result = $this->processLogoutCommand($senderId);
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($commandCode == $this->_define->REGISTER_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->NOT_LOGGED)
                    $result = $this->processRegisterCommand();
                else
                {
                    $text = __("You are already registered.");
                    $result = $this->getTextMessageArray($text);
                }
            }
            else if ($commandCode == $this->_define->LIST_MORE_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());
                $result = $this->processListMore($lastCommandObject, $senderId);
            }
            else // should never fall in here
                $result = $this->getConfusedMessage();
        }

        return $result;
    }

    private function getConfusedMessage()
    {
        $text = __("Sorry, I didn't understand that.");
        return $this->getTextMessageArray($text);
    }

    private function processListMore($lastCommandObject, $senderId)
    {
        $result = array();
        if (!isset($lastCommandObject->last_listed_quantity))
            return $result;

        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $listedQuantity = $lastCommandObject->last_listed_quantity;

        if ($listedQuantity > 0)
        {
            $conversationState = $lastCommandObject->last_conversation_state;
            $listCommands = array( // TODO
                $this->_define->CONVERSATION_LIST_CATEGORIES,
                $this->_define->CONVERSATION_SEARCH
            );

            if (in_array($conversationState, $listCommands))
            {
                // change conversation state to use handleConversationState flow
                $chatbotAPI->updateConversationState($conversationState);
                $this->setChatbotAPIModel($chatbotAPI);

                $result = $this->handleConversationState($lastCommandObject->last_command_parameter, $senderId);
            }
            else if (strtolower($lastCommandObject->last_command_text) == strtolower($this->getCommandText($this->_define->LIST_ORDERS_COMMAND_ID)))
                $result = $this->processListOrdersCommand($senderId);
        }

        return $result;
    }

    private function getCommandNLPEntityData($commandCode)
    {
        $result = array();
        $serializedNLPEntities = $this->getConfigValue($this->_configPrefix . '/general/nlp_replies');
        $NLPEntitiesList = $this->_serializer->unserialize($serializedNLPEntities);

        foreach ($NLPEntitiesList as $key => $entity)
        {
            if (isset($entity['command_id']))
            {
                if ($entity['command_id'] == $commandCode)
                {
                    $confidence = $this->_define->DEFAULT_MIN_CONFIDENCE;
                    $extraText = '';
                    if (isset($entity['enable_reply']))
                    {
                        if ($entity['enable_reply'] == $this->_define->ENABLED)
                        {
                            if (isset($entity['confidence']))
                                $confidence = (float)$entity['confidence'] / 100;
                            if (isset($entity['reply_text']))
                                $extraText = $entity['reply_text'];

                            $result = array(
                                'confidence' => $confidence,
                                'reply_text' => $extraText
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function handleNaturalLanguageProcessor($message) // TODO_NOW
    {
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($message->getSenderId());
        $result = array();
        $parameterValue = false;
        $parameter = false;
        if ($message->getContent() == '')
            return $result;

        $entity = $chatbotAPI->getNLPTextMeaning($message->getContent());

        if (isset($entity['intent']))
        {
            $intent = $entity['intent']; // command string
            if (isset($entity['parameter'])) // check if has parameter
            {
                $parameter = $entity['parameter'];
                if (isset($parameter['value']))
                    $parameterValue = $parameter['value'];
            }

            $commandString = $intent['value'];
            $commandCode = $this->getCurrentCommand($commandString);

            if ($commandCode)
            {
                if (isset($intent['confidence']))
                {
                    $entityData = $this->getCommandNLPEntityData($commandCode); // get all NPL configs from backend
                    if (isset($entityData['confidence']))
                    {
                        $confidence = $entityData['confidence'];
                        if ($intent['confidence'] >= $confidence) // check intent confidence
                        {
                            if ($parameterValue)
                            {
                                if ($parameter['confidence'] >= $confidence) // check parameter confidence
                                    $result = $this->handleCommandsWithParameters($message, $parameterValue, $commandCode);
                            }
                            else
                                $result = $this->processCommands($commandString, $message->getSenderId(), $commandCode);

                            if ($result)
                            {
                                if (isset($entity['reply'])) // extra reply text from wit.ai
                                {
                                    $reply = $entity['reply'];
                                    if (isset($reply['value']))
                                    {
                                        $extraText = $reply['value'];
                                        if (isset($reply['confidence']))
                                        {
                                            if ($reply['confidence'] >= $confidence) // check text reply confidence
                                            {
                                                if ($extraText != '')
                                                {
                                                    $extraMessage = array(
                                                        'content_type' => $this->_define->CONTENT_TEXT,
                                                        'content' => $extraText,
                                                        'current_command_details' => json_encode(array(
                                                            'command_text' => $this->getCommandText($commandCode)
                                                        ))
                                                    );
                                                    array_unshift($result, $extraMessage);
                                                }
                                            }
                                        }
                                    }
                                }

                                if (isset($entityData['reply_text'])) // extra reply text from Magento backend config
                                {
                                    $extraText = $entityData['reply_text'];
                                    if ($extraText != '')
                                    {
                                        $extraMessage = array(
                                            'content_type' => $this->_define->CONTENT_TEXT,
                                            'content' => $extraText,
                                            'current_command_details' => json_encode(array(
                                                'command_text' => $this->getCommandText($commandCode)
                                            ))
                                        );
                                        array_unshift($result, $extraMessage);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function handleCommandsWithParameters($message, $keyword, $commandCode)
    {
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($message->getSenderId());
        $state = $this->getCommandConversationState($commandCode);
        if ($state)
            $chatbotAPI->updateConversationState($state);

        $result = $this->handleConversationState($message->getContent(), $message->getSenderId(), $keyword);
        return $result;
    }

    private function getCommandConversationState($command)
    {
        $result = null;
        if ($command == $this->_define->START_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->LIST_CATEGORIES_COMMAND_ID)
            $result = $this->_define->CONVERSATION_LIST_CATEGORIES;
        else if ($command == $this->_define->SEARCH_COMMAND_ID)
            $result = $this->_define->CONVERSATION_SEARCH;
        else if ($command == $this->_define->LOGIN_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->LIST_ORDERS_COMMAND_ID){}// doesn't change conversation state
//        else if ($command == $this->_define->REORDER_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->ADD_TO_CART_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->CHECKOUT_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->CLEAR_CART_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->TRACK_ORDER_COMMAND_ID)
            $result = $this->_define->CONVERSATION_TRACK_ORDER;
        else if ($command == $this->_define->SUPPORT_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->SEND_EMAIL_COMMAND_ID)
            $result = $this->_define->CONVERSATION_EMAIL;
        else if ($command == $this->_define->CANCEL_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->HELP_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->ABOUT_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->LOGOUT_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->REGISTER_COMMAND_ID){}// doesn't change conversation state
        else if ($command == $this->_define->LIST_MORE_COMMAND_ID){}// doesn't change conversation state

        return $result;
    }

    private function getCommandText($commandId)
    {
        $commands = $this->getCompleteCommandsList();
        if (isset($commands[$commandId]['command_code']))
            return $commands[$commandId]['command_code'];

        return '';
    }

    private function getCompleteCommandsList()
    {
        if (isset($this->_completeCommandsList))
            return $this->_completeCommandsList;

        // should never get here
        $this->prepareCommandsList();
        return $this->_completeCommandsList;
    }

    private function getDefaultRepliesData()
    {
        $defaultReplies = array();
        $serializedDefaultReplies = $this->getConfigValue($this->_configPrefix . '/general/default_replies');
        if ($serializedDefaultReplies)
            $defaultReplies = $this->_serializer->unserialize($serializedDefaultReplies);

        return $defaultReplies;
    }

    private function handleDefaultReplies($message)
    {
        $result = array();
        $defaultReplies = $this->getDefaultRepliesData();
        $text = $message->getContent();

        foreach ($defaultReplies as $defaultReply)
        {
            if (isset($defaultReply['enable_reply']))
            {
                if ($defaultReply['enable_reply'] == $this->_define->ENABLED)
                {
                    // MODES:
//                    EQUALS_TO
//                    STARTS_WITH
//                    ENDS_WITH
//                    CONTAINS
//                    MATCH_REGEX

                    $matched = false;
                    $match = $defaultReply["match_sintax"];
                    if ($defaultReply["match_case"] != $this->_define->ENABLED)
                    {
                        $match = strtolower($match);
                        $text = strtolower($text);
                    }

                    if ($defaultReply['match_mode'] == $this->_define->EQUALS_TO)
                    {
                        if ($text == $match)
                            $matched = true;
                    }
                    else if ($defaultReply['match_mode'] == $this->_define->STARTS_WITH)
                    {
                        if ($this->startsWith($text, $match))
                            $matched = true;
                    }
                    else if ($defaultReply['match_mode'] == $this->_define->ENDS_WITH)
                    {

                        if ($this->endsWith($text, $match))
                            $matched = true;
                    }
                    else if ($defaultReply['match_mode'] == $this->_define->CONTAINS)
                    {
                        if (strpos($text, $match) !== false)
                            $matched = true;
                    }
                    else if ($defaultReply['match_mode'] == $this->_define->MATCH_REGEX)
                    {
                        if (preg_match($match, $text))
                            $matched = true;
                    }

                    if ($matched)
                    {
                        $replyText = $defaultReply['reply_text'];
                        if ($replyText)
                            $result = $this->getTextMessageArray($replyText);
                        break;
                    }
                }
            }
        }

        return $result;
    }

    private function handleCommands($message)
    {
        // TODO uncomment this to dirty accept typed orders number
//        $chatbotAPI = $this->getChatbotAPIModelBySenderId($message->getSenderId());
//        $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());
//        if (strtolower($lastCommandObject->last_command_text) == strtolower($this->getCommandText($this->_define->LIST_ORDERS_COMMAND_ID)))
//            $result = $this->processCommands('', $message->getSenderId(), $this->_define->REORDER_COMMAND_ID, $message->getContent());
//        else
        $result = $this->processCommands($message->getContent(), $message->getSenderId());

        return $result;
    }

    private function handleUnableToProcessRequest($message)
    {
        $fallbackLimit = $this->getConfigValue($this->_configPrefix . '/general/fallback_message_quantity');
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($message->getSenderId());

        if ($chatbotAPI->getFallbackQty())
            $fallbackQty = (int)$chatbotAPI->getFallbackQty();
        else
            $fallbackQty = 0;

        if ($fallbackQty >= (int)$fallbackLimit)
        {
            $text = $this->getConfigValue($this->_configPrefix . '/general/fallback_message');
            if ($text != '')
            {
                $responseContent = $this->getTextMessageArray($text);
                $chatbotAPI->updateChatbotAPIFallbackQty(0);
            }
            else
                $responseContent = $this->getErrorMessage();
        }
        else
        {
            $chatbotAPI->updateChatbotAPIFallbackQty($fallbackQty + 1);
            $responseContent = $this->getTextMessageArray(__("Sorry, I didn't understand that."));
        }

        return $responseContent;
    }

    public function processIncomingMessageQueueBySenderId($senderId)
    {
        $outgoingMessageList = array();
        $messageQueueMode = $this->getQueueMessageMode();
        $messageCollection = $this->getMessageCollectionBySenderIdAndDirection($senderId, $this->_define->INCOMING);

        foreach ($messageCollection as $message)
        {
            $datetime = date('Y-m-d H:i:s');
            $processingLimit = $this->_define->QUEUE_PROCESSING_LIMIT;
            // if processed or not in the processing queue limit
            if (($message->getStatus() == $this->_define->PROCESSED) || (($message->getStatus() == $this->_define->PROCESSING) && ((strtotime($datetime) - strtotime($message->getUpdatedAt())) > $processingLimit)))
                continue;

            $outgoingMessageList = $this->processIncomingMessage($message);
            if (!$outgoingMessageList)
            {
                if ($messageQueueMode != $this->_define->QUEUE_SIMPLE_RESTRICTIVE)
                    break;
            }
        }

        return $outgoingMessageList;
    }

    private function getMessageCollectionBySenderIdAndDirection($senderId, $direction)
    {
        $messageCollection = $this->_messageModel->getCollection()
            ->addFieldToFilter('status', array('neq' => $this->_define->PROCESSED))
            ->addFieldToFilter('direction', array('eq' => $direction))
            ->addFieldToFilter('sender_id', array('eq' => $senderId))
            ->setOrder('created_at', 'asc');

        return $messageCollection;
    }

    public function processOutgoingMessageQueueBySenderId($senderId)
    {
        $result = array();
        $messageQueueMode = $this->getQueueMessageMode();
        $messageCollection = $this->getMessageCollectionBySenderIdAndDirection($senderId, $this->_define->OUTGOING);

        foreach ($messageCollection as $message)
        {
            $datetime = date('Y-m-d H:i:s');
            $processingLimit = $this->_define->QUEUE_PROCESSING_LIMIT;
            // if processed or not in the processing queue limit
            if (($message->getStatus() == $this->_define->PROCESSED) || (($message->getStatus() == $this->_define->PROCESSING) && ((strtotime($datetime) - strtotime($message->getUpdatedAt())) > $processingLimit)))
                continue;

            $result = $this->processOutgoingMessage($message);
            if (!$result)
            {
                if ($messageQueueMode == $this->_define->QUEUE_SIMPLE_RESTRICTIVE)
                {
                    // only breaks the loop if it's a message that changes conversation state
                    if ($message->getCurrentCommandDetails())
                    {
                        $currentCommandDetails = json_decode($message->getCurrentCommandDetails());
                        if (isset($currentCommandDetails->conversation_state))
                            break;
                    }
                }
                else
                    break;
            }
        }

        return $result;
    }

    private function getNoMessage()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define->NO_REPLY_MESSAGE,
            'content' => '',
            'current_command_details' => json_encode(array())
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function handleConversationState($content, $senderId, $keyword = false)
    {
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $result = array();
        if ($keyword)
            $messageContent = $keyword;
        else
            $messageContent = $content;

        if ($chatbotAPI->getConversationState() == $this->_define->CONVERSATION_LIST_CATEGORIES)
        {
            $payload = $this->getCurrentMessagePayload();
            $result = $this->listProductsFromCategory($messageContent, $payload, $senderId); // $message->getMessagePayload()
        }
        else if ($chatbotAPI->getConversationState() == $this->_define->CONVERSATION_SEARCH)
        {
            $result = $this->listProductsFromSearch($messageContent, $senderId);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define->CONVERSATION_EMAIL)
        {
            $result = $this->sendEmailFromMessage($messageContent);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define->CONVERSATION_TRACK_ORDER)
        {
            $result = $this->listOrderDetailsFromOrderId($messageContent, $senderId);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define->CONVERSATION_SUPPORT)
        {
            $result = $this->getNoMessage();
        }

        return $result;
    }

    private function handlePayloadCommands($message)
    {
        return $this->processPayloadCommands($message);
    }

    private function getNotLoggedMessage()
    {
        $text = __("You have to be logged to do that.");
        return $this->getTextMessageArray($text);
    }

    private function processPayloadCommands($message)
    {
        $payload = $this->getCurrentMessagePayload();
        $result = array();
        if ($payload)
        {
            if ($payload->command == $this->_define->REORDER_COMMAND_ID)
            {
                $senderId = $message->getSenderId();
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define->LOGGED)
                    $result = $this->processReorderCommand($payload->parameter, $senderId);
                else
                    $result = $this->getNotLoggedMessage();
            }
        }

        return $result;
    }

    private function processMessageRequest($message)
    {
        // ORDER -> cancel_command, conversation_state, commands, wit_ai, errors

        $responseContent = array();
        $commandResponses = array();
        $errorMessages = array();
        $conversationStateResponses = array();
        $payloadCommandResponses = array();
        $defaultRepliesResponses = array();
        $NLPResponses = array();
        $this->setHelperMessageAttributes($message);

        // first of all must check if it's a cancel command
        $command = $this->getCurrentCommand($message->getContent());
        $cancelResponses = $this->checkCancelCommand($command);
        if ($cancelResponses)
        {
            foreach ($cancelResponses as $cancelResponse)
            {
                array_push($responseContent, $cancelResponse);
            }
        }

        if (count($responseContent) <= 0)
            $conversationStateResponses = $this->handleConversationState($message->getContent(), $message->getSenderId());
        if ($conversationStateResponses)
        {
            foreach ($conversationStateResponses as $conversationStateResponse)
            {
                array_push($responseContent, $conversationStateResponse);
            }
        }

        if (count($responseContent) <= 0)
            $payloadCommandResponses = $this->handlePayloadCommands($message);
        if ($payloadCommandResponses)
        {
            foreach ($payloadCommandResponses as $payloadCommandResponse)
            {
                array_push($responseContent, $payloadCommandResponse);
            }
        }

        $enableDefaultReplies = $this->getConfigValue($this->_configPrefix . '/general/enable_default_replies');
        if ($enableDefaultReplies == $this->_define->ENABLED)
        {
            if (count($responseContent) <= 0)
                $defaultRepliesResponses = $this->handleDefaultReplies($message);
            if ($defaultRepliesResponses)
            {
                foreach ($defaultRepliesResponses as $defaultReplyResponse)
                {
                    array_push($responseContent, $defaultReplyResponse);
                }
            }
        }

        if (count($responseContent) <= 0)
            $commandResponses = $this->handleCommands($message);
        if ($commandResponses)
        {
            foreach ($commandResponses as $commandResponse)
            {
                array_push($responseContent, $commandResponse);
            }
        }

        $enableNLPwitAI = $this->getConfigValue('werules_chatbot_general/general/enable_wit_ai');
        if ($enableNLPwitAI == $this->_define->ENABLED)
        {
            if (count($responseContent) <= 0)
                $NLPResponses = $this->handleNaturalLanguageProcessor($message); // getNLPTextMeaning
            if ($NLPResponses)
            {
                foreach ($NLPResponses as $NLPResponse)
                {
                    array_push($responseContent, $NLPResponse);
                }
            }
        }

        if (count($responseContent) <= 0)
            $errorMessages = $this->handleUnableToProcessRequest($message);
        if ($errorMessages)
        {
            foreach ($errorMessages as $errorMessage)
            {
                array_push($responseContent, $errorMessage);
            }
        }

        return $responseContent;
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
//        $outgoingMessage->setSentAt(time());
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