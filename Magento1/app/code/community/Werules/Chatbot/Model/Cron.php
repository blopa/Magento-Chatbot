<?php
require_once("Api/Facebook/MessengerBot.php");

class Werules_Chatbot_Model_Cron
{
	public function crontask()
	{
		$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
		$facebook = new MessengerBot($apikey);

		$facebook->postMessage('1253214391436892', "Isso eh um teste");
	}
}
