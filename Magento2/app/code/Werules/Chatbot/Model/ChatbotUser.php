<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
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

use Werules\Chatbot\Api\Data\ChatbotUserInterface;

class ChatbotUser extends \Magento\Framework\Model\AbstractModel implements ChatbotUserInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Werules\Chatbot\Model\ResourceModel\ChatbotUser');
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
     * @param string $chatbotuserId
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setChatbotuserId($chatbotuserId)
    {
        return $this->setData(self::CHATBOTUSER_ID, $chatbotuserId);
    }

    /**
     * Get customer_id
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customer_id
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setCustomerId($customer_id)
    {
        return $this->setData(self::CUSTOMER_ID, $customer_id);
    }

    /**
     * Get quote_id
     * @return string
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * Set quote_id
     * @param string $quote_id
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setQuoteId($quote_id)
    {
        return $this->setData(self::QUOTE_ID, $quote_id);
    }

    /**
     * Get session_id
     * @return string
     */
    public function getSessionId()
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * Set session_id
     * @param string $session_id
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setSessionId($session_id)
    {
        return $this->setData(self::SESSION_ID, $session_id);
    }

    /**
     * Get enable_promotional_messages
     * @return string
     */
    public function getEnablePromotionalMessages()
    {
        return $this->getData(self::ENABLE_PROMOTIONAL_MESSAGES);
    }

    /**
     * Set enable_promotional_messages
     * @param string $enable_promotional_messages
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setEnablePromotionalMessages($enable_promotional_messages)
    {
        return $this->setData(self::ENABLE_PROMOTIONAL_MESSAGES, $enable_promotional_messages);
    }

    /**
     * Get enable_support
     * @return string
     */
    public function getEnableSupport()
    {
        return $this->getData(self::ENABLE_SUPPORT);
    }

    /**
     * Set enable_support
     * @param string $enable_support
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setEnableSupport($enable_support)
    {
        return $this->setData(self::ENABLE_SUPPORT, $enable_support);
    }

    /**
     * Get admin
     * @return string
     */
    public function getAdmin()
    {
        return $this->getData(self::ADMIN);
    }

    /**
     * Set admin
     * @param string $admin
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setAdmin($admin)
    {
        return $this->setData(self::ADMIN, $admin);
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
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
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
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     */
    public function setUpdatedAt($updated_at)
    {
        return $this->setData(self::UPDATED_AT, $updated_at);
    }

    // CUSTOM METHODS
}
