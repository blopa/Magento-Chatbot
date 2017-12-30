<?php
/**
 * Messenger Bot Class.
 * @author Pablo Montenegro
 * @about Based on the Telegram API wrapper by Gabriele Grillo <gabry.grillo@alice.it>
 * TODO:
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/buy-button
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/url-button
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/image-attachment
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/sender-actions
 * https://developers.facebook.com/docs/messenger-platform/send-api-reference/errors
 */

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

namespace Werules\Chatbot\Model\Api;

class Messenger extends \Magento\Framework\Model\AbstractModel {

    private $bot_token;
    private $api_version = "v2.11";
    private $data;

    /// Class constructor
    public function __construct($bot_token) {
        $this->bot_token = $bot_token;
        $this->data = $this->getPostData();
    }

    /// Verify webhook
    public function verifyWebhook($hub_token) {
        if (isset($this->data['hub_verify_token']))
        {
            if ($this->data['hub_verify_token'] == $hub_token) {
                if (isset($this->data['hub_challenge']))
                    return $this->data['hub_challenge'];
            }
        }

        return '';
    }

    /// Do requests to Messenger Bot API
    public function endpoint($api, array $content, $post = true) {
        $url = 'https://graph.facebook.com/' . $this->api_version . '/' . $api . '?access_token=' . $this->bot_token;
        if ($post)
            $reply = $this->sendAPIRequest($url, $content);
        else
            $reply = $this->sendAPIRequest($url, array(), false);
        return json_decode($reply, true);
    }

    public function respondSuccess() {
        http_response_code(200);
        return json_encode(array("status" => "success"));
    }

    // send chat action
    // sender_action:
    // mark_seen
    // typing_on
    // typing_off
    public function sendChatAction($chat_id, $action) {
        return $this->endpoint("me/messages", array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'sender_action' => $action
            )
        );
    }

    // send message
//        https://developers.facebook.com/docs/messenger-platform/send-api-reference#request
    public function sendMessage($chat_id, $text) {
        return $this->endpoint("me/messages", array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'message' => array(
                    'text' => $text
                )
            )
        );
    }

//        sendGenericTemplate
//        $button = array(
//            array(
//                    'type' => 'web_url',
//                    'url' => 'URL_HERE',
//                    'title' => 'TITLE_HERE'
//                ),
//            array(
//                'type' => 'web_url',
//                'url' => 'URL_HERE',
//                'title' => 'TITLE_HERE'
//            ),
//            ...
//        );
//        $elements = array(
//            array(
//            'title' => 'TITLE_TEXT_HERE',
//            'item_url' => 'ITEM_URL_HERE',
//            'image_url' => 'IMAGE_URL_HERE',
//            'subtitle' => 'SUBTITLE_HERE',
//            'buttons' => $buttons
//            )
//        );
//        https://developers.facebook.com/docs/messenger-platform/send-api-reference#request
    public function sendGenericTemplate($chat_id, array $elements) {
        return $this->endpoint("me/messages", array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'message' => array(
                    'attachment' => array(
                        'type' => 'template',
                        'payload' => array(
                            'template_type' => 'generic',
                            'elements' => $elements
                        )
                    )
                )
            )
        );
    }

//        https://developers.facebook.com/docs/messenger-platform/send-messages/template/list#example_request
//        $elements = array(
//            array(
//                'title' => 'Classic T-Shirt Collection',
//                'subtitle' => 'See all our colors 1',
//                'image_url' => 'https://mysite.com/image/toy_1.jpg',
//                'buttons' => array(
//                    array(
//                        'title' => 'View',
//                        'type' => 'web_url',
//                        'url' => 'https://mysite.com/'
//                    )
//                )
//            ),
//            array(
//                'title' => 'Classic White T-Shirt',
//                'subtitle' => 'See all our colors',
//                'default_action' => array(
//                    'type' => 'web_url',
//                    'url' => 'https://mysite.com/'
//                )
//            ),
//            array(
//                'title' => 'Classic Blue T-Shirt',
//                'image_url' => 'https://mysite.com/image/toy_1.jpg',
//                'subtitle' => '100% Cotton, 200% Comfortable',
//                'default_action' => array(
//                    'type' => 'web_url',
//                    'url' => 'https://mysite.com/'
//                ),
//                'buttons' => array(
//                    array(
//                        'title' => 'Shop Now',
//                        'type' => 'web_url',
//                        'url' => 'https://mysite.com/'
//                    )
//                )
//            )
//        );
//        $buttons = array(
//            array(
//                'title' => 'View More',
//                'type' => 'postback',
//                'payload' => 'payload',
//            )

//        );

    public function sendListTemplate($chat_id, array $elements, array $buttons = array(), $top_element = 'compact') {
        return $this->endpoint("me/messages", array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'message' => array(
                    'attachment' => array(
                        'type' => 'template',
                        'payload' => array(
                            'template_type' => 'list',
                            'top_element_style' => $top_element,
                            'elements' => $elements,
                            'buttons' => $buttons
                        )
                    )
                )
            )
        );
    }

    // send quick reply
//        $replies = array(
//            array(
//                    'content_type' => 'text',
//                    'title' => 'TITLE_HERE',
//                    'payload' => 'DEVELOPER_CUSTOM_PAYLOAD_HERE'
//                ),
//            array(
//                'content_type' => 'text',
//                'title' => 'TITLE_HERE',
//                'payload' => 'DEVELOPER_CUSTOM_PAYLOAD_HERE'
//            ),
//            ...
//        );
//        https://developers.facebook.com/docs/messenger-platform/send-api-reference/quick-replies
    public function sendQuickReply($chat_id, $text, array $replies) {
        return $this->endpoint("me/messages", array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'message' => array(
                    'text' => $text,
                    'quick_replies' => $replies
                )
            )
        );
    }

    // send button
