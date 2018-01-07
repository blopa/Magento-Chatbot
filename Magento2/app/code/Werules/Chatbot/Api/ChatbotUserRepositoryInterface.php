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

namespace Werules\Chatbot\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ChatbotUserRepositoryInterface
{


    /**
     * Save ChatbotUser
     * @param \Werules\Chatbot\Api\Data\ChatbotUserInterface $chatbotUser
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Werules\Chatbot\Api\Data\ChatbotUserInterface $chatbotUser
    );

    /**
     * Retrieve ChatbotUser
     * @param string $chatbotuserId
     * @return \Werules\Chatbot\Api\Data\ChatbotUserInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($chatbotuserId);

    /**
     * Retrieve ChatbotUser matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Werules\Chatbot\Api\Data\ChatbotUserSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ChatbotUser
     * @param \Werules\Chatbot\Api\Data\ChatbotUserInterface $chatbotUser
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Werules\Chatbot\Api\Data\ChatbotUserInterface $chatbotUser
    );

    /**
     * Delete ChatbotUser by ID
     * @param string $chatbotuserId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($chatbotuserId);
}
