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

interface MessageInterface
{

    const SENDER_ID = 'sender_id';
    const CREATED_AT = 'created_at';
    const MESSAGE_ID = 'message_id';
    const CONTENT = 'content';
    const DIRECTION = 'direction';
    const CHAT_MESSAGE_ID = 'chat_message_id';
    const CHATBOT_TYPE = 'chatbot_type';
    const UPDATED_AT = 'updated_at';
    const STATUS = 'status';


    /**
     * Get message_id
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set message_id
     * @param string $message_id
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setMessageId($messageId);

    /**
     * Get sender_id
     * @return string|null
     */
    public function getSenderId();

    /**
     * Set sender_id
     * @param string $sender_id
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setSenderId($sender_id);

    /**
     * Get content
     * @return string|null
     */
    public function getContent();

    /**
     * Set content
     * @param string $content
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setContent($content);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setStatus($status);

    /**
     * Get direction
     * @return string|null
     */
    public function getDirection();

    /**
     * Set direction
     * @param string $direction
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setDirection($direction);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $created_at
     * @return \Werules\Chatbot\Api\Data\MessageInterface
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
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setUpdatedAt($updated_at);

    /**
     * Get chat_message_id
     * @return string|null
     */
    public function getChatMessageId();

    /**
     * Set chat_message_id
     * @param string $chat_message_id
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setChatMessageId($chat_message_id);

    /**
     * Get chatbot_type
     * @return string|null
     */
    public function getChatbotType();

    /**
     * Set chatbot_type
     * @param string $chatbot_type
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setChatbotType($chatbot_type);
}