//        $buttons = array(
//            array(
//                    'type' => 'web_url',
//                    'url' => 'URL_HERE',
//                    'title' => 'TITLE_HERE'
//                ),
//            array(
//                'type' => 'web_url',
//                'url' => 'URL_HERE',
//                'title' => 'TITLE_HERE'
//            ),
//            ...
//        );
//        https://developers.facebook.com/docs/messenger-platform/send-api-reference/button-template
//        https://developers.facebook.com/docs/messenger-platform/send-api-reference/share-button <- works only with sendGenericTemplate
    public function sendButtonTemplate($chat_id, $text, array $buttons) {
        return $this->endpoint("me/messages",
            array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'message' => array(
                    'attachment' => array(
                        'type' => 'template',
                        'payload' => array(
                            'template_type' => 'button',
                            'text' => $text,
                            'buttons' => $buttons
                        )
                    )
                )
            )
        );
    }

    // send elements
//        $elements = array(
//            array(
//            'title' => 'TITLE_TEXT_HERE',
//            'item_url' => 'ITEM_URL_HERE',
//            'image_url' => 'IMAGE_URL_HERE',
//            'subtitle' => 'SUBTITLE_HERE',
//            'buttons' => $buttons
//            )
//        );
//        https://developers.facebook.com/docs/messenger-platform/send-api-reference/receipt-template
    public function sendReceiptTemplate($chat_id, array $payload) {
        return $this->endpoint("me/messages",
            array(
                'recipient' => array(
                    'id' => $chat_id
                ),
                'message' => array(
                    'attachment' => array(
                        'type' => 'template',
                        'payload' => $payload
                    )
                )
            )
        );
    }

//    $whitelist = array(
//        'www.github.com',
//        'www.facebook.com'
//    )
    public function addDomainsToWhitelist(array $whitelist) {
        return $this->endpoint("me/thread_settings",
            array(
                'setting_type' => 'domain_whitelisting',
                'whitelisted_domains' => $whitelist,
                'domain_action_type' => 'add'
            )
        );
    }

    public function getPageDetails() {
        return $this->endpoint(
            "me",
            array(),
            false
        );
    }

    /// Get the text of the current message
    public function Text() {
        if (isset($this->data["entry"][0]["messaging"][0]["message"]["text"]))
            return $this->data["entry"][0]["messaging"][0]["message"]["text"];

        return '';
    }

    /// Get the userdata who sent the message
    public function UserData($chat_id) {
        return $this->endpoint($chat_id, array(), false);
    }

    /// Get the chat_id of the current message
    public function ChatID() {
        if (isset($this->data["entry"][0]['messaging'][0]['sender']['id']))
            return $this->data['entry'][0]['messaging'][0]['sender']['id'];

        return '';
    }

    /// Get the recipient_id of the current message
    public function RecipientID() {
        if (isset($this->data["entry"][0]['messaging'][0]['recipient']['id']))
            return $this->data['entry'][0]['messaging'][0]['recipient']['id'];

        return '';
    }

    /// Get m.me ref type
    public function getReferralType() {
        if (isset($this->data["entry"][0]["messaging"][0]["referral"]["type"]))
            return $this->data["entry"][0]["messaging"][0]["referral"]["type"];

        return '';
    }

    /// Get m.me ref data
    public function getReferralRef() {
        if (isset($this->data["entry"][0]["messaging"][0]["referral"]["ref"]))
            return $this->data["entry"][0]["messaging"][0]["referral"]["ref"];

        return '';
    }

    /// Get postback payload
    public function getPostbackPayload() {
        if (isset($this->data["entry"][0]["messaging"][0]["postback"]["payload"]))
            return $this->data["entry"][0]["messaging"][0]["postback"]["payload"];

        return '';
    }

    /// Get postback title
    public function getPostbackTitle() {
        if (isset($this->data["entry"][0]["messaging"][0]["postback"]["title"]))
            return $this->data["entry"][0]["messaging"][0]["postback"]["title"];

        return '';
    }

    /// Get quickreply payload
    public function getQuickReplyPayload() {
        if (isset($this->data["entry"][0]["messaging"][0]["message"]["quick_reply"]["payload"]))
            return $this->data["entry"][0]["messaging"][0]["message"]["quick_reply"]["payload"];

        return '';
    }

    /// Get message timestamp
    public function getMessageTimestamp() {
        if (isset($this->data["entry"][0]["time"]))
            return $this->data["entry"][0]["time"];

        return '';
    }

    /// Get the message_id of the current message
    public function MessageID() {
        if (isset($this->data["entry"][0]["messaging"][0]["message"]["mid"]))
            return $this->data["entry"][0]["messaging"][0]["message"]["mid"];

        return '';
    }

    /// Get the is_echo of the current message
    public function getEcho() {
        if (isset($this->data["entry"][0]["messaging"][0]["message"]["is_echo"]))
            return $this->data["entry"][0]["messaging"][0]["message"]["is_echo"];

        return '';
    }

    /// Get the app_id of the current message
    public function getAppId() {
        if (isset($this->data["entry"][0]["messaging"][0]["message"]["app_id"]))
            return $this->data["entry"][0]["messaging"][0]["message"]["app_id"];

        return '';
    }

    private function sendAPIRequest($url, array $content, $post = true, $response = true) {
        $ch = curl_init($url);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if ($response)
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /// Get the data of the current message
    public function getPostData() {
        if (!($this->data)) {
            $rawData = file_get_contents("php://input");
            if ($rawData)
                return json_decode($rawData, true);
            else
                return $_REQUEST;
        } else {
            return $this->data;
        }
    }
}

// Helper for Uploading file using CURL
if (!function_exists('curl_file_create')) {

    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
            . ($postname ? : basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}
