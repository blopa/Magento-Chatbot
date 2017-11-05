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

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, \Werules\Chatbot\Model\Message $message)
    {
        $this->_logger = $logger;
        $this->_messageModel = $message;
    }

    /**
     * Execute the cron
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
        $this->_logger->addInfo("Cronjob Worker is executed.");
        $messageCollection = $this->_messageModel->getCollection()
                    ->addFieldToFilter('status', array('eq' => '0'));
        foreach($messageCollection as $m) {
            $this->_logger->addInfo(var_export($m->getContent(), true));
        }
    }
}
