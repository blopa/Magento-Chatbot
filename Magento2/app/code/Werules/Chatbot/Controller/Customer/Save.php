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

namespace Werules\Chatbot\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Framework\App\Action\Action
{
    protected $_customerSession;
    protected $_urlBuilder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $post = (array)$this->getRequest()->getPost();

        if (!empty($post))
        {
            // Retrieve your form data
            $enablePromotion = $post['enable_promotion'];
            $enableMessenger = $post['enable_messenger'];
            $id = $this->_customerSession->getCustomer()->getId();

            // Doing-something with...

            // Display the success form validation message
            $this->messageManager->addSuccessMessage('enablePromotion - ' . $enablePromotion . ' / enableMessenger - ' . $enableMessenger . ' / customer id - ' . $id);

            // Redirect to your form page (or anywhere you want...)
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->getReturnUrl());

            return $resultRedirect;
        }
        // Render the page
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    public function getReturnUrl()
    {
        return $this->getUrl('chatbot/customer/index');
    }
}