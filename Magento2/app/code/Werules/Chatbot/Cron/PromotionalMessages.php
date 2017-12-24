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

namespace Werules\Chatbot\Cron;

class PromotionalMessages
{

    protected $_logger;
    protected $_messageModel;
    protected $_promotionalMessagesModel;
    protected $_helper;
    protected $_define;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Werules\Chatbot\Model\Message $message,
        \Werules\Chatbot\Model\PromotionalMessages $promotionalMessages,
        \Werules\Chatbot\Helper\Data $helperData,
        \Werules\Chatbot\Helper\Define $define
    )
    {
        $this->_logger = $logger;
        $this->_messageModel = $message;
        $this->_promotionalMessagesModel = $promotionalMessages;
        $this->_helper = $helperData;
        $this->_define = $define;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $promotionalMessageCollection = $this->_promotionalMessagesModel->getCollection()
                    ->addFieldToFilter('status', array('eq' => $this->_define::NOT_SENT));

        if (count($promotionalMessageCollection) > 0)
        {
            $uniqueMessageCollection = $this->_messageModel->getCollection()->distinct(true);
            $uniqueMessageCollection->getSelect()->group('sender_id');
            foreach ($promotionalMessageCollection as $promotionalMessage)
            {
                $messageContent = $promotionalMessage->getContent();
                foreach ($uniqueMessageCollection as $message)
                {
                    $chatbotUser = $this->_helper->getChatbotuserBySenderId($message->getSenderId());
                    if ($chatbotUser->getEnablePromotionalMessages() == $this->_define::DISABLED)
                        continue;

                    $content = array(
                        'content_type' => $this->_define::CONTENT_TEXT,
                        'content' => $messageContent,
                        'current_command_details' => json_encode(array()),
                    );
                    $outgoingMessage = $this->_helper->createOutgoingMessage($message, $content);
                    $result = $this->_helper->processOutgoingMessage($outgoingMessage);

                    if ($result)
                    {
                        if ($promotionalMessage->getStatus() != $this->_define::SENT)
                        {
                            $promotionalMessage->setStatus($this->_define::SENT);
                            $promotionalMessage->setUpdatedAt(date('Y-m-d H:i:s'));
                            $promotionalMessage->save();
                        }
                    }
                }
            }
        }
    }
}
