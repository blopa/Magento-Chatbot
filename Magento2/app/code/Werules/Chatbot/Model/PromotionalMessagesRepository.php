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

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Werules\Chatbot\Model\ResourceModel\PromotionalMessages\CollectionFactory as PromotionalMessagesCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Werules\Chatbot\Model\ResourceModel\PromotionalMessages as ResourcePromotionalMessages;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Exception\CouldNotDeleteException;
use Werules\Chatbot\Api\Data\PromotionalMessagesInterfaceFactory;
use Werules\Chatbot\Api\PromotionalMessagesRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Werules\Chatbot\Api\Data\PromotionalMessagesSearchResultsInterfaceFactory;

class PromotionalMessagesRepository implements promotionalMessagesRepositoryInterface
{

    protected $promotionalMessagesCollectionFactory;

    protected $dataPromotionalMessagesFactory;

    protected $dataObjectProcessor;

    private $storeManager;

    protected $dataObjectHelper;

    protected $searchResultsFactory;

    protected $resource;

    protected $promotionalMessagesFactory;


    /**
     * @param ResourcePromotionalMessages $resource
     * @param PromotionalMessagesFactory $promotionalMessagesFactory
     * @param PromotionalMessagesInterfaceFactory $dataPromotionalMessagesFactory
     * @param PromotionalMessagesCollectionFactory $promotionalMessagesCollectionFactory
     * @param PromotionalMessagesSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourcePromotionalMessages $resource,
        PromotionalMessagesFactory $promotionalMessagesFactory,
        PromotionalMessagesInterfaceFactory $dataPromotionalMessagesFactory,
        PromotionalMessagesCollectionFactory $promotionalMessagesCollectionFactory,
        PromotionalMessagesSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->promotionalMessagesFactory = $promotionalMessagesFactory;
        $this->promotionalMessagesCollectionFactory = $promotionalMessagesCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPromotionalMessagesFactory = $dataPromotionalMessagesFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Werules\Chatbot\Api\Data\PromotionalMessagesInterface $promotionalMessages
    ) {
        /* if (empty($promotionalMessages->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $promotionalMessages->setStoreId($storeId);
        } */
        try {
            $promotionalMessages->getResource()->save($promotionalMessages);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the promotionalMessages: %1',
                $exception->getMessage()
            ));
        }
        return $promotionalMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($promotionalMessagesId)
    {
        $promotionalMessages = $this->promotionalMessagesFactory->create();
        $promotionalMessages->getResource()->load($promotionalMessages, $promotionalMessagesId);
        if (!$promotionalMessages->getId()) {
            throw new NoSuchEntityException(__('PromotionalMessages with id "%1" does not exist.', $promotionalMessagesId));
        }
        return $promotionalMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->promotionalMessagesCollectionFactory->create();
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
        \Werules\Chatbot\Api\Data\PromotionalMessagesInterface $promotionalMessages
    ) {
        try {
            $promotionalMessages->getResource()->delete($promotionalMessages);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PromotionalMessages: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($promotionalMessagesId)
    {
        return $this->delete($this->getById($promotionalMessagesId));
    }
}
