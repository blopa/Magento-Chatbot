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
    protected $_chatbotUser;
    protected $_chatbotAPI;
    protected $_define;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Werules\Chatbot\Model\ChatbotUserFactory $chatbotUser,
        \Werules\Chatbot\Model\ResourceModel\ChatbotAPI\CollectionFactory $chatbotAPI,
//        \Werules\Chatbot\Model\ChatbotUser $chatbotAPI,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_chatbotUser  = $chatbotUser;
        $this->_chatbotAPI  = $chatbotAPI;
        $this->_urlBuilder = $urlBuilder;
        $this->_customerSession = $customerSession;
        $this->_define = new \Werules\Chatbot\Helper\Define;
        parent::__construct($context);
    }

    public function execute()
    {
        $post = (array)$this->getRequest()->getPost();

        if (!empty($post))
        {
            $saved = false;
            $enableMessenger = 0;
            $enablePromotion = 0;
            if (isset($post['enable_promotion']))
                    $enablePromotion = $post['enable_promotion'];
            if (isset($post['enable_messenger']))
                    $enableMessenger = $post['enable_messenger'];

            $customerId = $this->_customerSession->getCustomer()->getId();
            $chatbotUser = $this->getChatbotuserByCustomerId($customerId);

            if ($chatbotUser->getChatbotuserId())
            {
                if ($chatbotUser->getEnablePromotionalMessages() != $enablePromotion)
                {
                    $chatbotUser->setEnablePromotionalMessages($enablePromotion);
                    $chatbotUser->save();
                    $saved = true;
                }

                $messengerChatbotAPI = $this->getChatbotAPIByChatbotuserIdAndChatType($chatbotUser->getChatbotuserId(), $this->_define::MESSENGER_INT);
                if ($messengerChatbotAPI->getChatbotapiId())
                {
                    if ($messengerChatbotAPI->getEnabled() != $enableMessenger)
                    {
                        $messengerChatbotAPI->setEnabled($enableMessenger);
                        $messengerChatbotAPI->save();
                        $saved = true;
                    }
                }

                // TODO add other chatbots apis here

                if ($saved)
                    $this->messageManager->addSuccessMessage(__("Chatbot settings saved successfully."));
                else
                    $this->messageManager->addWarning(__("You haven't changed any data in the settings."));
            }
            else
                $this->messageManager->addErrorMessage(__("You still haven't chat with our Chatbot."));
        }
        else
            $this->messageManager->addErrorMessage(__("Something went wrong, please try again."));

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getReturnUrl());

        return $resultRedirect;

//        $this->_view->loadLayout();
//        $this->_view->renderLayout();
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    public function getReturnUrl()
    {
        return $this->getUrl('chatbot/customer/index');
    }

    public function getChatbotAPIByChatbotuserIdAndChatType($chatbotUserId, $chatType) // TODO find a better place for this function
    {
//        $chatbotAPI = $this->_chatbotAPI->create();
//        $chatbotAPI->load($chatbotUserId, 'chatbotuser_id'); // TODO
        $chatbotAPI = $this->_chatbotAPI
            ->create()
            ->addFieldToFilter('chatbotuser_id', $chatbotUserId)
            ->addFieldToFilter('chatbot_type', $chatType)
            ->getFirstItem()
        ;

        return $chatbotAPI;
    }

    public function getChatbotuserByCustomerId($customerId) // TODO find a better place for this function
    {
        $chatbotUser = $this->_chatbotUser->create();
        $chatbotUser->load($customerId, 'customer_id'); // TODO

        return $chatbotUser;
    }
}