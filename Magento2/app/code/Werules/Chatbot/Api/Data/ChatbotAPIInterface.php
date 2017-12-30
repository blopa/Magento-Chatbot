<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
 * 
 * This file is part of Werules/Chatbot.
 * 
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * MIT License for more details.
 * 
 * You should have received a copy of the MIT License
 * along with this program. If not, see <https://opensource.org/licenses/MIT>.
 */

namespace Werules\Chatbot\Api\Data;

interface ChatbotAPIInterface
{

    const UPDATED_AT = 'updated_at';
    const CHAT_ID = 'chat_id';
    const LAST_COMMAND_DETAILS = 'last_command_details';
    const CREATED_AT = 'created_at';
    const HASH_KEY = 'hash_key';
    const CHATBOTAPI_ID = 'chatbotapi_id';
    const LOGGED = 'logged';
    const CHATBOT_TYPE = 'chatbot_type';
    const CONVERSATION_STATE = 'conversation_state';
    const CHATBOTUSER_ID = 'chatbotuser_id';
    const ENABLED = 'enabled';
    const FALLBACK_QTY = 'fallback_qty';


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
     * Get hash_key
     * @return string|null
     */
    public function getHashKey();

    /**
     * Set hash_key
     * @param string $hash_key
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setHashKey($hash_key);

    /**
     * Get logged
     * @return string|null
     */
    public function getLogged();

    /**
     * Set logged
     * @param string $logged
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setLogged($logged);

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

    /**
     * Get last_command_details
     * @return string|null
     */
    public function getLastCommandDetails();

    /**
     * Set last_command_details
     * @param string $last_command_details
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     */
    public function setLastCommandDetails($last_command_details);
}
