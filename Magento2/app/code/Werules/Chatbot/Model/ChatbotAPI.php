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

namespace Werules\Chatbot\Model;

use Werules\Chatbot\Api\Data\ChatbotAPIInterface;

class ChatbotAPI extends \Magento\Framework\Model\AbstractModel implements ChatbotAPIInterface
{
    protected $_apiModel;
    protected $_NLPModel;
    protected $_objectManager;
    protected $_define;
    protected $_helper;
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // TODO find a better way to to this
        $this->_define = new \Werules\Chatbot\Helper\Define;
         $this->_helper = $this->_objectManager->create('Werules\Chatbot\Helper\Data'); // TODO find a better way to to this
        // $this->_helper = new \Werules\Chatbot\Helper\Data;
        $this->_init('Werules\Chatbot\Model\ResourceModel\ChatbotAPI');
    }

    /**
     * Get chatbotapi_id
     * @return string
     */
    public function getChatbotapiId()
    {
        return $this->getData(self::CHATBOTAPI_ID);
    }

    /**
     * Set chatbotapi_id
     * @param string $chatbotapiId
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatbotapiId($chatbotapiId)
    {
        return $this->setData(self::CHATBOTAPI_ID, $chatbotapiId);
    }
    /**
     * Get hash_key
     * @return string
     */
    public function getHashKey()
    {
        return $this->getData(self::HASH_KEY);
    }
    /**
     * Set hash_key
     * @param string $hash_key
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setHashKey($hash_key)
    {
        return $this->setData(self::HASH_KEY, $hash_key);
    }
    
    /**
     * Get logged
     * @return string
     */
    public function getLogged()
    {
        return $this->getData(self::LOGGED);
    }
    
    /**
     * Set logged
     * @param string $logged
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setLogged($logged)
    {
        return $this->setData(self::LOGGED, $logged);
    }

    /**
     * Get enabled
     * @return string
     */
    public function getEnabled()
    {
        return $this->getData(self::ENABLED);
    }

    /**
     * Set enabled
     * @param string $enabled
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setEnabled($enabled)
    {
        return $this->setData(self::ENABLED, $enabled);
    }

    /**
     * Get chatbot_type
     * @return string
     */
    public function getChatbotType()
    {
        return $this->getData(self::CHATBOT_TYPE);
    }

    /**
     * Set chatbot_type
     * @param string $chatbot_type
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatbotType($chatbot_type)
    {
        return $this->setData(self::CHATBOT_TYPE, $chatbot_type);
    }

    /**
     * Get chat_id
     * @return string
     */
    public function getChatId()
    {
        return $this->getData(self::CHAT_ID);
    }

    /**
     * Set chat_id
     * @param string $chat_id
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatId($chat_id)
    {
        return $this->setData(self::CHAT_ID, $chat_id);
    }

    /**
     * Get conversation_state
     * @return string
     */
    public function getConversationState()
    {
        return $this->getData(self::CONVERSATION_STATE);
    }

    /**
     * Set conversation_state
     * @param string $conversation_state
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setConversationState($conversation_state)
    {
        return $this->setData(self::CONVERSATION_STATE, $conversation_state);
    }

    /**
     * Get fallback_qty
     * @return string
     */
    public function getFallbackQty()
    {
        return $this->getData(self::FALLBACK_QTY);
    }

    /**
     * Set fallback_qty
     * @param string $fallback_qty
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setFallbackQty($fallback_qty)
    {
        return $this->setData(self::FALLBACK_QTY, $fallback_qty);
    }

    /**
     * Get created_at
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $created_at
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setCreatedAt($created_at)
    {
        return $this->setData(self::CREATED_AT, $created_at);
    }

    /**
     * Get updated_at
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated_at
     * @param string $updated_at
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setUpdatedAt($updated_at)
    {
        return $this->setData(self::UPDATED_AT, $updated_at);
    }

    /**
     * Get chatbotuser_id
     * @return string
     */
    public function getChatbotuserId()
    {
        return $this->getData(self::CHATBOTUSER_ID);
    }

    /**
     * Set chatbotuser_id
     * @param string $chatbotuser_id
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatbotuserId($chatbotuser_id)
    {
        return $this->setData(self::CHATBOTUSER_ID, $chatbotuser_id);
    }

    public function initMessengerAPI($bot_token) // TODO TODO TODO
    {
        return $this->_objectManager->create('Werules\Chatbot\Model\Api\Messenger', array('bot_token' => $bot_token)); // TODO find a better way to to this
    }

    public function initWitAIAPI($token) // TODO TODO TODO
    {
        return $this->_objectManager->create('Werules\Chatbot\Model\Api\witAI', array('token' => $token)); // TODO find a better way to to this
    }

    // custom methods
//    public function requestHandler($api_name)
//    {
//        $this->initChatbotAPI($this->_define::MESSENGER_INT, 'needed_TODO');
////        $logger = $this->_objectManager->get('Psr\Log\LoggerInterface'); // TODO why isn't this working?
////        $logger->debug('something');
//        $this->logger($this->_apiModel);
//        return 'hello world';//array('status' => 'success');
//    }

//    public function initChatbotAPI($chatbot_type, $apiToken)
//    {
//        $this->setChatbotType($chatbot_type);
//
//        if ($chatbot_type == $this->_define::MESSENGER_INT)
//        {
//            $apiToken = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
//            $this->_apiModel = $this->initMessengerAPI($apiToken);
//        }
//    }

    public function sendMessage($message)
    {
        $result = array();
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $result = $this->sendMessageToMessenger($message);
        }

        return $result;
    }

    public function sendMessageToMessenger($message)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($apiToken);

        $result = $this->_apiModel->sendMessage($message->getSenderId(), $message->getContent());

        return $result;
    }

    public function sendQuickReply($message)
    {
        $result = array();
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $result = $this->sendQuickReplyToMessenger($message);
        }

        return $result;
    }

    public function sendQuickReplyToMessenger($message)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($apiToken);

        $messageContent = $message->getContent();
        $decodedContent = json_decode($messageContent);
//            foreach ($decodedContent->quick_replies as $quickReply)
//            {
//                // TODO build quickreplies here for generic
//            }
        $result = $this->_apiModel->sendQuickReply($message->getSenderId(), $decodedContent->message, $decodedContent->quick_replies);

        return $result;
    }

    public function sendReceiptList($message)
    {
        $result = array();
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $result = $this->sendReceiptListToMessenger($message);
        }

        return $result;
    }

    public function sendListToMessenger($message)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($apiToken);

        $messageContent = $message->getContent();
        $decodedContent = json_decode($messageContent);
        $listItems = $decodedContent->list;
        $buttons = $decodedContent->buttons;
        $elements = array();
        $options = array();

        foreach ($listItems as $item)
        {
            $itemDefaultAction = $item->default_action;
            $defaultAction = array(
                'type' => $itemDefaultAction->type,
                'url' => $itemDefaultAction->url,
                'messenger_extensions' => $itemDefaultAction->messenger_extensions,
                'webview_height_ratio' => $itemDefaultAction->webview_height_ratio,
                'fallback_url' => $itemDefaultAction->fallback_url,
            );

            $itemListButtons = $item->buttons;
            $listButtons = array();
            foreach ($itemListButtons as $listButtom)
            {
                $auxArr = array(
                    'title' => $listButtom->title,
                    'type' => $listButtom->type,
                    'url' => $listButtom->url,
                    'messenger_extensions' => $listButtom->messenger_extensions,
                    'webview_height_ratio' => $listButtom->webview_height_ratio,
                    'fallback_url' => $listButtom->fallback_url
                );

                array_push($listButtons, $auxArr);
            }

            $element = array(
                'title' => $item->title,
                'image_url' => $item->image_url,
                'subtitle' => $item->subtitle,
                'default_action' => $defaultAction,
                'buttons' => $listButtons
            );

            array_push($elements, $element);
        }

        foreach ($buttons as $buttom)
        {
            $btn = array(
                'type' => $buttom->type,
                'title' => $buttom->title,
                'url' => $buttom->url
            );
            array_push($options, $btn);
        }

        $this->_helper->logger($elements);
        $this->_helper->logger($options);
        $result = $this->_apiModel->sendListTemplate($message->getSenderId(), $elements, $options);
        $this->_helper->logger($result);
        return $result;
    }

    public function sendList($message)
    {
        $result = array();
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $result = $this->sendListToMessenger($message);
        }

        return $result;
    }

    public function sendImageWithOptions($message)
    {
        $result = array();
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $result = $this->sendImageWithOptionsToMessenger($message);
        }

        return $result;
    }

    public function sendReceiptListToMessenger($message)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($apiToken);

        $result = array( // TODO
            'status' => 'success'
        );
        $messageContent = $message->getContent();
        $decodedContent = json_decode($messageContent);
//        $receiptPaylod = array();

        foreach ($decodedContent as $decodedObject)
        {
            $address = $decodedObject->address;
            $summary = $decodedObject->summary;
            $elements = array();
            foreach ($decodedObject->elements as $element)
            {
                $productDetails = array(
                    'title' => $element->title,
                    'subtitle' => $element->subtitle,
                    'quantity' => $element->quantity,
                    'price' => $element->price,
                    'currency' => $element->currency,
                    'image_url' => $element->image_url
                );
                array_push($elements, $productDetails);
            }
            $addressDetails = array(
                'street_1' => $address->street_1,
                'street_2' => $address->street_2,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'state' => $address->state,
                'country' => $address->country
            );
            $summaryDetails = array(
                'subtotal' => $summary->subtotal,
                'shipping_cost' => $summary->shipping_cost,
                'total_tax' => $summary->total_tax,
                'total_cost' => $summary->total_cost,
            );

            $receiptPaylod = array(
                'template_type' => $decodedObject->template_type,
                'recipient_name' => $decodedObject->recipient_name,
                'order_number' => $decodedObject->order_number,
                'currency' => $decodedObject->currency,
                'payment_method' => $decodedObject->payment_method,
                'order_url' => $decodedObject->order_url,
                'timestamp' => $decodedObject->timestamp,
                'elements' => $elements,
                'address' => $addressDetails,
                'summary' => $summaryDetails
            );

            $response = $this->_apiModel->sendReceiptTemplate($message->getSenderId(), $receiptPaylod);
        }

        return $result;
    }

    public function sendImageWithOptionsToMessenger($message)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($apiToken);

        $messageContent = $message->getContent();
        $decodedContent = json_decode($messageContent);

        $elements = array();
        foreach ($decodedContent as $decodedObject)
        {
            $auxArr = array();
            foreach ($decodedObject->buttons as $button)
            {
                $auxArr2 = array(
                    'type' => $button->type,
                    'title' => $button->title
                );

                if (isset($button->payload))
                    $auxArr2['payload'] = $button->payload;
                if (isset($button->url))
                    $auxArr2['url'] = $button->url;
                array_push($auxArr, $auxArr2);
            }

            $element = array(
                'title' => $decodedObject->title,
                'item_url' => $decodedObject->item_url,
                'image_url' => $decodedObject->image_url,
                'subtitle' => $decodedObject->subtitle,
                'buttons' => $auxArr
            );
            array_push($elements, $element);
        }

        $result = $this->_apiModel->sendGenericTemplate($message->getSenderId(), $elements);

        return $result;
    }

    private function getEntitiesValue($entity, $entitiesAttributes)
    {
        $finalEntity = array();
        if (count($entitiesAttributes) > 0)
        {
            foreach ($entitiesAttributes as $key => $entityAttribute)
            {
                if (isset($entity[$entityAttribute]))
                {
                    foreach ($entity[$entityAttribute] as $entAttr)
                    {
                        if (isset($entAttr['confidence']))
                        {
                            if (isset($entAttr['value']))
                            {
                                $entityArray = array(
                                    'value' => $entAttr['value'],
                                    'confidence' => $entAttr['confidence']
                                );
//                                array_push($finalEntity, $ent);
                                $finalEntity[$key] = $entityArray;
                            }
                        }
                    }
                }
            }
        }
//        if (count($finalEntity) < count($entitiesAttributes))
//            return array();
        if (!isset($finalEntity['intent'])) // TODO mandatory
            return array();

        return $finalEntity;
    }

    public function getNLPTextMeaning($text)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_general/general/wit_ai_token');
        $this->_NLPModel = $this->initWitAIAPI($apiToken);

        $response = $this->_NLPModel->getTextResponse($text);
        $result = array();
        $prefix = '';

        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
            $prefix = $this->_helper->getConfigValue('werules_chatbot_messenger/general/nlp_entity_prefix');

        if (isset($response['_text']))
        {
            if ($response['_text'] == $text)
            {
                if (isset($response['entities']))
                {
                    $entitiesList = $this->getEntitiesArray($prefix);
                    $result = $this->getEntitiesValue($response['entities'], $entitiesList);
                    if (count($result) <= 0) // if no specific API entities, look for general
                    {
                        $entitiesList = $this->getEntitiesArray();
                        $result = $this->getEntitiesValue($response['entities'], $entitiesList);
                    }
                }
            }
        }

        return $result;
    }

    private function getEntitiesArray($prefix = '')
    {
        return array(
            'intent' => $prefix . 'intent',  // TODO mandatory
            'parameter' => 'parameter',
            'keyword' => 'keyword',
            'reply' => $prefix . 'reply'
        );
    }

    public function getNLPAudioMeaning($audio)
    {
        $apiToken = $this->_helper->getConfigValue('werules_chatbot_general/general/wit_ai_token');
        $this->_NLPModel = $this->initWitAIAPI($apiToken);

        $result = $this->_NLPModel->getAudioResponse($audio);

        return $result;
    }
}
