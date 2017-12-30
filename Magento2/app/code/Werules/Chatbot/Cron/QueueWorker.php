<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
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

class QueueWorker
{

    protected $_logger;
    protected $_messageModel;
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
        \Werules\Chatbot\Helper\Data $helperData,
        \Werules\Chatbot\Helper\Define $define
    )
    {
        $this->_logger = $logger;
        $this->_messageModel = $message;
        $this->_helper = $helperData;
        $this->_define = $define;
    }

    /**
     * Process all pending messages that for some reason wasn't processed yet
     *
     * @return void
     */
    public function execute()
    {
//        $lock = fopen('werules_chatbot_cron_lock', 'w') or die ('Cannot create lock file');
//        if (flock($lock, LOCK_EX | LOCK_NB)) {
//            while (true)
//            {
//                $messageCollection = $this->_messageModel->getCollection()
//                    ->addFieldToFilter('status', array('eq' => '0'));
//            }
//        }
        if ($this->_helper->getConfigValue('werules_chatbot_danger/general/clear_message_pending') == $this->_define::CLEAR_MESSAGE_QUEUE)
        {
            $messageSenderId = $this->_helper->getConfigValue('werules_chatbot_danger/general/clear_message_sender_id');
            if ($messageSenderId)
            {
                $messageCollection = $this->_messageModel->getCollection()
                    ->addFieldToFilter('sender_id', array('eq' => $messageSenderId))
                ;
            }
            else
                $messageCollection = $this->_messageModel->getCollection();

            foreach ($messageCollection as $message)
            {
                $message->updateMessageStatus($this->_define::PROCESSED);
            }

            $this->_helper->setConfigValue('werules_chatbot_danger/general/clear_message_pending', $this->_define::DONT_CLEAR_MESSAGE_QUEUE);
        }

        $messageQueueMode = $this->_helper->getQueueMessageMode();
        if (($messageQueueMode == $this->_define::QUEUE_NONE) || ($messageQueueMode == $this->_define::QUEUE_NON_RESTRICTIVE))
        {
            $processingLimit = $this->_define::QUEUE_PROCESSING_LIMIT;
            $messageCollection = $this->_messageModel->getCollection()
                ->addFieldToFilter('status', array('neq' => $this->_define::PROCESSED))
            ;
            foreach ($messageCollection as $message)
            {
                $result = array();
                $datetime = date('Y-m-d H:i:s');
                if ( // if not processed neither in the processing queue limit
                    ($message->getStatus() == $this->_define::NOT_PROCESSED) ||
                    (($message->getStatus() == $this->_define::PROCESSING) && ((strtotime($datetime) - strtotime($message->getUpdatedAt())) > $processingLimit))
                )
                {
//                $message->updateMessageStatus($this->_define::PROCESSING);
                    if ($message->getDirection() == $this->_define::INCOMING)
                        $result = $this->_helper->processIncomingMessage($message);
                    else //if ($message->getDirection() == $this->_define::OUTGOING)
                        $result = $this->_helper->processOutgoingMessage($message);
                }

                if (!$result)
                    $message->updateMessageStatus($this->_define::NOT_PROCESSED);
//            else
//                $this->_logger->addInfo('Result of MessageID ' . $message->getMessageId() . ':\n' . var_export($result, true));
            }
        }
        else if (($messageQueueMode == $this->_define::QUEUE_RESTRICTIVE) || ($messageQueueMode == $this->_define::QUEUE_SIMPLE_RESTRICTIVE))
        {
            // get all messages with different sender_id values (aka list of all sender_id)
            $uniqueMessageCollection = $this->_messageModel->getCollection()->distinct(true);
            $uniqueMessageCollection->getSelect()->group('sender_id');

            foreach ($uniqueMessageCollection as $message)
            {
                // foreach unique sender_id, process all their queue messages
                $result = $this->_helper->processIncomingMessageQueueBySenderId($message->getSenderId());
                if ($result)
                    $result = $this->_helper->processOutgoingMessageQueueBySenderId($message->getSenderId());
            }
        }
//        $this->_logger->addInfo("Chatbot Cronjob was executed.");
    }
}
