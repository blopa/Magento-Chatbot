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

namespace Werules\Chatbot\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface PromotionalMessagesRepositoryInterface
{


    /**
     * Save PromotionalMessages
     * @param \Werules\Chatbot\Api\Data\PromotionalMessagesInterface $promotionalMessages
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Werules\Chatbot\Api\Data\PromotionalMessagesInterface $promotionalMessages
    );

    /**
     * Retrieve PromotionalMessages
     * @param string $promotionalmessagesId
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($promotionalmessagesId);

    /**
     * Retrieve PromotionalMessages matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Werules\Chatbot\Api\Data\PromotionalMessagesSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete PromotionalMessages
     * @param \Werules\Chatbot\Api\Data\PromotionalMessagesInterface $promotionalMessages
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Werules\Chatbot\Api\Data\PromotionalMessagesInterface $promotionalMessages
    );

    /**
     * Delete PromotionalMessages by ID
     * @param string $promotionalmessagesId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($promotionalmessagesId);
}
