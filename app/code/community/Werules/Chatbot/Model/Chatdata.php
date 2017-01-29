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

		public function checkEnabled($apiType) // check if bot integration is enabled
		{
			if ($apiType == $this->tg_bot) // telegram api
			{
				$enabled = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
				if ($enabled != 1) // is disabled
					return false;
			}
			else if ($apiType == $this->fb_bot)
			{
				$enabled = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
				if ($enabled != 1) // is disabled
					return false;
			}

			return $apiType;
		}

		public function sendTextMessage($apiType, $text)
		{
			if ($apiType == $this->checkEnabled($this->tg_bot)) // telegram api
			{
				$enabled = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
				if ($enabled) // is enabled
				{
					$apikey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
					return $apikey . $text;
				}
			}
			else if ($apiType == $this->checkEnabled($this->fb_bot))
			{
				$enabled = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
				if ($enabled) // is enabled
				{
					$apikey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
					return $apikey . $text;
				}
			}
			else
				return 'error'; // TODO
		}
	}