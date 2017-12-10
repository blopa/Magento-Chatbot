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

class Werules_Chatbot_Model_Api_witAI extends Mage_Core_Model_Mysql4_Abstract {

    protected $_token;
    protected $_version = '11/12/2017';
    //protected $_data;

    /// Class constructor
    public function __construct($token) {
        $this->_token = $token;
    }

    function getTextResponse($query) {
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $this->_token . "\r\n"
            )
        );
        $content = "&q=" . urlencode($query);
        return $this->getWitAIResponse("message", $content, $options);
    }

    function getAudioResponse($audioFile) {
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $this->_token . "\n" .
                    "Content-Type: audio/mpeg3" . "\r\n",
                'content' => file_get_contents($audioFile)
            )
        );
        return $this->getWitAIResponse("speech", "", $options);
    }

    function getWitAIResponse($endPoint, $content, $options) {
        $context = stream_context_create($options);
        $url = 'https://api.wit.ai/' . $endPoint . '?v=' . $this->_version . $content;
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result, true);

        if ($result)
            return $result;

        return null;
    }
}
