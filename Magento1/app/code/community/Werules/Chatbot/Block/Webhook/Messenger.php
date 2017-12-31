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

class Werules_Chatbot_Block_Webhook_Messenger extends Werules_Chatbot_Block_Webhook_Index
{
    public function processRequest()
    {
        $challenge_hub = $this->getConfigValue('werules_chatbot_messenger/general/enable_hub_challenge');
        if ($challenge_hub == $this->_define->ENABLED)
        {
            $hub_token = $this->getConfigValue('werules_chatbot_general/general/custom_key');
            $verification_hub = $this->getVerificationHub($hub_token);
            if ($verification_hub)
                $result = $verification_hub;
            else
                $result = __("Please check your Hub Verify Token.");
        }
        else // process message
        {
            $messengerInstance = $this->getMessengerInstance();
            $this->logPostData($messengerInstance->getPostData(), 'werules_chatbot_messenger.log');

            $messageObject = $this->createMessageObject($messengerInstance);
            if (isset($messageObject->content))
                $result = $this->messageHandler($messageObject);
            else
                $result = $this->getJsonSuccessResponse(); // return success to avoid receiving the same message again
        }

        return $result;
    }

    protected function getMessengerInstance()
    {
        $api_token = $this->_helper->getConfigValue('werules_chatbot_messenger/general/api_key');
        $messenger = $this->_chatbotAPI->initMessengerAPI($api_token);
        return $messenger;
    }

    protected function createMessageObject($messenger)
    {
        $messageObject = new stdClass();
        if ($messenger->Text())
            $content = $messenger->Text();
        else
            $content = $messenger->getPostbackTitle();
        if (!$content)
            return $messageObject;

        $messageObject->senderId = $messenger->ChatID();
        $messageObject->content = $content;
        $messageObject->status = $this->_define->PROCESSING;
        $messageObject->direction = $this->_define->INCOMING;
        $messageObject->chatType = $this->_define->MESSENGER_INT; // TODO
        $messageObject->contentType = $this->_define->CONTENT_TEXT; // TODO
        $messageObject->currentCommandDetails = $this->_define->CURRENT_COMMAND_DETAILS_DEFAULT; // TODO
        $messageObject->messagePayload = $this->getMessengerPayload($messenger); // TODO
        $messageObject->chatMessageId = $messenger->MessageID();
        if ($messenger->getMessageTimestamp())
            $messageObject->sentAt = (int)$messenger->getMessageTimestamp();
        else
            $messageObject->sentAt = time();
        $datetime = date('Y-m-d H:i:s');
        $messageObject->createdAt = $datetime;
        $messageObject->updatedAt = $datetime;

        return $messageObject;
    }

    protected function getMessengerPayload($messenger)
    {
        $payload = $messenger->getPostbackPayload();
        if (!$payload)
            $payload = $messenger->getQuickReplyPayload();

        return $payload;
    }
}