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

namespace Werules\Chatbot\Block\Customer;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $_customerSession;
    protected $_urlBuilder;
    protected $_chatbotUserFactory;
    protected $_chatbotUser;
    protected $_chatbotAPI;
    protected $_define;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Werules\Chatbot\Model\ChatbotUserFactory $chatbotUser,
        \Werules\Chatbot\Model\ResourceModel\ChatbotAPI\CollectionFactory $chatbotAPI,
//        \Werules\Chatbot\Model\ChatbotUser $chatbotAPI,
        \Magento\Customer\Model\Session $customerSession,
        array $data = array()
    )
    {
        $this->_customerSession = $customerSession;
        $this->_chatbotUserFactory  = $chatbotUser;
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_chatbotUser = $this->initChatbotuser();
        $this->_chatbotAPI  = $chatbotAPI;
        $this->_define = new \Werules\Chatbot\Helper\Define;
//        $this->_isScopePrivate = true;
        parent::__construct($context, $data);
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__("Chatbot Settings"));
        return parent::_prepareLayout();
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    public function getPostActionUrl()
    {
        return $this->getUrl('chatbot/customer/save'); // get save URL
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/'); // get last accessed tab
    }

    public function initChatbotuser()
    {
        $customerId = $this->_customerSession->getCustomer()->getId();
        return $this->getChatbotuserByCustomerId($customerId);
    }

    public function getChatbotuserByCustomerId($customerId) // TODO find a better place for this function
    {
        $chatbotUser = $this->_chatbotUserFactory->create();
        $chatbotUser->load($customerId, 'customer_id'); // TODO

        return $chatbotUser;
    }

    public function checkStartedChat()
    {
        if ($this->_chatbotUser->getChatbotuserId())
            return true;

        return false;
    }

    public function checkEnabledPromotion()
    {
        if ($this->_chatbotUser->getEnablePromotionalMessages() == $this->_define::ENABLED)
            return true;

        return false;
    }

    public function checkEnabledMessenger()
    {
        $chatbotAPI = $this->getChatbotAPIByChatbotuserIdAndChatType($this->_chatbotUser->getChatbotuserId(), $this->_define::MESSENGER_INT);
        if ($chatbotAPI->getChatbotapiId())
        {
            if ($chatbotAPI->getEnabled() == $this->_define::ENABLED)
                return true;
        }

        return false;
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
}