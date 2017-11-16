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
    protected $_serializer;
    protected $_categoryHelper;
    protected $_categoryFactory;
    protected $_categoryCollectionFactory;
    protected $_storeManagerInterface;
    protected $_commandsList;
    protected $_productCollection;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        StoreManagerInterface $storeManager,
        \Werules\Chatbot\Model\ChatbotAPIFactory $chatbotAPI,
        \Werules\Chatbot\Model\MessageFactory $message,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollection
    )
    {
        $this->objectManager = $objectManager;
        $this->_serializer = $serializer;
        $this->storeManager  = $storeManager;
        $this->_messageModel  = $message;
        $this->_chatbotAPI  = $chatbotAPI;
        $this->_configPrefix = '';
        $this->_define = new \Werules\Chatbot\Helper\Define;
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_productCollection = $productCollection;
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
        if ($message->getChatbotType() == $this->_define::MESSENGER_INT)
            $this->_configPrefix = 'werules_chatbot_messenger';

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

    private function processOutgoingMessage($message_id)
    {
        $outgoingMessage = $this->_messageModel->create();
        $outgoingMessage->load($message_id);

        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($outgoingMessage->getSenderId(), 'chat_id'); // TODO

        $result = false;
        if ($outgoingMessage->getContentType() == $this->_define::CONTENT_TEXT)
            $result = $chatbotAPI->sendMessage($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::QUICK_REPLY)
            $result = $chatbotAPI->sendQuickReply($outgoingMessage);
        else if ($outgoingMessage->getContentType() == $this->_define::IMAGE_WITH_OPTIONS)
            $result = $chatbotAPI->sendImageWithOptions($outgoingMessage);

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
        $commandResponses = false;
        $conversationStateResponses = false;
        $NLPResponses = false;

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

//        array_push($responseContent, array('content_type' => $this->_define::CONTENT_TEXT, 'content' => 'Dunno!'));

        return $responseContent;
    }

    private function handleNaturalLanguageProcessor($message)
    {
        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($message->getSenderId(), 'chat_id'); // TODO
        $result = false;

        $entity = $chatbotAPI->getNLPTextMeaning($message->getContent());

        if (isset($entity['intent']))
        {
            $intent = $entity['intent']['value'];
            if ($intent == 'command')
            {
                if (isset($entity['command']))
                {
                    $command = $entity['command'];
                    if (isset($entity['keyword']))
                    {
                        $keyword = $entity['keyword'];
                        // TODO add command somehow
                        $result = $this->handleCommandsWithParameters($message, $command, $keyword);
                    }
                }
            }
        }

        return $result;
    }

    private function handleConversationState($message, $keyword = false)
    {
        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($message->getSenderId(), 'chat_id'); // TODO
        $result = false;
        if ($keyword)
            $messageContent = $keyword;
        else
            $messageContent = $message->getContent();

        if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_LIST_CATEGORIES)
        {
            $result = $this->listProductsFromCategory($messageContent, $message->getPayload());
        }
        else if ($chatbotAPI->getConversationState() == $this->_define::CONVERSATION_SEARCH)
        {
            $result = $this->listProductsFromSearch($messageContent);
        }

        if ($result)
        {
            $chatbotAPI->setConversationState($this->_define::CONVERSATION_STARTED);
            $chatbotAPI->save();
        }

        return $result;
    }

    public function listProductsFromSearch($messageContent)
    {
        $result = array();
        $productList = array();
        $productCollection = $this->getProductCollectionByName($messageContent);

        foreach ($productCollection as $product)
        {
            $content = $this->getProductDetailsObject($product);
            array_push($productList, $content);
        }

        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::IMAGE_WITH_OPTIONS;
        $responseMessage['content'] = json_encode($productList);
        array_push($result, $responseMessage);

        return $result;
    }

    public function getProductCollectionByName($searchString)
    {
        $collection = $this->_productCollection->create();
        $collection->addAttributeToSelect('*'); // ['name', 'sku']
        $collection->addAttributeToFilter(array(
            array('attribute' => 'name', 'like' => '%' . $searchString . '%'),
            array('attribute' => 'sku', 'like' => '%' . $searchString . '%'),
        ));
//        $collection->setPageSize(3); // fetching only 3 products
        return $collection;
    }

    public function getCategoryById($category_id)
    {
        $category = $this->_categoryFactory->create();
        $category->load($category_id);

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

    public function getProductsFromCategoryId($category_id)
    {
        $productCollection = $this->getCategoryById($category_id)->getProductCollection();
        $productCollection->addAttributeToSelect('*');

        return $productCollection;
    }

    private function listProductsFromCategory($messageContent, $messagePayload = false)
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
            $content = $this->getProductDetailsObject($product);
            array_push($productList, $content);
        }

        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::IMAGE_WITH_OPTIONS;
        $responseMessage['content'] = json_encode($productList);
        array_push($result, $responseMessage);

        return $result;
    }

    private function getProductDetailsObject($product)
    {
        $element = array();
        if ($product->getId())
        {
            $productName = $product->getName();
            $productUrl = $product->getProductUrl();
//            $productImage = $product->getImage();
            $productImage = $this->_storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            // TODO add placeholder
            $options = array(
                array(
                    'type' => 'postback',
                    'title' => 'Add to cart',
                    'payload' => 'todo_here'
                ),
                array(
                    'type' => 'web_url',
                    'title' => "Visit product's page",
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

    private function prepareCommandsList($commands)
    {
        $commandsList = array();
        foreach($commands as $command)
        {
            if ($command['enable_command'] == '1')
            {
                $command_id = $command['command_id'];
                $commandsList[$command_id] = array(
                    'command_code' => $command['command_code'],
                    'command_alias_list' => explode(',', $command['command_alias_list'])
                );
            }
        }
        return $commandsList;
    }

    private function processCommands($messageContent, $senderId, $setStateOnly = false)
    {
//        $messageContent = $message->getContent();
        $serializedCommands = $this->getConfigValue($this->_configPrefix . '/general/commands_list');
        $commandsList = $this->_serializer->unserialize($serializedCommands);
        $this->_commandsList = $this->prepareCommandsList($commandsList);
        if (!is_array($this->_commandsList))
            return false;

        $result = false;
        $state = false;
        foreach($this->_commandsList as $key => $command)
        {
//                 if ($messageContent == $command['command_code']) // TODO add alias check
            if (strtolower($messageContent) == strtolower($command['command_code'])) // TODO add configuration for this
            {
                if ($key == $this->_define::START_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processStartCommand();
                }
                else if ($key == $this->_define::LIST_CATEGORIES_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processListCategoriesCommand();
                    $state = $this->_define::CONVERSATION_LIST_CATEGORIES;
                }
                else if ($key == $this->_define::SEARCH_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processSearchCommand();
                    $state = $this->_define::CONVERSATION_SEARCH;
                }
                else if ($key == $this->_define::LOGIN_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processLoginCommand();
                }
                else if ($key == $this->_define::LIST_ORDERS_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processListOrdersCommand();
                }
                else if ($key == $this->_define::REORDER_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processReorderCommand();
                }
                else if ($key == $this->_define::ADD_TO_CART_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processAddToCartCommand();
                }
                else if ($key == $this->_define::CHECKOUT_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processCheckoutCommand();
                }
                else if ($key == $this->_define::CLEAR_CART_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processClearCartCommand();
                }
                else if ($key == $this->_define::TRACK_ORDER_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processTrackOrderCommand();
                }
                else if ($key == $this->_define::SUPPORT_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processSupportCommand();
                }
                else if ($key == $this->_define::SEND_EMAIL_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processSendEmailCommand();
                }
                else if ($key == $this->_define::CANCEL_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processCancelCommand();
                }
                else if ($key == $this->_define::HELP_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processHelpCommand();
                }
                else if ($key == $this->_define::ABOUT_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processAboutCommand();
                }
                else if ($key == $this->_define::LOGOUT_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processLogoutCommand();
                }
                else if ($key == $this->_define::REGISTER_COMMAND_ID)
                {
                    if ($setStateOnly)
                        $result = $this->processRegisterCommand();
                }
                else
                {
                    // TODO add error handler here
                }
                break;
            }
        }
        if (($state && $result) || $setStateOnly)
            $this->updateConversationState($senderId, $state);

        return $result;
    }

    private function handleCommandsWithParameters($message, $command, $keyword)
    {
//        $this->logger($serializedCommands);
//        $this->logger($this->_commandsList);

        $this->processCommands($command, $message->getSenderId(), true); // ignore output
        $result = $this->handleConversationState($message, $keyword);

        return $result;
    }

//    private function handleCommands($messageContent, $senderId)
    private function handleCommands($message)
    {
//        $this->logger($serializedCommands);
//        $this->logger($this->_commandsList);
        $result = $this->processCommands($message->getContent(), $message->getSenderId());

        return $result;
    }

    private function updateConversationState($sender_id, $state)
    {
        $chatbotAPI = $this->_chatbotAPI->create();
        $chatbotAPI->load($sender_id, 'chat_id'); // TODO

        if ($chatbotAPI->getChatbotapiId())
        {
            $chatbotAPI->setConversationState($state);
            $datetime = date('Y-m-d H:i:s');
            $chatbotAPI->setUpdatedAt($datetime);
            $chatbotAPI->save();

            return true;
        }

        return false;
    }

    private function processStartCommand()
    {
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the START command!';
        return $responseMessage;
    }

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
        $contentObject->message = 'Pick one of the following categories.';
        $contentObject->quick_replies = $quickReplies;
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::QUICK_REPLY;
        $responseMessage['content'] = json_encode($contentObject);
        array_push($result, $responseMessage);
        return $result;
    }

    private function processSearchCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'Sure, send me the name of the product you\'re looking for';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processLoginCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the LOGIN command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processListOrdersCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the LIST_ORDERS command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processReorderCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the REORDER command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processAddToCartCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the ADD_TO_CART command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processCheckoutCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the CHECKOUT command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processClearCartCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the CLEAR_CART command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processTrackOrderCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the TRACK_ORDER command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processSupportCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the SUPPORT command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processSendEmailCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the SEND_EMAIL command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processCancelCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the CANCEL command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processHelpCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the HELP command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processAboutCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the ABOUT command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processLogoutCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the LOGOUT command!';
        array_push($result, $responseMessage);
        return $result;
    }

    private function processRegisterCommand()
    {
        $result = array();
        $responseMessage = array();
        $responseMessage['content_type'] = $this->_define::CONTENT_TEXT;
        $responseMessage['content'] = 'you just sent the REGISTER command!';
        array_push($result, $responseMessage);
        return $result;
    }

//    public function getConfig($code, $storeId = null)
//    {
//        return $this->getConfigValue(self::XML_PATH_CHATBOT . $code, $storeId);
//    }
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted , $asCollection, $toLoad);
//        return $this->_categoryFactory->create()->getCollection();
    }
}