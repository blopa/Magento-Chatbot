<?php
	include("Api/Telegram/Telegram.php");

	class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		public $tg_bot = "telegram";
		public $fb_bot = "facebook";

		public function _construct()
		{
			//parent::_construct();
			$this->_init('chatbot/chatdata'); // this is location of the resource file.
		}

		public function requestHandler($apiType) // handle request
		{
			$api = $this->getApikey($apiType);
			if ($apiType == $this->tg_bot && $api) // telegram api
			{
				// all logic goes here
				$this->handleTelegram($api);
			}
			else if ($apiType == $this->fb_bot && $api) // facebook api
			{
				// all logic goes here
				$this->handleFacebook($api);
			}
			else
				return "error 101"; // TODO
		}

		public function getApikey($apiType) // check if bot integration is enabled
		{
			if ($apiType == $this->tg_bot) // telegram api
			{
				$enabled = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
				$apikey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
				if ($enabled == 1 && $apikey) // is enabled and has API
					return $apikey;
			}
			else if ($apiType == $this->fb_bot)
			{
//				$enabled = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_bot');
//				$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
//				if ($enabled == 1 && $apikey) // is enabled and has API
//					return $apikey;
				return "error 101"; // TODO
			}
			return false;
		}

		public function handleTelegram($api)
		{
			// Instances the class
			$telegram = new Telegram($api);

			// Take text and chat_id from the message
			$text = $telegram->Text();
			$chat_id = $telegram->ChatID();

			if (!is_null($text) && !is_null($chat_id))
			{
				if ($text == "/start")
				{
					// started the bot for the first time
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg');
					$content = array('chat_id' => $chat_id, 'text' => $message);
					$telegram->sendMessage($content);
				}
				else if ($text == "/list_cat") {}
				else if ($text == "/list_prod") {}
				else if ($text == "/search") {}
				else if ($text == "/login") {}
				else if ($text == "/list_orders") {}
				else if ($text == "/reorder") {}
				else if ($text == "/add2cart") {}
				else if ($text == "/show_cart") {}
				else if ($text == "/checkout") {}
				else if ($text == "/track_order") {}
				else if ($text == "/support") {}
				else if ($text == "/send_email") {}
				else return "error 101"; // TODO
			}
		}

		public function handleFacebook()
		{

		}
	}