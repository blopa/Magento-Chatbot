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

//    public function initChatbotAPI($chatbot_type, $api_token)
//    {
//        $this->setChatbotType($chatbot_type);
//
//        if ($chatbot_type == $this->_define::MESSENGER_INT)
//        {
//            $api_token = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
//            $this->_apiModel = $this->initMessengerAPI($api_token);
//        }
//    }

    public function sendMessage($message)
    {
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $this->sendMessageToMessenger($message);
        }

        return true;
    }

    public function sendMessageToMessenger($message)
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($api_token);

        $this->_apiModel->sendMessage($message->getSenderId(), $message->getContent());
    }

    public function sendQuickReply($message)
    {
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $this->sendQuickReplyToMessenger($message);
        }

        return true;
    }

    public function sendQuickReplyToMessenger($message)
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($api_token);

        $messageContent = $message->getContent();
        $decodedContent = json_decode($messageContent);
//            foreach ($decodedContent->quick_replies as $quickReply)
//            {
//                // TODO build quickreplies here for generic
//            }
        $this->_apiModel->sendQuickReply($message->getSenderId(), $decodedContent->message, $decodedContent->quick_replies);
    }

    public function sendImageWithOptions($message)
    {
        if ($this->getChatbotType() == $this->_define::MESSENGER_INT)
        {
            $this->sendImageWithOptionsToMessenger($message);
        }

        return true;
    }

    public function sendImageWithOptionsToMessenger($message)
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $this->_apiModel = $this->initMessengerAPI($api_token);

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
        $this->_apiModel->sendGenericTemplate($message->getSenderId(), $elements);
    }

    private function getEntitiesValue($entity, $entitiesAttributes)
    {
        $finalEntity = array();
        if (count($entitiesAttributes) > 0)
        {
            foreach ($entitiesAttributes as $entityAttribute)
            {
                if (isset($entity[$entityAttribute]))
                {
                    foreach ($entity[$entityAttribute] as $entAttr)
                    {
                        if (isset($entAttr['confidence']))
                        {
                            if ($entAttr['confidence'] > 0.1)
                            {
                                if (isset($entAttr['value']))
                                    $finalEntity[$entityAttribute] = $entAttr['value'];
                            }
                        }
                    }
                }
            }
        }
        if (count($finalEntity) < count($entitiesAttributes))
            return array();

        return $finalEntity;
    }

    public function getNLPTextMeaning($text)
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_general/general/wit_ai_token');
        $this->_NLPModel = $this->initWitAIAPI($api_token);

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

    public function getEntitiesArray($prefix = '')
    {
        return array(
            $prefix . 'intent',
            $prefix . 'keyword',
            $prefix . 'command'
        );
    }

    public function getNLPAudioMeaning($audio)
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_general/general/wit_ai_token');
        $this->_NLPModel = $this->initWitAIAPI($api_token);

        $result = $this->_NLPModel->getAudioResponse($audio);

        return $result;
    }
}
