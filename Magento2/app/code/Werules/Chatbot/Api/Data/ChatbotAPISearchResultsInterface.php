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

interface ChatbotAPISearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get ChatbotAPI list.
     * @return \Werules\Chatbot\Api\Data\ChatbotAPIInterface[]
     */
    public function getItems();

    /**
     * Set hash_key list.
     * @param \Werules\Chatbot\Api\Data\ChatbotAPIInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
