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

class Worker
{

    protected $_logger;
    protected $_messageModel;
    protected $_helper;

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
        $processingLimit = $this->_define::SECONDS_IN_MINUTE * 3;
        $messageCollection = $this->_messageModel->getCollection()
            ->addFieldToFilter('status', array('neq' => $this->_define::PROCESSED))
        ;
        foreach ($messageCollection as $message) {
            $result = true;
            $datetime = date('Y-m-d H:i:s');
            if ($message->getStatus() == $this->_define::NOT_PROCESSED)
            {
                $message->updateMessageStatus($this->_define::PROCESSING);
                $result = $this->_helper->processMessage($message->getMessageId()); // TODO
            }
            else if (($message->getStatus() == $this->_define::PROCESSING) && ((strtotime($datetime) - strtotime($message->getUpdatedAt())) > $processingLimit))
            {
                // if a message is in 'processing' status for more than 3 minutes, try to reprocess it
//                $message->updateMessageStatus($this->_define::PROCESSING); // already on 'processing' status
                $result = $this->_helper->processMessage($message->getMessageId()); // TODO
            }

            if (!$result)
                $message->updateMessageStatus($this->_define::NOT_PROCESSED);
//            else
//                $this->_logger->addInfo('Result of MessageID ' . $message->getMessageId() . ':\n' . var_export($result, true));
        }
//        $this->_logger->addInfo("Chatbot Cronjob was executed.");
    }
}
