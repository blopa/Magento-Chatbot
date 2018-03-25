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

namespace Werules\Chatbot\Block\Webhook;

class Telegram extends \Werules\Chatbot\Block\Webhook\Index
{
    public function requestHandler()
    {
        // TODO
        $messageObject = new \stdClass();
        $this->messageHandler($messageObject);
    }

    protected function processRequest()
    {
        $telegramInstance = $this->getTelegramInstance();
        if (!$telegramInstance->getData())
            return $this->getJsonErrorResponse();
        $this->logPostData($telegramInstance->getData(), 'werules_chatbot_telegram.log');

        $messageObject = $this->createMessageObject($telegramInstance);
        if (isset($messageObject->content))
            $result = $this->messageHandler($messageObject);
        else
            $result = $this->getJsonSuccessResponse(); // return success to avoid receiving the same message again

        return $result;
    }

    protected function getTelegramInstance()
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_telegram/general/api_key');
        $telegram = $this->_chatbotAPI->initTelegramAPI($api_token);
        return $telegram;
    }

    protected function createMessageObject($telegram)
    {
        $messageObject = new \stdClass();
        if ($telegram->Text())
            $content = $telegram->Text();
        else
            return $messageObject;

        $messageObject->senderId = $telegram->ChatID();
        $messageObject->content = $content;
        $messageObject->status = $this->_define::PROCESSING;
        $messageObject->direction = $this->_define::INCOMING;
        $messageObject->chatType = $this->_define::TELEGRAM_INT; // TODO
        $messageObject->contentType = $this->_define::CONTENT_TEXT; // TODO
        $messageObject->currentCommandDetails = $this->_define::CURRENT_COMMAND_DETAILS_DEFAULT; // TODO
        $messageObject->messagePayload = '{}'; // TODO
        $messageObject->chatMessageId = $telegram->MessageID();
//        if ($telegram->getMessageTimestamp())
//            $messageObject->sentAt = substr($telegram->getMessageTimestamp(), 0, 10);
//        else
            $messageObject->sentAt = time();
        $datetime = date('Y-m-d H:i:s');
        $messageObject->createdAt = $datetime;
        $messageObject->updatedAt = $datetime;

        return $messageObject;
    }
}
