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
    protected $_chatbotUser;
    protected $_serializer;
    protected $_categoryHelper;
    protected $_categoryFactory;
    protected $_categoryCollectionFactory;
    protected $_storeManagerInterface;
    protected $_orderCollectionFactory;
    protected $_productCollection;
    protected $_customerRepositoryInterface;
    protected $_quoteModel;

    protected $_define;
    protected $_configPrefix;
    protected $_commandsList;
    protected $_completeCommandsList;
    protected $_currentCommand;
    protected $_messagePayload;
    protected $_chatbotAPIModel;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        StoreManagerInterface $storeManager,
        \Werules\Chatbot\Model\ChatbotAPIFactory $chatbotAPI,
        \Werules\Chatbot\Model\ChatbotUserFactory $chatbotUser,
        \Werules\Chatbot\Model\MessageFactory $message,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Quote\Model\Quote $quoteModel,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollection
    )
    {
        $this->objectManager = $objectManager;
        $this->_serializer = $serializer;
        $this->storeManager  = $storeManager;
        $this->_messageModel  = $message;
        $this->_chatbotAPI  = $chatbotAPI;
        $this->_chatbotUser  = $chatbotUser;
        $this->_configPrefix = '';
        $this->_define = new \Werules\Chatbot\Helper\Define;
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_quoteModel = $quoteModel;
        $this->_productCollection = $productCollection;
        parent::__construct($context);
    }

    public function logger($message) // TODO find a better way to to this
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/werules_chatbot.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(var_export($message, true));
    }

    private function generateRandomHashKey($len = 32)
    {
        // max length = 32
        return substr(md5(openssl_random_pseudo_bytes(20)), -$len);
    }

    public function processMessage($messageId)
    {
        $message = $this->_messageModel->create();
        $message->load($messageId);

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
        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($message->getSenderId(), 'chat_id'); // TODO

        if (!($chatbotAPI->getChatbotapiId()))
        {
            $chatbotAPI->setEnabled($this->_define::ENABLED);
            $chatbotAPI->setChatbotType($message->getChatbotType()); // TODO
            $chatbotAPI->setChatId($message->getSenderId());
            $chatbotAPI->setConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->setFallbackQty(0);
            $hash = $this->generateRandomHashKey();
            $chatbotAPI->setHashKey($hash);
            $datetime = date('Y-m-d H:i:s');
            $chatbotAPI->setCreatedAt($datetime);
            $chatbotAPI->setUpdatedAt($datetime);
            $chatbotAPI->save();
        }

        $this->setChatbotAPIModel($chatbotAPI);

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
                $outgoingMessage->setContent($content['content']);
                $outgoingMessage->setContentType($content['content_type']); // TODO
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

    private function processOutgoingMessage($messageId)
    {
        $outgoingMessage = $this->_messageModel->create();
        $outgoingMessage->load($messageId);

        $chatbotAPI = $this->getChatbotAPIModel($outgoingMessage->getSenderId());

        $result = array();
        if ($outgoingMessage->getContentType() == $this->_define::CONTENT_TEXT)
            $result = $chatbotAPI->sendMessage($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::QUICK_REPLY)
            $result = $chatbotAPI->sendQuickReply($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::IMAGE_WITH_OPTIONS)
            $result = $chatbotAPI->sendImageWithOptions($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::RECEIPT_LAYOUT)
            $result = $chatbotAPI->sendReceipt($outgoingMessage);

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
        //$messageContent = $message->getContent();
        $responseContent = array();
        $commandResponses = array();
        $conversationStateResponses = array();
        $NLPResponses = array();
        $this->setHelperMessageAttributes($message);

        // first of all must check if it's a cancel command
        $command = $this->getCurrentCommand($message->getContent());
        $cancelResponses = $this->checkCancelCommand($command, $message->getSenderId());
        if ($cancelResponses)
        {
            foreach ($cancelResponses as $cancelResponse)
            {
                array_push($responseContent, $cancelResponse);
            }
        }

        if (count($responseContent) <= 0)
            $conversationStateResponses = $this->handleConversationState($message);
        if ($conversationStateResponses)
        {
            foreach ($conversationStateResponses as $conversationStateResponse)
            {
                array_push($responseContent, $conversationStateResponse);
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

        if (count($responseContent) <= 0)
            $NLPResponses = $this->handleNaturalLanguageProcessor($message); // getNLPTextMeaning
        if ($NLPResponses)
        {
            foreach ($NLPResponses as $NLPResponse)
            {
                array_push($responseContent, $NLPResponse);
            }
        }

//        if (count($responseContent) <= 0)
//            array_push($responseContent, 'Sorry, I didnt get that'); // TODO

        return $responseContent;
    }

    private function handleNaturalLanguageProcessor($message)
    {
        $chatbotAPI = $this->getChatbotAPIModel($message->getSenderId());
        $result = array();
        $parameterValue = false;
        $parameter = false;

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
                    $entityData = $this->getCommandNLPEntityData($commandCode);
                    if (isset($entityData['confidence']))
                    {
                        $confidence = $entityData['confidence'];
                        if ($intent['confidence'] >= $confidence) // check intent confidence
                        {
                            if ($parameterValue)
                            {
                                if ($parameter['confidence'] >= $confidence) // check parameter confidence
                                    $result = $this->handleCommandsWithParameters($message, $commandString, $parameterValue, $commandCode);
                            }
                            else
                                $result = $this->processCommands($commandString, $message->getSenderId(), false, $commandCode);

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
                                                        'content_type' => $this->_define::CONTENT_TEXT,
                                                        'content' => $extraText
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
                                            'content_type' => $this->_define::CONTENT_TEXT,
                                            'content' => $extraText
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

    private function handleConversationState($message, $keyword = false)
    {
        $chatbotAPI = $this->getChatbotAPIModel($message->getSenderId());
        $result = array();
        if ($keyword)
            $messageContent = $keyword;
        else
            $messageContent = $message->getContent();

        if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_LIST_CATEGORIES)
        {
            $result = $this->listProductsFromCategory($messageContent, $this->_messagePayload); // $message->getMessagePayload()
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_SEARCH)
        {
            $result = $this->listProductsFromSearch($messageContent);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_EMAIL)
        {
            $result = $this->sendEmailFromMessage($messageContent);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_TRACK_ORDER)
        {
            $result = $this->listOrderFromOrderId($messageContent, $message->getSenderId());
        }

        if ($result)
        {
            $chatbotAPI->setConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->save();
            $this->setChatbotAPIModel($chatbotAPI);
        }

        return $result;
    }

    public function listOrderFromOrderId($messageContent, $senderId)
    {
        $result = array();
        $orderList = array();
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $ordersCollection = $this->getOrdersFromCustomerId($chatbotUser->getCustomerId());
        $ordersCollection->addFieldToFilter('increment_id', $messageContent);
        if (count($ordersCollection) > 0)
        {
            $orderObject = $this->getOrderDetailsObject($ordersCollection->getFirstItem());
            array_push($orderList, $orderObject);
        }

        if (count($orderList) > 0)
        {
            $contentType = $this->_define::RECEIPT_LAYOUT;
            $content = json_encode($orderList);
        }
        else
        {
            $content = __("Sorry, we couldn't find any order with this information.");
            $contentType = $this->_define::CONTENT_TEXT;
        }

        $responseMessage = array(
            'content_type' => $contentType,
            'content' => $content
        );
        array_push($result, $responseMessage);

        return $result;
    }

    public function listProductsFromSearch($messageContent)
    {
        $result = array();
        $productList = array();
        $productCollection = $this->getProductCollectionByName($messageContent);

        foreach ($productCollection as $product)
        {
            $productObject = $this->getProductDetailsObject($product);
            if (count($productList) < 10) // TODO
                array_push($productList, $productObject);
        }

        if (count($productList) > 0)
        {
            $contentType = $this->_define::IMAGE_WITH_OPTIONS;
            $content = json_encode($productList);
        }
        else
        {
            $content = __("Sorry, no products found for this criteria.");
            $contentType = $this->_define::CONTENT_TEXT;
        }

//        $responseMessage = array();
//        $responseMessage['content_type'] = $contentType;
//        $responseMessage['content'] = $content;
        $responseMessage = array(
            'content_type' => $contentType,
            'content' => $content
        );
        array_push($result, $responseMessage);

        return $result;
    }

    private function listProductsFromCategory($messageContent, $messagePayload = '')
    {
        $result = array();
        $productList = array();
        if ($messagePayload)
            $category = $this->getCategoryById($messagePayload);
        else
            $category = $this->getCategoryByName($messageContent);

        $productCollection = $this->getProductsFromCategoryId($category->getId());

        foreach ($productCollection as $product)
        {
            $productObject = $this->getProductDetailsObject($product);
            if (count($productList) < 10) // TODO
                array_push($productList, $productObject);
        }

        if (count($productList) > 0)
        {
            $contentType = $this->_define::IMAGE_WITH_OPTIONS;
            $content = json_encode($productList);
        }
        else
        {
            $content = __("Sorry, no products found in this category.");
            $contentType = $this->_define::CONTENT_TEXT;
        }

//        $responseMessage = array();
//        $responseMessage['content_type'] = $contentType;
//        $responseMessage['content'] = $content;
        $responseMessage = array(
            'content_type' => $contentType,
            'content' => $content
        );
        array_push($result, $responseMessage);

        return $result;
    }

    public function excerpt($text, $size)
    {
        if (strlen($text) > $size)
        {
            $text = substr($text, 0, $size);
            $text = substr($text, 0, strrpos($text, " "));
            $etc = " ...";
            $text = $text . $etc;
        }

        return $text;
    }

    private function logOutChatbotCustomer($senderId)
    {
        $chatbotAPI = $this->getChatbotAPIModel($senderId);

        if ($chatbotAPI->getChatbotapiId())
        {
            $chatbotAPI->setChatbotuserId(null);
            $chatbotAPI->setLogged($this->_define::NOT_LOGGED);
            $chatbotAPI->save();
            $this->setChatbotAPIModel($chatbotAPI);
            return true;
        }

        return false;
    }

    private function prepareCommandsList()
    {
        $serializedCommands = $this->getConfigValue($this->_configPrefix . '/general/commands_list');
        $commands = $this->_serializer->unserialize($serializedCommands);
        $commandsList = array();
        $completeCommandsList = array();
        foreach ($commands as $command)
        {
            $commandId = $command['command_id'];
            $completeCommandsList[$commandId] = array(
                'command_code' => $command['command_code'],
                'command_alias_list' => explode(',', $command['command_alias_list'])
            );

            if ($command['enable_command'] == $this->_define::ENABLED)
                $commandsList[$commandId] = $completeCommandsList[$commandId];
        }
        $this->setCommandsList($commandsList);
        $this->setCompleteCommandsList($completeCommandsList);
//        return $commandsList;
    }

    private function checkCancelCommand($command, $senderId)
    {
        $result = array();
        if ($command == $this->_define::CANCEL_COMMAND_ID)
            $result = $this->processCancelCommand($senderId);

        return $result;
    }

    private function processCommands($messageContent, $senderId, $setStateOnly = false, $command = false, $payload = false)
    {
//        $messageContent = $message->getContent();
        $result = array();
        $state = false;
        if (!$command)
            $command = $this->getCurrentCommand($messageContent);

        if (!$payload)
            $payload = $this->getCurrentMessagePayload();

        if ($command)
        {
            if ($command == $this->_define::START_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processStartCommand();
            }
            else if ($command == $this->_define::LIST_CATEGORIES_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processListCategoriesCommand();
                $state = $this->_define::CONVERSATION_LIST_CATEGORIES;
            }
            else if ($command == $this->_define::SEARCH_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processSearchCommand();
                $state = $this->_define::CONVERSATION_SEARCH;
            }
            else if ($command == $this->_define::LOGIN_COMMAND_ID)
            {
                if (!$setStateOnly)
                {
                    $chatbotAPI = $this->getChatbotAPIModel($senderId);
                    if ($chatbotAPI->getLogged() == $this->_define::NOT_LOGGED)
                        $result = $this->processLoginCommand($senderId);
                    else
                    {
                        $text = __("You are already logged.");
                        $result = $this->getTextMessageArray($text);
                    }
                }
            }
            else if ($command == $this->_define::LIST_ORDERS_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModel($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processListOrdersCommand($senderId);
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($command == $this->_define::REORDER_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModel($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processReorderCommand();
                }
                else
                        $result = $this->getNotLoggedMessage();
            }
            else if ($command == $this->_define::ADD_TO_CART_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModel($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                    {
                        if ($payload)
                            $result = $this->processAddToCartCommand($senderId, $payload);
                        else
                            $result = $this->getErrorMessage();
                    }
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($command == $this->_define::CHECKOUT_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processCheckoutCommand();
            }
            else if ($command == $this->_define::CLEAR_CART_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processClearCartCommand();
            }
            else if ($command == $this->_define::TRACK_ORDER_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModel($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processTrackOrderCommand();
                    $state = $this->_define::CONVERSATION_TRACK_ORDER;
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($command == $this->_define::SUPPORT_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processSupportCommand();
            }
            else if ($command == $this->_define::SEND_EMAIL_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processSendEmailCommand();
                $state = $this->_define::CONVERSATION_EMAIL;
            }
            else if ($command == $this->_define::CANCEL_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processCancelCommand($senderId);
            }
            else if ($command == $this->_define::HELP_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processHelpCommand();
            }
            else if ($command == $this->_define::ABOUT_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processAboutCommand();
            }
            else if ($command == $this->_define::LOGOUT_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModel($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processLogoutCommand($senderId);
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($command == $this->_define::REGISTER_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModel($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::NOT_LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processRegisterCommand();
                }
                else
                {
                    $text = __("You are already registered.");
                    $result = $this->getTextMessageArray($text);
                }
            }
            else // should never fall in here
            {
                $result = $this->getErrorMessage();
            }
        }
        if ($state && (($result) || $setStateOnly)) // TODO
            $this->updateConversationState($senderId, $state);

        return $result;
    }

    private function handleCommandsWithParameters($message, $command, $keyword, $commandCode)
    {
        $result = $this->processCommands($command, $message->getSenderId(), true, $commandCode); // should return empty array
        if ($result)
            return $result; // if this happens, means there's an error message

        $result = $this->handleConversationState($message, $keyword);
        return $result;
    }

    private function handleCommands($message)
    {
        $result = $this->processCommands($message->getContent(), $message->getSenderId());

        return $result;
    }

    private function updateConversationState($senderId, $state)
    {
        $chatbotAPI = $this->getChatbotAPIModel($senderId);

        if ($chatbotAPI->getChatbotapiId())
        {
            $chatbotAPI->setConversationState($state);
            $datetime = date('Y-m-d H:i:s');
            $chatbotAPI->setUpdatedAt($datetime);
            $chatbotAPI->save();
            $this->setChatbotAPIModel($chatbotAPI);

            return true;
        }

        return false;
    }

    private function sendEmailFromMessage($text)
    {
        $result = array();
        $response = $this->sendZendEmail($text);
        if ($response)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => __("Email sent.")
            );
        }
        else
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => __("Sorry, I wasn't able to send an email this time. Please try again later.")
            );
        }

        array_push($result, $responseMessage);
        return $result;
    }

    private function sendZendEmail($text) // TODO TODO TODO
    {
        $storeName = 'store_name';
        $storeEmail = 'sample@sample.com';// TODO

        $url = __("Not informed");
        $customerEmail = __("Not informed");
        $customerName = __("Not informed");

        $mail = new \Zend_Mail('UTF-8');

        $emailBody =
            __("Message from chatbot customer") . "<br><br>" .
            __("Customer name") . ": " .
            $customerName . "<br>" .
            __("Message") . ":<br>" .
            $text . "<br><br>" .
            __("Contacts") . ":<br>" .
            __("Chatbot") . ": " . $url . "<br>" .
            __("Email") . ": " . $customerEmail . "<br>";

        $mail->setBodyHtml($emailBody);
        $mail->setFrom($storeEmail, $storeName);
        $mail->addTo($storeEmail, $storeName);
        $mail->setSubject(__("Contact from chatbot"));

        try {
            $mail->send();
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // SETS
    private function setCommandsList($commandsList)
    {
        $this->_commandsList = $commandsList;
    }

    private function setCompleteCommandsList($completeCommandsList)
    {
        $this->_completeCommandsList = $completeCommandsList;
    }

    private function setChatbotAPIModel($chatbotAPI)
    {
        $this->_chatbotAPIModel = $chatbotAPI;
    }

    private function setHelperMessageAttributes($message)
    {
        if ($message->getChatbotType() == $this->_define::MESSENGER_INT)
            $this->_configPrefix = 'werules_chatbot_messenger';

        $this->setCurrentMessagePayload($message->getMessagePayload());
        $this->setCurrentCommand($message->getContent()); // ignore output
        $this->prepareCommandsList();
    }

    private function setCurrentMessagePayload($messagePayload)
    {
        if ($messagePayload)
            $this->_messagePayload = $messagePayload;

        return false;
    }

    private function setCurrentCommand($messageContent)
    {
        if (!isset($this->_commandsList))
            $this->_commandsList = $this->getCommandsList();

        foreach ($this->_commandsList as $key => $command)
        {
            if (strtolower($messageContent) == strtolower($command['command_code'])) // TODO add configuration for this
            {
                $this->_currentCommand = $key;
                return $key;
            }
        }

        return false;
    }

    // GETS
    private function getCurrentMessagePayload()
    {
        if (isset($this->_messagePayload))
            return $this->_messagePayload;

        return false;
    }

    private function getCurrentCommand($messageContent)
    {
        if (isset($this->_currentCommand))
            return $this->_currentCommand;

        return $this->setCurrentCommand($messageContent);
    }

    private function getCommandsList()
    {
        if (isset($this->_commandsList))
            return $this->_commandsList;

        // should never get here
        $this->prepareCommandsList();
        return $this->_commandsList;
    }

    private function getCompleteCommandsList()
    {
        if (isset($this->_completeCommandsList))
            return $this->_completeCommandsList;

        // should never get here
        $this->prepareCommandsList();
        return $this->_completeCommandsList;
    }

    private function getStoreURL($extraPath, $path = false)
    {
        if ($path)
            return $this->_storeManagerInterface->getStore()->getBaseUrl($path) . $extraPath;

        return $this->_storeManagerInterface->getStore()->getBaseUrl() . $extraPath;
    }

    private function getMediaURL($path)
    {
        return $this->getStoreURL($path, \Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    private function getTextMessageArray($text)
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => $text
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function getNotLoggedMessage()
    {
        $text = __("You have to be logged to do that.");
        return $this->getTextMessageArray($text);
    }

    private function getErrorMessage()
    {
        $text = __("Sorry, I didn't understand that.");
        return $this->getTextMessageArray($text);
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

    private function getCommandText($commandId)
    {
        $commands = $this->getCompleteCommandsList();
        if (isset($commands[$commandId]['command_code']))
            return $commands[$commandId]['command_code'];

        return '';
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getOrdersFromCustomerId($customerId)
    {
        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'desc')
        ;

        return $orders;
    }

    private function getProductCollection()
    {
        $collection = $this->_productCollection->create();
        $collection->addAttributeToSelect('*');

        return $collection;
    }

    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted , $asCollection, $toLoad);
    }

    public function getProductCollectionByName($searchString)
    {
        $collection = $this->getProductCollection();
        $collection->addAttributeToFilter(array(
            array('attribute' => 'name', 'like' => '%' . $searchString . '%'),
            array('attribute' => 'sku', 'like' => '%' . $searchString . '%'),
        ));
//        $collection->setPageSize(3); // fetching only 3 products
        return $collection;
    }

    public function getCategoryById($categoryId)
    {
        $category = $this->_categoryFactory->create();
        $category->load($categoryId);

        return $category;
    }

    public function getCategoryByName($name)
    {
        return $this->getCategoriesByName($name)->getFirstItem();
    }

    public function getCategoriesByName($name)
    {
        $categoryCollection = $this->_categoryCollectionFactory->create();
        $categoryCollection = $categoryCollection->addAttributeToFilter('name', $name);

        return $categoryCollection;
    }

    public function getProductsFromCategoryId($categoryId)
    {
        $productCollection = $this->getCategoryById($categoryId)->getProductCollection();
        $productCollection->addAttributeToSelect('*');

        return $productCollection;
    }

    private function getProductDetailsObject($product)
    {
        $element = array();
        if ($product->getId())
        {
            $productName = $product->getName();
            $productUrl = $product->getProductUrl();
//            $productImage = $product->getImage();
            $productImage = $this->getMediaURL('catalog/product') . $product->getImage();
            // TODO add placeholder
            $options = array(
                array(
                    'type' => 'postback',
                    'title' => $this->getCommandText($this->_define::ADD_TO_CART_COMMAND_ID),
                    'payload' => $product->getId()
                ),
                array(
                    'type' => 'web_url',
                    'title' => __("Visit product's page"),
                    'url' => $productUrl
                )
            );
            $element = array(
                'title' => $productName,
                'item_url' => $productUrl,
                'image_url' => $productImage,
                'subtitle' => $this->excerpt($product->getShortDescription(), 60),
                'buttons' => $options
            );
            //array_push($result, $element);
        }

        return $element;
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
                    $confidence = $this->_define::DEFAULT_MIN_CONFIDENCE;
                    $extraText = '';
                    if (isset($entity['enable_reply']))
                    {
                        if ($entity['enable_reply'] == $this->_define::ENABLED)
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

    private function getOrderDetailsObject($order) // TODO add link to product name
    {
        $detailedOrderObject = array();
        if ($order->getId())
        {
            $orderNumber = $order->getIncrementId();
            $customerName = $order->getCustomerName();
            $orderUrl = $this->getStoreURL('sales/order/view/order_id/' . $order->getId());
            $currency = $order->getOrderCurrencyCode();
            $createdAt = strtotime($order->getCreatedAt());
            $elements = array();
            $items = $order->getAllVisibleItems();
            foreach ($items as $item)
            {
                $productCollection = $this->getProductCollection();
                $productCollection->addFieldToFilter('entity_id', $item->getProductId());
                $product = $productCollection->getFirstItem();
                $productImage = $this->getMediaURL('catalog/product') . $product->getImage();

                $element = array(
                    'title' => $item->getName(),
                    'subtitle' => $this->excerpt($item->getShortDescription(), 30),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'price' => $item->getPrice(),
                    'currency' => $currency,
                    'image_url' => $productImage
                );
                array_push($elements, $element);
            }

            $shippingAddress = $order->getShippingAddress();
            $streetOne = $shippingAddress->getStreet()[0];
            $streetTwo = '';
            if (count($shippingAddress->getStreet()) > 1)
                $streetTwo = $shippingAddress->getStreet()[1];
            $address = array(
                'street_1' => $streetOne,
                'street_2' => $streetTwo,
                'city' => $shippingAddress->getCity(),
                'postal_code' => $shippingAddress->getPostcode(),
                'state' => $shippingAddress->getRegion(),
                'country' => $shippingAddress->getCountryId()
            );

            $summary = array(
                'subtotal' => $order->getSubtotal(),
                'shipping_cost' => $order->getShippingAmount(),
                'total_tax' => $order->getTaxAmount(),
                'total_cost' => $order->getGrandTotal()
            );

            $detailedOrderObject = array(
                'template_type' => 'receipt',
                'recipient_name' => $customerName,
                'order_number' => $orderNumber,
                'currency' => $currency,
                'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
                'order_url' => $orderUrl,
                'timestamp' => $createdAt,
                'elements' => $elements,
                'address' => $address,
                'summary' => $summary
            );
        }

        return $detailedOrderObject;
    }
    private function getChatbotAPIModel($senderId)
    {
        if (isset($this->_chatbotAPIModel))
            return $this->_chatbotAPIModel;

        // should never get here
        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($senderId, 'chat_id'); // TODO
        $this->setChatbotAPIModel($chatbotAPI);

        return $chatbotAPI;
    }

    public function getChatbotuserBySenderId($senderId)
    {
        $chatbotAPI = $this->getChatbotAPIModel($senderId);
        $chatbotUser = $this->_chatbotUser->create();

        if ($chatbotAPI->getChatbotapiId())
        {
            $chatbotUser->load($chatbotAPI->getChatbotuserId(), 'chatbotuser_id'); // TODO
//            if ($chatbotUser->getChatbotuserId())
//                return $chatbotUser;
        }

        return $chatbotUser;
    }

    // COMMANDS FUNCTIONS
    private function processListCategoriesCommand()
    {
        $result = array();
        $categories = $this->getStoreCategories(false,false,true);
        $quickReplies = array();
        foreach ($categories as $category)
        {
            $categoryName = $category->getName();
            if ($categoryName)
            {
                $quickReply = array(
                    'content_type' => 'text', // TODO messenger pattern
                    'title' => $categoryName,
                    'payload' => $category->getId()
                );
                array_push($quickReplies, $quickReply);
            }
        }
        $contentObject = new \stdClass();
        $contentObject->message = __("Please select a category.");
        $contentObject->quick_replies = $quickReplies;
//        $responseMessage = array();
//        $responseMessage['content_type'] = $this->_define::QUICK_REPLY;
//        $responseMessage['content'] = json_encode($contentObject);
        $responseMessage = array(
            'content_type' => $this->_define::QUICK_REPLY,
            'content' => json_encode($contentObject)
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processStartCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => 'you just sent the START command!' // TODO
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processSearchCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("Sure, send me the name of the product you're looking for.")
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processLoginCommand($senderId)
    {
        $chatbotAPI = $this->getChatbotAPIModel($senderId);

        $result = array();
        $loginUrl = $this->getStoreURL('chatbot/customer/login/hash/' . $chatbotAPI->getHashKey());
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("To link your account to this Chatbot, access %1", $loginUrl)
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processLogoutCommand($senderId)
    {
        $response = $this->logOutChatbotCustomer($senderId);
        $result = array();
        if ($response)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => __("Ok, you're logged out.")
            );
            array_push($result, $responseMessage);
        }
        else
            $result = $this->getErrorMessage();

        return $result;
    }

    private function processRegisterCommand()
    {
        $result = array();
        $registerUrl = $this->getStoreURL('customer/account/create');
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("Access %1 to register a new account on our shop.", $registerUrl)
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processListOrdersCommand($senderId)
    {
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $ordersCollection = $this->getOrdersFromCustomerId($chatbotUser->getCustomerId());
        $result = array();
        $orderList = array();

        foreach ($ordersCollection as $order)
        {
            $orderObject = $this->getOrderDetailsObject($order);
//            $this->logger(json_encode($productObject));
            if (count($orderList) < 10) // TODO
                array_push($orderList, $orderObject);
        }

        if (count($orderList) > 0)
        {
            $contentType = $this->_define::RECEIPT_LAYOUT;
            $content = json_encode($orderList);
        }
        else
        {
            $content = __("This account has no orders.");
            $contentType = $this->_define::CONTENT_TEXT;
        }

        $responseMessage = array(
            'content_type' => $contentType,
            'content' => $content
        );
        array_push($result, $responseMessage);

        return $result;
    }

    private function processReorderCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => 'The REORDER command is still under development' // TODO
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function addProductToCustomerCart($productId, $customerId) // TODO simple products only for now
    {
        $productCollection = $this->getProductCollection();
        $productCollection->addFieldToFilter('entity_id', $productId);
        $product = $productCollection->getFirstItem();

        $customer = $this->_customerRepositoryInterface->getById($customerId);
        $quote = $this->_quoteModel->loadByCustomer($customer);
        if (!$quote->getId())
        {
            $quote->setCustomer($customer);
            $quote->setIsActive(1);
            $quote->setStoreId($this->storeManager->getStore()->getId());
        }

        $quote->addProduct($product, 1); // TODO
        $quote->collectTotals()->save();
    }

    private function processAddToCartCommand($senderId, $productId)
    {
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $this->addProductToCustomerCart($productId, $chatbotUser->getCustomerId());
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("Ok, I just add the product to your cart, to checkout send '%1'", $this->getCommandText($this->_define::CHECKOUT_COMMAND_ID))
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processCheckoutCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => 'The REORDER command is still under development' // TODO
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processClearCartCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => 'The CLEAR_CART command is still under development' // TODO
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processTrackOrderCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("Ok, send me the order number you're looking for.")
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processSupportCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => 'The SUPPORT command is still under development' // TODO
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processSendEmailCommand()
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("Sure, send me the email content.")
        );
        array_push($result, $responseMessage);
        return $result;
    }

    private function processCancelCommand($senderId)
    {
        $result = array();
        $responseMessage = array(
            'content_type' => $this->_define::CONTENT_TEXT,
            'content' => __("Ok, canceled.")
        );
        array_push($result, $responseMessage);
        $this->updateConversationState($senderId, $this->_define::CONVERSATION_STARTED);
        return $result;
    }

    private function processHelpCommand()
    {
        $result = array();
        $text = $this->getConfigValue($this->_configPrefix . '/general/help_message');
        if ($text)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => $text
            );
            array_push($result, $responseMessage);
        }
        return $result;
    }

    private function processAboutCommand()
    {
        $result = array();
        $text = $this->getConfigValue($this->_configPrefix . '/general/about_message');
        if ($text)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => $text
            );
            array_push($result, $responseMessage);
        }
        return $result;
    }
}