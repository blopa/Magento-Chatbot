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

namespace Werules\Chatbot\Api\Data;

interface ChatbotAPIInterface
{

    const FALLBACK_QTY = 'fallback_qty';
    const CHAT_ID = 'chat_id';
    const CREATED_AT = 'created_at';
    const CHATBOTUSER_ID = 'chatbotuser_id';
    const ENABLED = 'enabled';
    const CONVERSATION_STATE = 'conversation_state';
    const CHATBOT_TYPE = 'chatbot_type';
    const UPDATED_AT = 'updated_at';
    const CHATBOTAPI_ID = 'chatbotapi_id';


    /**
     * Get chatbotapi_id
     * @return string|null
     */
    public function getChatbotapiId();

    /**
     * Set chatbotapi_id
     * @param string $chatbotapi_id
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatbotapiId($chatbotapiId);

    /**
     * Get enabled
     * @return string|null
     */
    public function getEnabled();

    /**
     * Set enabled
     * @param string $enabled
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setEnabled($enabled);

    /**
     * Get chatbot_type
     * @return string|null
     */
    public function getChatbotType();

    /**
     * Set chatbot_type
     * @param string $chatbot_type
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatbotType($chatbot_type);

    /**
     * Get chat_id
     * @return string|null
     */
    public function getChatId();

    /**
     * Set chat_id
     * @param string $chat_id
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatId($chat_id);

    /**
     * Get conversation_state
     * @return string|null
     */
    public function getConversationState();

    /**
     * Set conversation_state
     * @param string $conversation_state
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setConversationState($conversation_state);

    /**
     * Get fallback_qty
     * @return string|null
     */
    public function getFallbackQty();

    /**
     * Set fallback_qty
     * @param string $fallback_qty
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setFallbackQty($fallback_qty);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $created_at
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setCreatedAt($created_at);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updated_at
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setUpdatedAt($updated_at);

    /**
     * Get chatbotuser_id
     * @return string|null
     */
    public function getChatbotuserId();

    /**
     * Set chatbotuser_id
     * @param string $chatbotuser_id
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setChatbotuserId($chatbotuser_id);
}
