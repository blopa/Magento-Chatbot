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

interface PromotionalMessagesInterface
{

    const UPDATED_AT = 'updated_at';
    const CONTENT = 'content';
    const CREATED_AT = 'created_at';
    const STATUS = 'status';
    const PROMOTIONALMESSAGES_ID = 'promotionalmessages_id';


    /**
     * Get promotionalmessages_id
     * @return string|null
     */
    public function getPromotionalmessagesId();

    /**
     * Set promotionalmessages_id
     * @param string $promotionalmessages_id
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setPromotionalmessagesId($promotionalmessagesId);

    /**
     * Get content
     * @return string|null
     */
    public function getContent();

    /**
     * Set content
     * @param string $content
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setContent($content);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $created_at
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
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
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setUpdatedAt($updated_at);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     */
    public function setStatus($status);
}
