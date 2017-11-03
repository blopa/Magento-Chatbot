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

interface MessageRepositoryInterface
{


	/**
	 * Save Message
	 * @param \Werules\Chatbot\Api\Data\MessageInterface $message
	 * @return \Werules\Chatbot\Api\Data\MessageInterface
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function save(
		\Werules\Chatbot\Api\Data\MessageInterface $message
	);

	/**
	 * Retrieve Message
	 * @param string $messageId
	 * @return \Werules\Chatbot\Api\Data\MessageInterface
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function getById($messageId);

	/**
	 * Retrieve Message matching the specified criteria.
	 * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
	 * @return \Werules\Chatbot\Api\Data\MessageSearchResultsInterface
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function getList(
		\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
	);

	/**
	 * Delete Message
	 * @param \Werules\Chatbot\Api\Data\MessageInterface $message
	 * @return bool true on success
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function delete(
		\Werules\Chatbot\Api\Data\MessageInterface $message
	);

	/**
	 * Delete Message by ID
	 * @param string $messageId
	 * @return bool true on success
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function deleteById($messageId);
}
