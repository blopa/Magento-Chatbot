<?php
require_once("Api/Facebook/MessengerBot.php");
require_once("Api/Telegram/TelegramBot.php");

class Werules_Chatbot_Model_Cron
{
	public function crontask()
	{
		//Mage::log("Cron processed", null, 'chatbot_cron.log');

		$chatbotCollection = Mage::getModel('chatbot/chatdata')->getCollection();
		foreach($chatbotCollection as $chatbot)
		{
			//$hasQuote = $chatbot->getSessionId() && $chatbot->getQuoteId(); // has class quote and session ids
			$enabled = // if backend promotional messages are disabled or if the customer wants to receive promotional messages
				(Mage::getStoreConfig('chatbot_enable/general_config/disable_promotional_messages') != "1") ||
				($chatbot->getEnablePromotionalMessages() == "1");
			$customer = Mage::getModel('customer/customer')->load((int)$chatbot->getCustomerId());
			if (($customer->getId()) && ($enabled)) // if is a valid customer id
			{
				$fbChatId = $chatbot->getFacebookChatId();
				$tgChatId = $chatbot->getTelegramChatId();
				$customerId = $customer->getId();
				$lifetime = (int)Mage::getStoreConfig('chatbot_enable/general_config/abandoned_cart_days');
				//$lifetime = 7;
				$quotes = Mage::getModel( 'sales/quote' )->getCollection();
				$quotes->addFieldToFilter('customer_id', $customerId)
						//->addFieldToFilter('is_active', 1)
						->addFieldToFilter('updated_at', array('to' => date("Y-m-d", time() - ($lifetime * 86400))))
					;
				if (count($quotes) > 0) // TODO
				{
					$enableFb = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_abandoned_cart');
					$enableTg = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_abandoned_cart');

					if ($enableFb == "1") // messenger
					{
						$message = Mage::getStoreConfig('chatbot_enable/facebook_config/abandoned_cart_msg');
						if ($message != "")
						{
							$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
							$facebook = new MessengerBot($apikey);
							$facebook->postMessage($fbChatId, $message);
						}
					}
					if ($enableTg == "1") // telegram
					{
						$message = Mage::getStoreConfig('chatbot_enable/telegram_config/abandoned_cart_msg');
						if ($message != "")
						{
							$apikey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
							$facebook = new TelegramBot($apikey);
							$facebook->postMessage($tgChatId, $message);
						}
					}
				}
//				foreach($quotes as $quote)
//				{
//					$orders = Mage::getModel('sales/order')->getCollection();
//					$orders->addFieldToFilter('quote_id', $quote->getId());
//					$items = Mage::getModel('sales/quote_item')
//						->getCollection()
//						->setQuote($quote);
//				}
			}
		}
	}
}
