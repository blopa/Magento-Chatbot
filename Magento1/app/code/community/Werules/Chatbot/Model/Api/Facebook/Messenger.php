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
	class Messenger {

		private $bot_id = "";
		private $api_version = "v2.9";
		private $data = array();
		private $updates = array();

		/// Class constructor
		public function __construct($bot_id) {
			$this->bot_id = $bot_id;
			$this->data = $this->getData();
		}

		/// Verify webhook
		public function verifyWebhook($hub_token) {
			if ($_REQUEST['hub_verify_token'] == $hub_token) {
				return $_REQUEST['hub_challenge'];
			}
			return false;
		}

		/// Do requests to Messenger Bot API
		public function endpoint($api, array $content, $post = true) {
			$url = 'https://graph.facebook.com/' . $this->api_version . '/' . $api . '?access_token=' . $this->bot_id;
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
//		https://developers.facebook.com/docs/messenger-platform/send-api-reference#request
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

		// send message
//		$button = array(
//			array(
//					'type' => 'web_url',
//					'url' => 'URL_HERE',
//					'title' => 'TITLE_HERE'
//				),
//			array(
//				'type' => 'web_url',
//				'url' => 'URL_HERE',
//				'title' => 'TITLE_HERE'
//			),
//			...
//		);
//		$elements = array(
//			array(
//			'title' => 'TITLE_TEXT_HERE',
//			'item_url' => 'ITEM_URL_HERE',
//			'image_url' => 'IMAGE_URL_HERE',
//			'subtitle' => 'SUBTITLE_HERE',
//			'buttons' => $buttons
//			)
//		);
//		https://developers.facebook.com/docs/messenger-platform/send-api-reference#request
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

		// send quick reply
//		$replies = array(
//			array(
//					'content_type' => 'text',
//					'title' => 'TITLE_HERE',
//					'payload' => 'DEVELOPER_CUSTOM_PAYLOAD_HERE'
//				),
//			array(
//				'content_type' => 'text',
//				'title' => 'TITLE_HERE',
//				'payload' => 'DEVELOPER_CUSTOM_PAYLOAD_HERE'
//			),
//			...
//		);
//		https://developers.facebook.com/docs/messenger-platform/send-api-reference/quick-replies
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
//		$buttons = array(
//			array(
//					'type' => 'web_url',
//					'url' => 'URL_HERE',
//					'title' => 'TITLE_HERE'
//				),
//			array(
//				'type' => 'web_url',
//				'url' => 'URL_HERE',
//				'title' => 'TITLE_HERE'
//			),
//			...
//		);
//		https://developers.facebook.com/docs/messenger-platform/send-api-reference/button-template
//		https://developers.facebook.com/docs/messenger-platform/send-api-reference/share-button <- works only with sendGenericTemplate
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

		/// Get the text of the current message
		public function Text() {
			return $this->data["entry"][0]["messaging"][0]["message"]["text"];
		}

		/// Get the userdata who sent the message
		public function UserData($chat_id) {
			return $this->endpoint($chat_id, array(), false);
		}

		/// Get the chat_id of the current message
		public function ChatID() {
			return $this->data['entry'][0]['messaging'][0]['sender']['id'];
		}

		/// Get the recipient_id of the current message
		public function RecipientID() {
			return $this->data['entry'][0]['messaging'][0]['recipient']['id'];
		}

		/// Get raw data
		public function RawData() {
			return $this->data;
		}

		/// Get payload
		public function getPayload() {
			return $this->data["entry"][0]["messaging"][0]["postback"]["payload"];
		}

		/// Get message timestamp
		public function getMessageTimestamp() {
			return $this->data["entry"][0]["time"];
		}

		/// Get the message_id of the current message
		public function MessageID() {
			return $this->data["entry"][0]["messaging"][0]["message"]["mid"];
		}

		/// Get the is_echo of the current message
		public function getEcho() {
			return $this->data["entry"][0]["messaging"][0]["message"]["is_echo"];
		}

		/// Get the app_id of the current message
		public function getAppId() {
			return $this->data["entry"][0]["messaging"][0]["message"]["app_id"];
		}

		private function sendAPIRequest($url, array $content, $post = true, $response = true) {
			$ch = curl_init($url);
			if ($post) {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			if ($response)
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		}

		/// Get the data of the current message
		public function getData() {
			if (empty($this->data)) {
				$rawData = file_get_contents("php://input");
				return json_decode($rawData, true);
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
?>
