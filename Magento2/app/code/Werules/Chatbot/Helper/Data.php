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

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $storeManager;
//    protected $objectManager;
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
    protected $_imageHelper;
    protected $_stockRegistry;
    protected $_stockFilter;
    protected $_priceHelper;
//    protected $_storeConfig;
//    protected $_cartModel;
//    protected $_cartManagementInterface;
//    protected $_cartRepositoryInterface;

    // not from construct parameters
    protected $_define;
    protected $_messageQueueMode;
    protected $_configPrefix;
    protected $_commandsList;
    protected $_completeCommandsList;
    protected $_currentCommand;
    protected $_messagePayload;
    protected $_chatbotAPIModel;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
//        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Werules\Chatbot\Model\ChatbotAPIFactory $chatbotAPI,
        \Werules\Chatbot\Model\ChatbotUserFactory $chatbotUser,
        \Werules\Chatbot\Model\MessageFactory $messageFactory,
        \Werules\Chatbot\Model\Message $message,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Quote\Model\Quote $quoteModel,
//        \Magento\Checkout\Model\Cart $cartModel,
//        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
//        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
//        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollection
    )
    {
        $this->_serializer = $serializer;
        $this->storeManager  = $storeManager;
        $this->_messageModel  = $message;
        $this->_messageModelFactory  = $messageFactory;
        $this->_chatbotAPIFactory  = $chatbotAPI;
        $this->_chatbotUserFactory  = $chatbotUser;
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_quoteModel = $quoteModel;
        $this->_stockRegistry = $stockRegistry;
        $this->_stockFilter = $stockFilter;
        $this->_imageHelper = $imageHelper;
        $this->_priceHelper = $priceHelper;
        $this->_productCollection = $productCollection;
//        $this->objectManager = $objectManager;
//        $this->_cartModel = $cartModel;
//        $this->_cartManagementInterface = $cartManagementInterface;
//        $this->_cartRepositoryInterface = $cartRepositoryInterface;
//        $this->_storeConfig = $scopeConfig;
        $this->_define = new \Werules\Chatbot\Helper\Define;
        parent::__construct($context);
    }

    public function logger($text, $file = 'werules_chatbot.log') // TODO find a better way to to this
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $file);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(var_export($text, true));
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

    private function generateRandomHashKey($len = 32)
    {
        // max length = 32
        return substr(md5(openssl_random_pseudo_bytes(20)), -$len);
    }

    public function processIncomingMessageQueueBySenderId($senderId)
    {
        $result = array();
        $messageQueueMode = $this->getQueueMessageMode();
        $messageCollection = $this->getMessageCollectionBySenderIdAndDirection($senderId, $this->_define::INCOMING);

        $this->logger('total incoming: ' . count($messageCollection));
        foreach ($messageCollection as $message)
        {
            $datetime = date('Y-m-d H:i:s');
            $processingLimit = $this->_define::QUEUE_PROCESSING_LIMIT;
            // if processed or not in the processing queue limit
            if (($message->getStatus() == $this->_define::PROCESSED) || !(($message->getStatus() == $this->_define::PROCESSING) && ((strtotime($datetime) - strtotime($message->getUpdatedAt())) > $processingLimit)))
                continue;

            $result = $this->processIncomingMessage($message);
//            if ($result)
//                $message->updateIncomingMessageStatus($this->_define::PROCESSED);
            if ($result)
            {
//                $this->logger('total outgoing: ' . count($result));
//                foreach ($result as $outgoingMessage)
//                {
//                    $this->logger('outgoing: ' . $outgoingMessage->getMessageId());
//                    $result = $this->processOutgoingMessage($outgoingMessage);
//                    if (!$result)
//                    {
//                        $result = false;
//                        break;
//                    }
//                }
            }
            else //if (!$result)
                $result = false;

            if (!$result)
                break;
        }

        return $result;
    }

    public function processOutgoingMessageQueueBySenderId($senderId)
    {
        $result = array();
        $messageQueueMode = $this->getQueueMessageMode();
        $messageCollection = $this->getMessageCollectionBySenderIdAndDirection($senderId, $this->_define::OUTGOING);

        foreach ($messageCollection as $message)
        {
            $datetime = date('Y-m-d H:i:s');
            $processingLimit = $this->_define::QUEUE_PROCESSING_LIMIT;
            // if processed or not in the processing queue limit
            if (($message->getStatus() == $this->_define::PROCESSED) || !(($message->getStatus() == $this->_define::PROCESSING) && ((strtotime($datetime) - strtotime($message->getUpdatedAt())) > $processingLimit)))
                continue;

            $result = $this->processOutgoingMessage($message);
//            if ($result)
//                $message->updateOutgoingMessageStatus($this->_define::PROCESSED);
            if (!$result)
            {
                $result = false;
                break;
            }
        }

        return $result;
    }

