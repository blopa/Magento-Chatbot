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

namespace Werules\Chatbot\Model;

use Werules\Chatbot\Api\Data\MessageInterface;

class Message extends \Magento\Framework\Model\AbstractModel implements MessageInterface
{
    protected $_define;
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_define = new \Werules\Chatbot\Helper\Define;
        $this->_init('Werules\Chatbot\Model\ResourceModel\Message');
    }

    /**
     * Get message_id
     * @return string
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * Set message_id
     * @param string $messageId
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setMessageId($messageId)
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    /**
     * Get sender_id
     * @return string
     */
    public function getSenderId()
    {
        return $this->getData(self::SENDER_ID);
    }

    /**
     * Set sender_id
     * @param string $sender_id
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setSenderId($sender_id)
    {
        return $this->setData(self::SENDER_ID, $sender_id);
    }

    /**
     * Get content
     * @return string
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * Set content
     * @param string $content
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * Get status
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set status
     * @param string $status
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get direction
     * @return string
     */
    public function getDirection()
    {
        return $this->getData(self::DIRECTION);
    }

    /**
     * Set direction
     * @param string $direction
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setDirection($direction)
    {
        return $this->setData(self::DIRECTION, $direction);
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
     * @return \Werules\Chatbot\Api\Data\MessageInterface
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
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setUpdatedAt($updated_at)
    {
        return $this->setData(self::UPDATED_AT, $updated_at);
    }

    /**
     * Get chat_message_id
     * @return string
     */
    public function getChatMessageId()
    {
        return $this->getData(self::CHAT_MESSAGE_ID);
    }

    /**
     * Set chat_message_id
     * @param string $chat_message_id
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setChatMessageId($chat_message_id)
    {
        return $this->setData(self::CHAT_MESSAGE_ID, $chat_message_id);
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
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setChatbotType($chatbot_type)
    {
        return $this->setData(self::CHATBOT_TYPE, $chatbot_type);
    }

    /**
     * Get content_type
     * @return string
     */
    public function getContentType()
    {
        return $this->getData(self::CONTENT_TYPE);
    }

    /**
     * Set content_type
     * @param string $content_type
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setContentType($content_type)
    {
        return $this->setData(self::CONTENT_TYPE, $content_type);
    }

    /**
     * Get message_payload
     * @return string
     */
    public function getMessagePayload()
    {
        return $this->getData(self::MESSAGE_PAYLOAD);
    }

    /**
     * Set message_payload
     * @param string $message_payload
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setMessagePayload($message_payload)
    {
        return $this->setData(self::MESSAGE_PAYLOAD, $message_payload);
    }

    /**
     * Get sent_at
     * @return string
     */
    public function getSentAt()
    {
        return $this->getData(self::SENT_AT);
    }

    /**
     * Set sent_at
     * @param string $sent_at
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setSentAt($sent_at)
    {
        return $this->setData(self::SENT_AT, $sent_at);
    }

    /**
     * Get current_command_details
     * @return string
     */
    public function getCurrentCommandDetails()
    {
        return $this->getData(self::CURRENT_COMMAND_DETAILS);
    }

    /**
     * Set current_command_details
     * @param string $current_command_details
     * @return \Werules\Chatbot\Api\Data\MessageInterface
     */
    public function setCurrentCommandDetails($current_command_details)
    {
        return $this->setData(self::CURRENT_COMMAND_DETAILS, $current_command_details);
    }

    // CUSTOM METHODS

    public function updateIncomingMessageStatus($status)
    {
        return $this->updateMessageStatus($status);
    }

    public function updateOutgoingMessageStatus($status)
    {
        return $this->updateMessageStatus($status);
    }

    public function updateMessageStatus($status)
    {
        $this->setStatus($status);
        $datetime = date('Y-m-d H:i:s');
        $this->setUpdatedAt($datetime);
        $this->save();

        return true;
    }

    public function updateSentAt($timestamp)
    {
        $this->setSentAt($timestamp);
        $datetime = date('Y-m-d H:i:s');
        $this->setUpdatedAt($datetime);
        $this->save();

        return true;
    }
}
