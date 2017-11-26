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

class Login extends \Magento\Framework\App\Action\Action
{
    protected $_urlBuilder;
    protected $_request;
    protected $_chatbotUser;
    protected $_chatbotAPI;
    protected $_customerSession;
    protected $_define;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Request\Http $request,
        \Werules\Chatbot\Model\ChatbotUserFactory $chatbotUser,
        \Werules\Chatbot\Model\ChatbotAPIFactory $chatbotAPI,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->_request = $request;
        $this->_chatbotUser  = $chatbotUser;
        $this->_chatbotAPI  = $chatbotAPI;
        $this->_customerSession = $customerSession;
        $this->_define = new \Werules\Chatbot\Helper\Define;
        parent::__construct($context);
    }

    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (!$this->_customerSession->authenticate()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    public function execute()
    {
        $hashKey = $this->_request->getParam('hash');
        if ($hashKey)
        {
            $chatbotAPI = $this->_chatbotAPI->create();
            $chatbotAPI->load($hashKey, 'hash_key'); // TODO
            if ($chatbotAPI->getChatbotapiId())
            {
                $customerId = $this->_customerSession->getCustomer()->getId();
                $chatbotUser = $this->getChatbotuserByCustomerId($customerId);

                if ($chatbotUser->getChatbotuserId())
                {
                    $chatbotAPI->setChatbotuserId($chatbotUser->getChatbotuserId());
                    $chatbotAPI->setLogged($this->_define::LOGGED);
                    $chatbotAPI->save();
                }
                else
                {
                    $chatbotUser->setCustomerId($customerId);
//                $chatbotUser->setQuoteId();
//                $chatbotUser->setSessionId();
                    $chatbotUser->setEnablePromotionalMessages($this->_define::ENABLED);
                    $chatbotUser->setEnableSupport($this->_define::ENABLED);
                    $chatbotUser->setLogged($this->_define::NOT_LOGGED);
                    $chatbotUser->setAdmin($this->_define::NOT_ADMIN);
                    $datetime = date('Y-m-d H:i:s');
                    $chatbotUser->setCreatedAt($datetime);
                    $chatbotUser->setUpdatedAt($datetime);
                    $chatbotUser->save();

                    $chatbotAPI->setChatbotuserId($chatbotUser->getChatbotuserId());
                    $chatbotAPI->setLogged($this->_define::LOGGED);
                    $chatbotAPI->save();
                }
                $this->messageManager->addSuccessMessage(__("Chatbot settings saved successfully."));
            }
            else
                $this->messageManager->addErrorMessage(__("Invalid URL hash, please try again."));
        }
        else
            $this->messageManager->addErrorMessage(__("Something went wrong, please try again."));

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getReturnUrl());

        return $resultRedirect;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    public function getReturnUrl()
    {
        return $this->getUrl('chatbot/customer/index');
    }

    public function getChatbotuserByCustomerId($customerId) // TODO find a better place for this function
    {
        $chatbotUser = $this->_chatbotUser->create();
        $chatbotUser->load($customerId, 'customer_id'); // TODO

        return $chatbotUser;
    }
}