//    public function processMessage($messageId)
//    {
//        $message = $this->getMessageModelById($messageId);
//        $result = false;
//
//        if ($message->getMessageId())
//        {
//            $message->updateMessageStatus($this->_define::PROCESSING);
//            if ($message->getDirection() == $this->_define::INCOMING)
//                $result = $this->processIncomingMessage($message);
//            else //if ($message->getDirection() == $this->_define::OUTGOING)
//                $result = $this->processOutgoingMessage($message);
//        }
//
//        return $result;
//    }

    public function processIncomingMessage($message)
    {
        $messageQueueMode = $this->getQueueMessageMode();
        if ($messageQueueMode == $this->_define::QUEUE_NONE)
            $message->updateIncomingMessageStatus($this->_define::PROCESSED);
        else
            $message->updateIncomingMessageStatus($this->_define::PROCESSING);

        $this->setConfigPrefix($message->getChatbotType());
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($message->getSenderId());
        $result = array();

        if (!($chatbotAPI->getChatbotapiId()))
        {
            $chatbotAPI = $this->createChatbotAPI($chatbotAPI, $message);
            $welcomeMessage = $this->getWelcomeMessage($message);
            if ($welcomeMessage)
                array_push($result, $welcomeMessage);
        }

        $enabled = $this->getConfigValue($this->_configPrefix . '/general/enable');
        if ($enabled == $this->_define::DISABLED)
            $outgoingMessages = $this->getDisabledMessage($message);
        else if ($chatbotAPI->getEnabled() == $this->_define::DISABLED)
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

        if ($result)
            $message->updateIncomingMessageStatus($this->_define::PROCESSED);

//        $this->logger("Message ID -> " . $message->getMessageId());
//        $this->logger("Message Content -> " . $message->getContent());
//        $this->logger("ChatbotAPI ID -> " . $chatbotAPI->getChatbotapiId());

        return $result;
    }

    private function prepareOutgoingMessage($message)
    {
        $responseContents = $this->processMessageRequest($message);
        $outgoingMessages = array();

        if ($responseContents)
        {
//            $result = $message->updateIncomingMessageStatus($this->_define::PROCESSED);
//            if ($result) // TODO

            foreach ($responseContents as $content)
            {
                // first guarantee outgoing message is saved
                $outgoingMessage = $this->createOutgoingMessage($message, $content);
                array_push($outgoingMessages, $outgoingMessage);
            }

//            if (count($responseContents) != count($outgoingMessages)) // TODO

//            foreach ($outgoingMessages as $outMessage)
//            {
//                // then process outgoing message
//                $this->processOutgoingMessage($outMessage); // ignore output
//            }
        }

        return $outgoingMessages;
    }

    public function processOutgoingMessage($outgoingMessage)
    {
        $messageQueueMode = $this->getQueueMessageMode();
        if ($messageQueueMode == $this->_define::QUEUE_NONE)
            $outgoingMessage->updateOutgoingMessageStatus($this->_define::PROCESSED);
        else
            $outgoingMessage->updateOutgoingMessageStatus($this->_define::PROCESSING);

        $chatbotAPI = $this->getChatbotAPIModelBySenderId($outgoingMessage->getSenderId());
        $result = array();
        if ($outgoingMessage->getContentType() == $this->_define::CONTENT_TEXT)
            $result = $chatbotAPI->sendMessage($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::QUICK_REPLY)
            $result = $chatbotAPI->sendQuickReply($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::IMAGE_WITH_OPTIONS)
            $result = $chatbotAPI->sendImageWithOptions($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::RECEIPT_LAYOUT)
            $result = $chatbotAPI->sendReceiptList($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::TEXT_WITH_OPTIONS) // LIST_WITH_IMAGE
            $result = $chatbotAPI->sendMessageWithOptions($outgoingMessage);

        if ($result)
            $outgoingMessage->updateOutgoingMessageStatus($this->_define::PROCESSED);

//        $this->logger("Outgoing Message ID -> " . $outgoingMessage->getMessageId());
//        $this->logger("Outgoing Message Content -> " . $outgoingMessage->getContent());
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
        $NLPResponses = array();
        $this->setHelperMessageAttributes($message);
        $this->setLastCommandDetails($message);

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
        if ($enableNLPwitAI == $this->_define::ENABLED)
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

    private function getWelcomeMessage($message)
    {
//        $this->setHelperMessageAttributes($message);
        $outgoingMessage = array();
        $text = $this->getConfigValue($this->_configPrefix . '/general/welcome_message');
        if ($text != '')
        {
            $contentObj = $this->getTextMessageArray($text);
            $outgoingMessage = $this->createOutgoingMessage($message, reset($contentObj)); // TODO reset -> gets first item of array
//            $this->processOutgoingMessage($outgoingMessage);
        }

        return $outgoingMessage;
    }

    private function getDisabledByCustomerMessage($message)
    {
        $outgoingMessages = array();
        $text = __("To chat with me, please enable Messenger on your account chatbot settings.");
        $contentObj = $this->getTextMessageArray($text);
        $outgoingMessage = $this->createOutgoingMessage($message, reset($contentObj)); // TODO reset -> gets first item of array
        if ($outgoingMessage)
            array_push($outgoingMessages, $outgoingMessage);
//        $this->processOutgoingMessage($outgoingMessage);

        return $outgoingMessages;
    }

    private function handleUnableToProcessRequest($message)
    {
//        $responseContent = array();
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
//                $this->setChatbotAPIModel($chatbotAPI); // does not need because it's the last request
            }
            else
                $responseContent = $this->getErrorMessage();

//            array_push($responseContent, $contentObj);
        }
        else
        {
            $chatbotAPI->updateChatbotAPIFallbackQty($fallbackQty + 1);
//            $this->setChatbotAPIModel($chatbotAPI); // does not need because it's the last request
//            array_push($responseContent, $this->getTextMessageArray(__("Sorry, I didn't understand that.")));
            $responseContent = $this->getTextMessageArray(__("Sorry, I didn't understand that."));
        }

        return $responseContent;
    }

    private function handleNaturalLanguageProcessor($message)
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

    private function handleConversationState($content, $senderId, $keyword = false)
    {
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $result = array();
        if ($keyword)
            $messageContent = $keyword;
        else
            $messageContent = $content;

        if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_LIST_CATEGORIES)
        {
            $payload = $this->getCurrentMessagePayload();
            $result = $this->listProductsFromCategory($messageContent, $payload, $senderId); // $message->getMessagePayload()
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_SEARCH)
        {
            $result = $this->listProductsFromSearch($messageContent, $senderId);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_EMAIL)
        {
            $result = $this->sendEmailFromMessage($messageContent);
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_TRACK_ORDER)
        {
            $result = $this->listOrderFromOrderId($messageContent, $senderId);
        }

        if ($result)
        {
            $chatbotAPI->updateConversationState($this->_define::CONVERSATION_STARTED);
            $this->setChatbotAPIModel($chatbotAPI);
        }

        return $result;
    }

    private function listOrderFromOrderId($messageContent, $senderId)
    {
        $result = array();
        $orderList = array();
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $ordersCollection = $this->getOrdersFromCustomerId($chatbotUser->getCustomerId());
        $ordersCollection->addFieldToFilter('increment_id', $messageContent);
        if (count($ordersCollection) > 0)
        {
            $orderObject = $this->getImageWithOptionsOrderObject($ordersCollection->getFirstItem());
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

    private function getDisabledMessage($message)
    {
        $outgoingMessages = array();
//        $this->setHelperMessageAttributes($message);
        $text = $this->getConfigValue($this->_configPrefix . '/general/disabled_message');

        if ($text != '')
            $contentObj = $this->getTextMessageArray($text);
        else
            $contentObj = $this->getErrorMessage();

        $outgoingMessage = $this->createOutgoingMessage($message, reset($contentObj)); // TODO reset -> gets first item of array
//        $this->processOutgoingMessage($outgoingMessage);
        if ($outgoingMessage)
            array_push($outgoingMessages, $outgoingMessage);

        return $outgoingMessages;
    }

    private function getMessageCollectionBySenderIdAndDirection($senderId, $direction)
    {
        $messageCollection = $this->_messageModel->getCollection()
            ->addFieldToFilter('status', array('neq' => $this->_define::PROCESSED))
            ->addFieldToFilter('direction', array('eq' => $direction))
            ->addFieldToFilter('sender_id', array('eq' => $senderId))
            ->setOrder('created_at', 'asc');

        return $messageCollection;
    }

    public function getQueueMessageMode()
    {
        if (isset($this->_messageQueueMode))
            return $this->_messageQueueMode;

        $this->_messageQueueMode = $this->getConfigValue('werules_chatbot_general/general/message_queue_mode');
        return $this->_messageQueueMode;
    }

    private function getStockByProductId($productId)
    {
        $stockQty = $this->_stockRegistry->getStockItem($productId);
        if ($stockQty)
            return $stockQty;

        return 0;
    }

    private function listProductsFromSearch($messageContent, $senderId)
    {
        $result = array();
        $productList = array();
        $extraListMessage = array();
        $productCollection = $this->getProductCollectionByName($messageContent);
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());
        if (!isset($lastCommandObject->last_listed_quantity))
            return $result;

        if ($lastCommandObject->last_conversation_state == $this->_define::CONVERSATION_SEARCH)
            $startAt = $lastCommandObject->last_listed_quantity;
        else
            $startAt = 0;

        $count = 0;

        foreach ($productCollection as $product)
        {
            if ($count < $startAt)
            {
                $count++;
                continue;
            }

            $imageWithOptionsProdObj = $this->getImageWithOptionsProductObject($product);
            if (count($productList) < $this->_define::MAX_MESSAGE_ELEMENTS) // TODO
                array_push($productList, $imageWithOptionsProdObj);
        }

        $listCount = count($productList);
        $totalListCount = $listCount + $startAt;
        if (count($productCollection) > $totalListCount)
        {
            $chatbotAPI->setChatbotAPILastCommandDetails($messageContent, $totalListCount);
            $this->setChatbotAPIModel($chatbotAPI);
            if ($listCount > 0)
                $extraListMessage = $this->getListMoreMessage();
        }
        else
        {
            if (($listCount > 0) && ($startAt != 0))
                    $extraListMessage = $this->getLastListItemMessage();

            $chatbotAPI->updateConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->setChatbotAPILastCommandDetails($this->getCommandText($this->_define::LIST_MORE_COMMAND_ID), 0);
            $this->setChatbotAPIModel($chatbotAPI);
        }

        if ($listCount > 0)
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
        if ($extraListMessage)
            array_push($result, reset($extraListMessage)); // TODO reset -> gets first item of array

        return $result;
    }

    private function listProductsFromCategory($messageContent, $messagePayload, $senderId)
    {
        $result = array();
        $extraListMessage = array();
        $productCarousel = array();
        if ($messagePayload)
            $category = $this->getCategoryById($messagePayload->parameter); // instance of \stdClass
        else
            $category = $this->getCategoryByName($messageContent);

        if (!($category->getId()))
        {
            $text = __("This category doesn't seems to exist. Please try again.");
            return $this->getTextMessageArray($text);
        }

        $productCollection = $this->getProductsFromCategoryId($category->getId());
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());
        if (!isset($lastCommandObject->last_listed_quantity))
            return $result;

        if ($lastCommandObject->last_conversation_state == $this->_define::CONVERSATION_LIST_CATEGORIES)
            $startAt = $lastCommandObject->last_listed_quantity;
        else
            $startAt = 0;

        $count = 0;

        foreach ($productCollection as $product)
        {
            if ($count < $startAt)
            {
                $count++;
                continue;
            }

            $imageWithOptionsProdObj = $this->getImageWithOptionsProductObject($product);
            if (count($productCarousel) < $this->_define::MAX_MESSAGE_ELEMENTS) // TODO
                array_push($productCarousel, $imageWithOptionsProdObj);
        }

        $listCount = count($productCarousel);
        $totalListCount = $listCount + $startAt;
        if (count($productCollection) > $totalListCount)
        {
            $chatbotAPI->setChatbotAPILastCommandDetails($messageContent, $totalListCount);
            $this->setChatbotAPIModel($chatbotAPI);
            if ($listCount > 0)
                $extraListMessage = $this->getListMoreMessage();
        }
        else
        {
            if (($listCount > 0) && ($startAt != 0))
                    $extraListMessage = $this->getLastListItemMessage();

            $chatbotAPI->updateConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->setChatbotAPILastCommandDetails($this->getCommandText($this->_define::LIST_MORE_COMMAND_ID), 0);
            $this->setChatbotAPIModel($chatbotAPI);
        }

        if ($listCount > 0)
        {
            $contentType = $this->_define::IMAGE_WITH_OPTIONS;
            $content = json_encode($productCarousel);
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
        if ($extraListMessage)
            array_push($result, reset($extraListMessage)); // TODO reset -> gets first item of array

        return $result;
    }

    private function prepareCommandsList()
    {
        if (isset($this->_commandsList) || isset($this->_completeCommandsList))
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

            if ($command['enable_command'] == $this->_define::ENABLED)
                $commandsList[$commandId] = $completeCommandsList[$commandId];
        }
        $this->setCommandsList($commandsList);
        $this->setCompleteCommandsList($completeCommandsList);
//        return $commandsList;

        return true;
    }

    private function checkCancelCommand($command, $senderId)
    {
        $result = array();
        if ($command == $this->_define::CANCEL_COMMAND_ID)
            $result = $this->processCancelCommand($senderId);

        return $result;
    }

    private function processPayloadCommands($message)
    {
        $payload = $this->getCurrentMessagePayload();
        $result = array();
        if ($payload)
        {
            if ($payload->command == $this->_define::REORDER_COMMAND_ID)
            {
                $senderId = $message->getSenderId();
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                    $result = $this->processReorderCommand($payload->parameter, $senderId);
                else
                    $result = $this->getNotLoggedMessage();
            }
        }

        return $result;
    }

    private function processCommands($messageContent, $senderId, $setStateOnly = false, $command = '', $payload = false)
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
                    $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
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
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processListOrdersCommand($senderId);
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
//            else if ($command == $this->_define::REORDER_COMMAND_ID)
//            {
//                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
//                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
//                {
//                    if (!$setStateOnly)
//                        $result = $this->processReorderCommand();
//                }
//                else
//                        $result = $this->getNotLoggedMessage();
//            }
            else if ($command == $this->_define::ADD_TO_CART_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
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
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                if ($chatbotAPI->getLogged() == $this->_define::LOGGED)
                {
                    if (!$setStateOnly)
                        $result = $this->processCheckoutCommand($senderId);
                }
                else
                    $result = $this->getNotLoggedMessage();
            }
            else if ($command == $this->_define::CLEAR_CART_COMMAND_ID)
            {
                if (!$setStateOnly)
                    $result = $this->processClearCartCommand($senderId);
            }
            else if ($command == $this->_define::TRACK_ORDER_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
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
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
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
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
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
            else if ($command == $this->_define::LIST_MORE_COMMAND_ID)
            {
                $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
                $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());

                if (!$setStateOnly)
                    $result = $this->processListMore($lastCommandObject, $senderId);
            }
            else // should never fall in here
            {
                $result = $this->getConfusedMessage();
            }
        }
        if ($state && (($result) || $setStateOnly)) // TODO
        {
            $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
            $chatbotAPI->updateConversationState($state);
            $this->setChatbotAPIModel($chatbotAPI);
        }

        return $result;
    }

    private function handleCommandsWithParameters($message, $command, $keyword, $commandCode)
    {
        $result = $this->processCommands($command, $message->getSenderId(), true, $commandCode); // should return empty array
        if ($result)
            return $result; // if this happens, means there's an error message

        $result = $this->handleConversationState($message->getContent(), $message->getSenderId(), $keyword);
        return $result;
    }

    private function handlePayloadCommands($message)
    {
        return $this->processPayloadCommands($message);
    }

    private function handleCommands($message)
    {
        $result = $this->processCommands($message->getContent(), $message->getSenderId());

        return $result;
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

    private function addProductToCustomerCart($productId, $customerId, $qty = 1) // TODO simple products only for now
    {
        $stock = $this->getStockByProductId($productId);
        if ($stock->getId())
        {
            $stockQty = (int)$stock->getQty();
            if ($stockQty > 0)
            {
                if ($stockQty < $qty)
                    $qty = $stockQty;
                $productCollection = $this->getProductCollection();
                $productCollection->addFieldToFilter('entity_id', $productId);
                $product = $productCollection->getFirstItem();

                if ($product->getId())
                {
                    $quote = $this->getQuoteByCustomerId($customerId);
                    $quote->addProduct($product, $qty); // TODO
                    $quote->collectTotals()->save();

                    return true;
                }
            }
        }

        return false;
    }

    private function clearCustomerCart($customerId)
    {
        // TODO find a way to update mini cart
        $quote = $this->getQuoteByCustomerId($customerId);
        if ($quote->getId())
        {
            $quote->removeAllItems();
            $quote->setItemsCount(0);
            $quote->save();

            return true;
        }

        return false;
    }

    // CREATE

    public function createChatbotAPI($chatbotAPI, $message)
    {
        $chatbotAPI->setEnabled($this->_define::ENABLED);
        $chatbotAPI->setChatbotType($message->getChatbotType());
        $chatbotAPI->setChatId($message->getSenderId());
        $chatbotAPI->setConversationState($this->_define::CONVERSATION_STARTED);
        $chatbotAPI->setFallbackQty(0);
        $chatbotAPI->setLastCommandDetails($this->_define::LAST_COMMAND_DETAILS_DEFAULT); // TODO
        $hash = $this->generateRandomHashKey();
        $chatbotAPI->setHashKey($hash);
        $datetime = date('Y-m-d H:i:s');
        $chatbotAPI->setCreatedAt($datetime);
        $chatbotAPI->setUpdatedAt($datetime);
        $chatbotAPI->save();

        return $chatbotAPI;
    }

    public function createIncomingMessage($messageObject)
    {
        $incomingMessage = $this->_messageModelFactory->create();
        if (isset($messageObject->senderId))
        {
            $incomingMessage->setSenderId($messageObject->senderId);
            $incomingMessage->setContent($messageObject->content);
            $incomingMessage->setChatbotType($messageObject->chatType);
            $incomingMessage->setContentType($messageObject->contentType);
            $incomingMessage->setStatus($messageObject->status);
            $incomingMessage->setDirection($messageObject->direction);
            $incomingMessage->setMessagePayload($messageObject->messagePayload);
            $incomingMessage->setChatMessageId($messageObject->chatMessageId);
            $incomingMessage->setCreatedAt($messageObject->createdAt);
            $incomingMessage->setUpdatedAt($messageObject->updatedAt);
            $incomingMessage->save();
        }

        return $incomingMessage;
    }

    public function createOutgoingMessage($message, $content)
    {
        $outgoingMessage = $this->_messageModelFactory->create();
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

        return $outgoingMessage;
    }

    // SETS

    private function setLastCommandDetails($currentMessage)
    {
        $command = $this->getCurrentCommand($currentMessage->getContent());
        if ($command != $this->_define::LIST_MORE_COMMAND_ID)
        {
            $listingConversationStates = array(
                $this->_define::CONVERSATION_LIST_CATEGORIES,
                $this->_define::CONVERSATION_SEARCH
            );
            $chatbotAPI = $this->getChatbotAPIModelBySenderId($currentMessage->getSenderId());
            $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());
            if (isset($lastCommandObject->last_conversation_state))
            {
                if ((!(in_array($lastCommandObject->last_conversation_state, $listingConversationStates))) || ($command == $this->_define::LIST_ORDERS_COMMAND_ID))
                {
                    $chatbotAPI->setChatbotAPILastCommandDetails($currentMessage->getContent());
                    $this->setChatbotAPIModel($chatbotAPI);
                }
            }
        }
    }

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

    private function setConfigPrefix($chatbotType)
    {
        if (!isset($this->_configPrefix))
        {
            if ($chatbotType == $this->_define::MESSENGER_INT)
                $this->_configPrefix = 'werules_chatbot_messenger';
        }
    }

    private function setHelperMessageAttributes($message)
    {
//        $this->setConfigPrefix($message->getChatbotType());
        $this->setCurrentMessagePayload($message->getMessagePayload());
        $this->setCurrentCommand($message->getContent()); // ignore output
        $this->prepareCommandsList();
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
            return $this->storeManager->getStore()->getBaseUrl($path) . $extraPath;

        return $this->storeManager->getStore()->getBaseUrl() . $extraPath;
    }

    private function getPlaceholderImage()
    {
        return $this->_imageHelper->getDefaultPlaceholderUrl('image');
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

    private function getLastListItemMessage()
    {
        $text = __("No more items to list.");
        return $this->getTextMessageArray($text);
    }

    private function getListMoreMessage()
    {
        $text = __("To list more send '%1'.", $this->getCommandText($this->_define::LIST_MORE_COMMAND_ID));
        return $this->getTextMessageArray($text);
    }

    private function getErrorMessage()
    {
        $text = __("Something went wrong, please try again.");
        return $this->getTextMessageArray($text);
    }

    private function getConfusedMessage()
    {
        $text = __("Sorry, I didn't understand that.");
        return $this->getTextMessageArray($text);
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

    private function getOrdersFromCustomerId($customerId)
    {
        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'desc')
        ;

        return $orders;
    }

    private function getOrderFromOrderId($orderId)
    {
        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('entity_id', $orderId)
            ->setOrder('created_at', 'desc')
        ;

        return $orders->getFirstItem();
    }

    private function getProductCollection()
    {
        $collection = $this->_productCollection->create();
        $collection->addAttributeToSelect('*');

        return $collection;
    }

    private function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted , $asCollection, $toLoad);
    }

    private function getProductCollectionByName($searchString)
    {
        $collection = $this->getProductCollection();
        $collection->addAttributeToFilter(array(
            array('attribute' => 'name', 'like' => '%' . $searchString . '%'),
            array('attribute' => 'sku', 'like' => '%' . $searchString . '%'),
        ));
//        $collection->setPageSize(3); // fetching only 3 products
        return $collection;
    }

    private function getCategoryById($categoryId)
    {
        $category = $this->_categoryFactory->create();
        $category->load($categoryId);

        return $category;
    }

    private function getCategoryByName($name)
    {
        return $this->getCategoriesByName($name)->getFirstItem();
    }

    private function getCategoriesByName($name)
    {
        $categoryCollection = $this->_categoryCollectionFactory->create();
        $categoryCollection = $categoryCollection->addAttributeToFilter('name', $name);

        return $categoryCollection;
    }

    private function getProductsFromCategoryId($categoryId, $filterStatus = true)
    {
        $productCollection = $this->getCategoryById($categoryId)->getProductCollection();
        $productCollection->addAttributeToSelect('*');
        if ($filterStatus)
        {
            $productCollection->addAttributeToFilter(
                'status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            );
            $this->_stockFilter->addInStockFilterToCollection($productCollection);
        }

        return $productCollection;
    }

    private function getQuoteByCustomerId($customerId)
    {
        $customer = $this->_customerRepositoryInterface->getById($customerId);
        $quote = $this->_quoteModel->loadByCustomer($customer);
        if (!$quote->getId())
        {
            $quote->setCustomer($customer);
            $quote->setIsActive(1);
            $quote->setStoreId($this->storeManager->getStore()->getId());
        }

        return $quote;
    }

//    private function getCartItemsByCustomerId($customerId)
//    {
//        $quote = $this->getQuoteByCustomerId($customerId);
//        if ($quote->getId())
//        {
//            $allItems = $quote->getItemsCollection(); // returns all the items in quote
//            if (count($allItems) > 0)
//                return $allItems;
//        }
//
//        return array();
//    }

//    private function getItemListImageProductObject($product)
//    {
//        return $this->getListProductDetailsObject($product);
//    }

    private function getCartItemsList($quote, $includeTax = true)
    {
        $orderItems = $quote->getItemsCollection();
        $text = '';
        foreach ($orderItems as $orderItem)
        {
            $text .= __("Product:") . ' ' . $orderItem->getName() . chr(10);
            $price = $this->_priceHelper->currency($orderItem->getPrice(), true, false);
            $text .= __("Price:") . ' ' . $price . chr(10);
            $text .= __("Quantity:") . ' ' . $orderItem->getQty() . chr(10);
            $text .= chr(10);
        }
        if ($text != '')
        {
            $price = $this->_priceHelper->currency($quote->getSubtotal(), true, false);
            $text .= __("Subtotal:") . ' ' . $price . chr(10);
            if ($includeTax)
            {
                if ($quote->getSubtotalInclTax())
                {
                    $price = $this->_priceHelper->currency($quote->getSubtotal(), true, false);
                    $text .= __("Subtotal (Tax Incl.):") . ' ' . $price . chr(10);
                }
            }
        }

        return $text;
    }

//    private function getListProductDetailsObject($product, $image = true) // return a single object to be used in a bundled list
//    {
//        $element = array();
//        if ($product->getId())
//        {
//            if ($product->getShortDescription())
//                $description = $this->excerpt($product->getShortDescription(), 60);
//            else
//                $description = '';
//
//            $productUrl = $product->getProductUrl();
//
//            $element = array(
//                'title' => $product->getName(),
////                'image_url' => $productImage,
//                'subtitle' => $description,
//                'default_action' => array(
//                    'type' => 'web_url',
//                    'url' => $productUrl
//                ),
//                'buttons' => array(
//                    array(
//                        'title' => __("Visit product's page"),
//                        'type' => 'web_url',
//                        'url' => $productUrl
//                    )
//                )
//            );
//
//            if ($image)
//            {
//                if ($product->getImage())
//                    $productImage = $this->getMediaURL('catalog/product') . $product->getImage();
//                else
//                    $productImage = $this->getPlaceholderImage();
//
//                $element['image_url'] = $productImage;
//            }
//        }
//
//        return $element;
//    }

    private function getImageWithOptionsProductObject($product)
    {
        return $this->getProductDetailsObject($product);
    }

//    private function getUnitWithImageProductObject($product)
//    {
//        return $this->getProductDetailsObject($product, true);
//    }

    private function getProductDetailsObject($product, $checkout = false) // used to get single object
    {
        $element = array();
        if ($product->getId())
        {
            $productName = $product->getName();
            $productUrl = $product->getProductUrl();
            if ($product->getImage())
                $productImage = $this->getMediaURL('catalog/product') . $product->getImage();
            else
                $productImage = $this->getPlaceholderImage();

            $options = array(
                array(
                    'type' => 'web_url',
                    'title' => __("Visit product's page"),
                    'url' => $productUrl
                )
            );

            if ($checkout)
            {
                $checkoutOption = array(
                    'type' => 'web_url',
                    'title' => __("Checkout"),
                    'url' => $this->getStoreURL('checkout/cart')
                );
                array_push($options, $checkoutOption);
            }
            else
            {
                if (($product->getTypeId() == 'simple') && (!$product->hasCustomOptions())) // TODO remove this to add any type of product
                {
                    $payload = array(
                        'command' => $this->_define::ADD_TO_CART_COMMAND_ID,
                        'parameter' => $product->getId()
                    );
                    $addToCartOption  = array(
                        'type' => 'postback',
                        'title' => $this->getCommandText($this->_define::ADD_TO_CART_COMMAND_ID),
                        'payload' => json_encode($payload)
                    );
                    array_push($options, $addToCartOption);
                }
            }

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

    private function getImageWithOptionsOrderObject($order)
    {
        return $this->getOrderDetailsObject($order);
    }

    private function getOrderDetailsObject($order) // used to get single order
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
                if ($product->getImage())
                    $productImage = $this->getMediaURL('catalog/product') . $product->getImage();
                else
                    $productImage = $this->getPlaceholderImage();

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

    private function getChatbotAPIBySenderId($senderId)
    {
        $chatbotAPI = $this->_chatbotAPIFactory->create();
        $chatbotAPI->load($senderId, 'chat_id'); // TODO

        return $chatbotAPI;
    }

    private function getChatbotuserBySenderId($senderId)
    {
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $chatbotUser = $this->_chatbotUserFactory->create();

        if ($chatbotAPI->getChatbotapiId())
        {
            $chatbotUser->load($chatbotAPI->getChatbotuserId(), 'chatbotuser_id'); // TODO
//            if ($chatbotUser->getChatbotuserId())
//                return $chatbotUser;
        }

        return $chatbotUser;
    }

    private function getMessageModelById($messageId)
    {
        $message = $this->_messageModelFactory->create();
        $message->load($messageId);

        return $message;
    }

    // COMMANDS FUNCTIONS
    private function processListCategoriesCommand()
    {
        $result = array();
        $emptyCategories = ($this->getConfigValue($this->_configPrefix . '/general/list_empty_categories') == $this->_define::ENABLED);
        $categories = $this->getStoreCategories(false, false, true);
        $quickReplies = array();
        foreach ($categories as $category)
        {
            if (!$emptyCategories)
            {
                $productCollection = $this->getProductsFromCategoryId($category->getId());
                if (count($productCollection) <= 0)
                    continue;
            }

            $categoryName = $category->getName();
            if ($categoryName)
            {
                $payload = array(
                    'command' => $this->_define::LIST_CATEGORIES_COMMAND_ID,
                    'parameter' => $category->getId()
                );
                $quickReply = array(
                    'content_type' => 'text', // TODO messenger pattern
                    'title' => $categoryName,
                    'payload' => json_encode($payload)
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
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);

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
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $response = $chatbotAPI->logOutChatbotCustomer();
        $this->setChatbotAPIModel($chatbotAPI);
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
        $quickReplies = array();
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $lastCommandObject = json_decode($chatbotAPI->getLastCommandDetails());
        $listOrdersCommand = $this->getCommandText($this->_define::LIST_ORDERS_COMMAND_ID);
        $listMoreCommand = $this->getCommandText($this->_define::LIST_MORE_COMMAND_ID);
        if (!isset($lastCommandObject->last_listed_quantity))
            return $result;

        if ($lastCommandObject->last_message_content == $listOrdersCommand)
            $startAt = $lastCommandObject->last_listed_quantity;
        else
            $startAt = 0;

        $count = 0;

        foreach ($ordersCollection as $order)
        {
            if ($count < $startAt)
            {
                $count++;
                continue;
            }

            $orderObject = $this->getImageWithOptionsOrderObject($order);
//            $this->logger(json_encode($productObject));
            if (count($orderList) < $this->_define::MAX_MESSAGE_ELEMENTS) // TODO
            {
                array_push($orderList, $orderObject);
                $payload = array(
                    'command' => $this->_define::LIST_ORDERS_COMMAND_ID,
                    'parameter' => $order->getId()
                );
                $reply = array(
                    'content_type' => 'text',
                    'title' => $order->getIncrementId(),
                    'payload' => json_encode($payload)
                );
                array_push($quickReplies, $reply);
            }
        }

        $listCount = count($orderList);
        $totalListCount = $listCount + $startAt;
        if (count($ordersCollection) > $totalListCount)
        {
            $chatbotAPI->setChatbotAPILastCommandDetails($listOrdersCommand, $totalListCount);
            $this->setChatbotAPIModel($chatbotAPI);
            if ($listCount > 0)
            {
                $payload = array(
                    'command' => $this->_define::LIST_ORDERS_COMMAND_ID,
                    'parameter' => ''
                );
                $reply = array(
                    'content_type' => 'text',
                    'title' => $listMoreCommand,
                    'payload' => json_encode($payload)
                );
                array_push($quickReplies, $reply);
            }
        }
        else
        {
            $chatbotAPI->updateConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->setChatbotAPILastCommandDetails($listMoreCommand, 0);
            $this->setChatbotAPIModel($chatbotAPI);
        }

        if ($listCount > 0)
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

        if ($quickReplies)
        {
            $contentObject = new \stdClass();
            $contentObject->message = __("If you want to reorder one of these orders choose it below.");
            $contentObject->quick_replies = $quickReplies;
            $responseMessage = array(
                'content_type' => $this->_define::QUICK_REPLY,
                'content' => json_encode($contentObject)
            );
            array_push($result, $responseMessage);
        }

        return $result;
    }

    private function processReorderCommand($orderId, $senderId)
    {
        $result = array();
        $order = $this->getOrderFromOrderId($orderId);
        if ($order->getId())
        {
            $chatbotUser = $this->getChatbotuserBySenderId($senderId);
            $orderItems = $order->getAllItems();
            $response = false;
            foreach ($orderItems as $orderItem)
            {
                $productId = $orderItem->getProductId();
                $qty = $orderItem->getQtyOrdered();
                $response = $this->addProductToCustomerCart($productId, $chatbotUser->getCustomerId(), $qty);

                if (!$response)
                    break;
            }

            if ($response)
            {
                $responseMessage = array(
                    'content_type' => $this->_define::CONTENT_TEXT,
                    'content' => __("All products from order %1 that are in stock were added to your cart.", $order->getIncrementId())
                );
                array_push($result, $responseMessage);
            }
            else
                $result = $this->getErrorMessage();
        }
        else
            $result = $this->getErrorMessage();

        return $result;
    }

    private function processAddToCartCommand($senderId, $payload)
    {
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $productId = $payload->parameter;
        $response = $this->addProductToCustomerCart($productId, $chatbotUser->getCustomerId());
        $result = array();
        if ($response)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => __("Ok, I just add the product to your cart, to checkout send '%1'.", $this->getCommandText($this->_define::CHECKOUT_COMMAND_ID))
            );
            array_push($result, $responseMessage);
        }
        else
            $result = $this->getErrorMessage();

        return $result;
    }

    private function processCheckoutCommand($senderId)
    {
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $quote = $this->getQuoteByCustomerId($chatbotUser->getCustomerId());
        $result = array();

        if ($quote->getId())
        {
            $checkoutTotalText = $this->getCartItemsList($quote);
            if ($checkoutTotalText)
            {
                $buttons = array(
                    array(
                        'type' => 'web_url',
                        'title' => __("Checkout"),
                        'url' => $this->getStoreURL('checkout/cart')
                    )
                );

                $contentObject = new \stdClass();
                $contentObject->message = $checkoutTotalText;
                $contentObject->buttons = $buttons;
                $responseMessage = array(
                    'content_type' => $this->_define::TEXT_WITH_OPTIONS,
                    'content' => json_encode($contentObject)
                );
                array_push($result, $responseMessage);
            }
            else
            {
                $text = __("Your cart is empty.");
                $result = $this->getTextMessageArray($text);
            }
        }
        else
            $result = $this->getErrorMessage();

        return $result;
    }

//    private function processCheckoutCommand($senderId)
//    {
//        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
//        $quote = $this->getQuoteByCustomerId($chatbotUser->getCustomerId());
//        $orderItems = $quote->getItemsCollection();
////        $orderItems = $this->getCartItemsByCustomerId($chatbotUser->getCustomerId());
//        $result = array();
//        $listObjectList = array();
//        if (count($orderItems) > 1)
//        {
//            foreach ($orderItems as $orderItem)
//            {
//                $listObject = $this->getItemListImageProductObject($orderItem->getProduct());
//                if ($listObject)
//                    array_push($listObjectList, $listObject);
//            }
//            $buttons = array(
//                array(
//                    'type' => 'web_url',
//                    'title' => __("Checkout"),
//                    'url' => $this->getStoreURL('checkout/cart')
//                )
//            );
//
//            if ($listObjectList)
//            {
//                $contentObject = new \stdClass();
//                $contentObject->list = $listObjectList;
//                $contentObject->buttons = $buttons;
//                $responseMessage = array(
//                    'content_type' => $this->_define::LIST_WITH_IMAGE,
//                    'content' => json_encode($contentObject)
//                );
//                array_push($result, $responseMessage);
//            }
//        }
//        else if (count($orderItems) == 1)
//        {
//            $orderItem = $orderItems->getFirstItem();
//            $imageWithOptionsProdObj = $this->getUnitWithImageProductObject($orderItem->getProduct());
//            array_push($listObjectList, $imageWithOptionsProdObj);
//
//            $responseMessage = array(
//                'content_type' => $this->_define::IMAGE_WITH_OPTIONS,
//                'content' => json_encode($listObjectList)
//            );
//            array_push($result, $responseMessage);
//        }
//        else // if (count($orderItems) <= 0)
//        {
//            $text = __("Your cart is empty.");
//            $result = $this->getTextMessageArray($text);
//        }
//
//        return $result;
//    }

    private function processClearCartCommand($senderId)
    {
        $chatbotUser = $this->getChatbotuserBySenderId($senderId);
        $response = $this->clearCustomerCart($chatbotUser->getCustomerId());
        $result = array();
        if ($response)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => __("Cart cleared.")
            );
            array_push($result, $responseMessage);
        }
        else
            $result = $this->getErrorMessage();

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
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        $response = $chatbotAPI->updateConversationState($this->_define::CONVERSATION_STARTED);
        $this->setChatbotAPIModel($chatbotAPI);
        if ($response)
        {
            $responseMessage = array(
                'content_type' => $this->_define::CONTENT_TEXT,
                'content' => __("Ok, canceled.")
            );
            array_push($result, $responseMessage);
        }
        else
            $result = $this->getErrorMessage();

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
        else
            $result = $this->getErrorMessage();

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
        else
            $result = $this->getErrorMessage();

        return $result;
    }

    private function processListMore($lastCommandObject, $senderId)
    {
        $result = array();
        $chatbotAPI = $this->getChatbotAPIModelBySenderId($senderId);
        if (!isset($lastCommandObject->last_listed_quantity))
            return $result;

        $listedQuantity = $lastCommandObject->last_listed_quantity;

        if ($listedQuantity > 0)
        {
            $conversationState = $lastCommandObject->last_conversation_state;
            $listCommands = array( // TODO
                $this->_define::CONVERSATION_LIST_CATEGORIES,
                $this->_define::CONVERSATION_SEARCH
            );

            if (in_array($conversationState, $listCommands))
            {
                $messageContent = $lastCommandObject->last_message_content;
                $chatbotAPI->updateConversationState($conversationState);
                $this->setChatbotAPIModel($chatbotAPI);

                $result = $this->handleConversationState($messageContent, $senderId);
            }
            else if ($lastCommandObject->last_message_content == $this->getCommandText($this->_define::LIST_ORDERS_COMMAND_ID))
            {
                $result = $this->processListOrdersCommand($senderId);
            }
        }

        return $result;
    }
}