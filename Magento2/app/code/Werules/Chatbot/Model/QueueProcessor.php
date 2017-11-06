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

namespace Werules\Chatbot\Model;


class QueueProcessor extends \Magento\Framework\Model\AbstractModel
{
    protected $_messageModel;
    protected $_logger;

    protected function _construct
    (
        \Psr\Log\LoggerInterface $logger,
        \Werules\Chatbot\Model\Message $message
    )
    {
        $this->_messageModel = $message;
    }

    public function processIncomingMessage($message_id)
    {
        $messageModel = $this->_messageModel->create();
        $message = $messageModel->load($message_id);
        // TODO do something
        $this->_logger->addInfo("Message ID -> " . $message->getMessageId());
        $this->_logger->addInfo("Message Content -> " . $message->getContent());
    }

    public function processOutgoingMessage($message_id)
    {
        $messageModel = $this->_messageModel->create();
        $message = $messageModel->load($message_id);
        // TODO do something
        $this->_logger->addInfo("Message ID -> " . $message->getMessageId());
        $this->_logger->addInfo("Message Content -> " . $message->getContent());
    }
}