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

namespace Werules\Chatbot\Block\Chatbox;

class Messenger extends \Magento\Framework\View\Element\Template
{
    protected $_helper;
    protected $_define;
    protected $_chatbotAPI;
    protected $_store;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Werules\Chatbot\Helper\Data $helperData,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Werules\Chatbot\Model\ChatbotAPI $chatbotAPI,
        array $data = array()
    )
    {
        $this->_chatbotAPI = $chatbotAPI;
        $this->_helper = $helperData;
        $this->_store = $store;
        $this->_define = new \Werules\Chatbot\Helper\Define;
        parent::__construct($context, $data);
    }

    private function getMessengerInstance()
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $messenger = $this->_chatbotAPI->initMessengerAPI($api_token);
        return $messenger;
    }

    public function getFacebookPageId()
    {
        $pageId = $this->getConfigValue('werules_chatbot_messenger/general/page_id');
        if ($pageId)
            return $pageId;

        $messengerInstance = $this->getMessengerInstance();
        $pageDetails = $messengerInstance->getPageDetails();
        if (isset($pageDetails['id']))
        {
            $pageId = $pageDetails['id'];
            $this->setConfigValue('werules_chatbot_messenger/general/page_id', $pageId);
            return $pageId;
        }

        return '';
    }

    public function getFacebookAppId()
    {
        $appId = $this->getConfigValue('werules_chatbot_messenger/general/app_id');
        return $appId;
    }

    public function isDomainWhitelisted()
    {
        $enable = $this->getConfigValue('werules_chatbot_messenger/general/domain_whitelisted');
        if ($enable)
            return true;

        $messengerInstance = $this->getMessengerInstance();
//        $url = parse_url($_SERVER['SERVER_NAME'], PHP_URL_HOST);
        $url = $_SERVER['SERVER_NAME'];
        $domain = array($url);
        $result = $messengerInstance->addDomainsToWhitelist($domain);
        if (!isset($result['error']))
        {
            $this->setConfigValue('werules_chatbot_messenger/general/domain_whitelisted', $this->_define::WHITELABELED);
            return true;
        }

        return false;
    }

    public function isChatboxEnabled()
    {
        $enable = $this->getConfigValue('werules_chatbot_messenger/general/enable_messenger_box');
        $isWhitelisted = $this->isDomainWhitelisted();
        if ($enable && $isWhitelisted)
            return true;

        return false;
    }

    private function getConfigValue($code)
    {
        return $this->_helper->getConfigValue($code);
    }

    private function setConfigValue($field, $value)
    {
        $this->_helper->setConfigValue($field, $value);
    }

    public function getLocaleCode()
    {
        return $this->_store->getLocaleCode();
    }
}
