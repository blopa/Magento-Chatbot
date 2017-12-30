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

namespace Werules\Chatbot\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ChatbotAPIRepositoryInterface
{


    /**
     * Save ChatbotAPI
     * @param \Werules\Chatbot\Api\Data\ChatbotAPIInterface $chatbotAPI
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Werules\Chatbot\Api\Data\ChatbotAPIInterface $chatbotAPI
    );

    /**
     * Retrieve ChatbotAPI
     * @param string $chatbotapiId
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($chatbotapiId);

    /**
     * Retrieve ChatbotAPI matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Werules\Chatbot\Api\Data\ChatbotAPISearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ChatbotAPI
     * @param \Werules\Chatbot\Api\Data\ChatbotAPIInterface $chatbotAPI
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Werules\Chatbot\Api\Data\ChatbotAPIInterface $chatbotAPI
    );

    /**
     * Delete ChatbotAPI by ID
     * @param string $chatbotapiId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($chatbotapiId);
}
