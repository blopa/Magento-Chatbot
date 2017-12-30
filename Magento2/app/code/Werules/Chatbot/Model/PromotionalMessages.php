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

use Werules\Chatbot\Api\Data\PromotionalMessagesInterface;

class PromotionalMessages extends \Magento\Framework\Model\AbstractModel implements PromotionalMessagesInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Werules\Chatbot\Model\ResourceModel\PromotionalMessages');
    }

    /**
     * Get promotionalmessages_id
     * @return string
     */
    public function getPromotionalmessagesId()
    {
        return $this->getData(self::PROMOTIONALMESSAGES_ID);
    }

    /**
     * Set promotionalmessages_id
     * @param string $promotionalmessagesId
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setPromotionalmessagesId($promotionalmessagesId)
    {
        return $this->setData(self::PROMOTIONALMESSAGES_ID, $promotionalmessagesId);
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
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
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
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
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
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setUpdatedAt($updated_at)
    {
        return $this->setData(self::UPDATED_AT, $updated_at);
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
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}
