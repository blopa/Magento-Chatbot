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

use Werules\Chatbot\Api\ChatbotAPIRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Werules\Chatbot\Model\ResourceModel\ChatbotAPI as ResourceChatbotAPI;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Werules\Chatbot\Api\Data\ChatbotAPIInterfaceFactory;
use Werules\Chatbot\Api\Data\ChatbotAPISearchResultsInterfaceFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Werules\Chatbot\Model\ResourceModel\ChatbotAPI\CollectionFactory as ChatbotAPICollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;

class ChatbotAPIRepository implements chatbotAPIRepositoryInterface
{

	protected $chatbotAPIFactory;

	protected $dataChatbotAPIFactory;

	protected $dataObjectProcessor;

	private $storeManager;

	protected $dataObjectHelper;

	protected $resource;

	protected $chatbotAPICollectionFactory;

	protected $searchResultsFactory;


	/**
	 * @param ResourceChatbotAPI $resource
	 * @param ChatbotAPIFactory $chatbotAPIFactory
	 * @param ChatbotAPIInterfaceFactory $dataChatbotAPIFactory
	 * @param ChatbotAPICollectionFactory $chatbotAPICollectionFactory
	 * @param ChatbotAPISearchResultsInterfaceFactory $searchResultsFactory
	 * @param DataObjectHelper $dataObjectHelper
	 * @param DataObjectProcessor $dataObjectProcessor
	 * @param StoreManagerInterface $storeManager
	 */
	public function __construct(
		ResourceChatbotAPI $resource,
		ChatbotAPIFactory $chatbotAPIFactory,
		ChatbotAPIInterfaceFactory $dataChatbotAPIFactory,
		ChatbotAPICollectionFactory $chatbotAPICollectionFactory,
		ChatbotAPISearchResultsInterfaceFactory $searchResultsFactory,
		DataObjectHelper $dataObjectHelper,
		DataObjectProcessor $dataObjectProcessor,
		StoreManagerInterface $storeManager
	) {
		$this->resource = $resource;
		$this->chatbotAPIFactory = $chatbotAPIFactory;
		$this->chatbotAPICollectionFactory = $chatbotAPICollectionFactory;
		$this->searchResultsFactory = $searchResultsFactory;
		$this->dataObjectHelper = $dataObjectHelper;
		$this->dataChatbotAPIFactory = $dataChatbotAPIFactory;
		$this->dataObjectProcessor = $dataObjectProcessor;
		$this->storeManager = $storeManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save(
		\Werules\Chatbot\Api\Data\ChatbotAPIInterface $chatbotAPI
	) {
		/* if (empty($chatbotAPI->getStoreId())) {
			$storeId = $this->storeManager->getStore()->getId();
			$chatbotAPI->setStoreId($storeId);
		} */
		try {
			$chatbotAPI->getResource()->save($chatbotAPI);
		} catch (\Exception $exception) {
			throw new CouldNotSaveException(__(
				'Could not save the chatbotAPI: %1',
				$exception->getMessage()
			));
		}
		return $chatbotAPI;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getById($chatbotAPIId)
	{
		$chatbotAPI = $this->chatbotAPIFactory->create();
		$chatbotAPI->getResource()->load($chatbotAPI, $chatbotAPIId);
		if (!$chatbotAPI->getId()) {
			throw new NoSuchEntityException(__('ChatbotAPI with id "%1" does not exist.', $chatbotAPIId));
		}
		return $chatbotAPI;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getList(
		\Magento\Framework\Api\SearchCriteriaInterface $criteria
	) {
		$collection = $this->chatbotAPICollectionFactory->create();
		foreach ($criteria->getFilterGroups() as $filterGroup) {
			foreach ($filterGroup->getFilters() as $filter) {
				if ($filter->getField() === 'store_id') {
					$collection->addStoreFilter($filter->getValue(), false);
					continue;
				}
				$condition = $filter->getConditionType() ?: 'eq';
				$collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
			}
		}

		$sortOrders = $criteria->getSortOrders();
		if ($sortOrders) {
			/** @var SortOrder $sortOrder */
			foreach ($sortOrders as $sortOrder) {
				$collection->addOrder(
					$sortOrder->getField(),
					($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
				);
			}
		}
		$collection->setCurPage($criteria->getCurrentPage());
		$collection->setPageSize($criteria->getPageSize());

		$searchResults = $this->searchResultsFactory->create();
		$searchResults->setSearchCriteria($criteria);
		$searchResults->setTotalCount($collection->getSize());
		$searchResults->setItems($collection->getItems());
		return $searchResults;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(
		\Werules\Chatbot\Api\Data\ChatbotAPIInterface $chatbotAPI
	) {
		try {
			$chatbotAPI->getResource()->delete($chatbotAPI);
		} catch (\Exception $exception) {
			throw new CouldNotDeleteException(__(
				'Could not delete the ChatbotAPI: %1',
				$exception->getMessage()
			));
		}
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteById($chatbotAPIId)
	{
		return $this->delete($this->getById($chatbotAPIId));
	}
}
