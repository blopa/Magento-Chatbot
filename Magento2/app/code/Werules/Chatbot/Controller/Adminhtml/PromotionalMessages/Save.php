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

namespace Werules\Chatbot\Controller\Adminhtml\PromotionalMessages;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action
{

    protected $dataPersistor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
//        $data = $this->getRequest()->getPostValue();
        $data = $this->getCompleteData($this->getRequest()->getPostValue());
        if ($data) {
            $id = $this->getRequest()->getParam('promotionalmessages_id');
        
            $model = $this->_objectManager->create('Werules\Chatbot\Model\PromotionalMessages')->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Promotionalmessages no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            $model->setData($data);
        
            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Promotionalmessages.'));
                $this->dataPersistor->clear('werules_chatbot_promotionalmessages');
        
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['promotionalmessages_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Promotionalmessages.'));
            }
        
            $this->dataPersistor->set('werules_chatbot_promotionalmessages', $data);
            return $resultRedirect->setPath('*/*/edit', ['promotionalmessages_id' => $this->getRequest()->getParam('promotionalmessages_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    private function getCompleteData($data)
    {
        if (!$data)
            return array();

        $newData = $data;
        $datetime = date('Y-m-d H:i:s');
        $newData['created_at'] = $datetime;
        $newData['updated_at'] = $datetime;
        $newData['status'] = 0;

        return $newData;
    }
}
