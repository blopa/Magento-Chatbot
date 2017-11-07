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

//    public function requestHandler($api_name)
//    {
//        if ($api_name == $this->_define::MESSENGER_INT)
//        {
//
//        }
//    }
}
