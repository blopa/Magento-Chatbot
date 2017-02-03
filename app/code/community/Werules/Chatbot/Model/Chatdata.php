<?php
	include("Api/Telegram/Telegram.php");

	class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		public $tg_bot = "telegram";
		public $fb_bot = "facebook";

		// CONVERSATION STATES
		public $start_state = 0;
		public $list_cat_state = 1;
		public $list_prod_state = 2;
		public $search_state = 3;
		public $login_state = 4;
		public $list_orders_state = 5;
		public $reorder_state = 6;
		public $add2cart_state = 7;
		public $show_cart_state = 8;
		public $checkout_state = 9;
		public $track_order_state = 10;
		public $support_state = 11;
		public $send_email_state = 12;

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

		public function getState($chat_id, $apiType)
		{
			if ($apiType == $this->tg_bot) // telegram api
			{
				$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'telegram_chat_id');
				return $chatdata->getTelegramConvState();
			}
			else if ($apiType == $this->fb_bot)
			{
				return "error 101"; // TODO
			}
		}

		public function setState($chat_id, $apiType, $state)
		{
			if ($apiType == $this->tg_bot) // telegram api
			{
				$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'telegram_chat_id');
				if ($chatdata->getTelegramChatId()) // TODO
				{
					$data = array(
						//'customer_id' => $customerId,
						'telegram_conv_state' => $state
					); // data to be insert on database
					$chatdata->addData($data);
					$chatdata->save();
				}
			}
			else if ($apiType == $this->fb_bot)
			{
				return "error 101"; // TODO
			}
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
				if ($this->getState($chat_id, $this->tg_bot) == $this->list_cat_state)
				{
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => var_export($_category, true)));

					$productIDs = $_category->getProductCollection()->getAllIds();
					foreach ($productIDs as $productID)
					{
						$_product = Mage::getModel('catalog/product')->load($productID);
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $_product->getName()));
					}
				}
				// commands
				if ($text == "/start")
				{
					// started the bot for the first time
					$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'telegram_chat_id');
					if ($chatdata->getTelegramChatId()) // TODO
					{
						$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_help_msg'); // TODO
						$content = array('chat_id' => $chat_id, 'text' => $message);
						$telegram->sendMessage($content);

//						$data = array(
//							//'customer_id' => $customerId,
//							'telegram_chat_id' => $chat_id
//						); // data to be insert on database
//						$model = Mage::getModel('chatbot/chatdata')->load($chatdata->getId())->addData($data); // insert data on database
//						$model->setId($chatdata->getId())->save(); // save (duh)
					}
					else // if customer id isnt on our database, means that we need to insert his data
					{
						$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg'); // TODO
						$content = array('chat_id' => $chat_id, 'text' => $message);
						$telegram->sendMessage($content);
						try
						{
							$hash = substr(md5(uniqid($chat_id, true)), 0, 150);
							Mage::getModel('chatbot/chatdata') // using magento model to insert data into database the proper way
								->setTelegramChatId($chat_id)
								->setHashKey($hash) // TODO
								->save();
						}
						catch (Exception $e)
						{
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Something went wrong, try again.")); // TODO
						}
					}
				}
				else if ($text == "/list_cat")
				{
					$this->setState($chat_id, $this->tg_bot, $this->list_cat_state);
					$helper = Mage::helper('catalog/category');
					$categories = $helper->getStoreCategories();
					$option = array();
					foreach ($categories as $_category) // TODO fix max size
					{
						array_push($option, $_category->getName());
					}

					$keyb = $telegram->buildKeyBoard(array($option));
					$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Pick a Category");
					$telegram->sendMessage($content);
				}
				else if ($text == "/list_prod")
				{
					$this->setState($chat_id, $this->tg_bot, $this->list_prod_state);
				}
				else if ($text == "/search")
				{
					$this->setState($chat_id, $this->tg_bot, $this->search_state);
				}
				else if ($text == "/login")
				{
					$this->setState($chat_id, $this->tg_bot, $this->login_state);
				}
				else if ($text == "/list_orders")
				{
					$this->setState($chat_id, $this->tg_bot, $this->list_orders_state);
				}
				else if ($text == "/reorder")
				{
					$this->setState($chat_id, $this->tg_bot, $this->reorder_state);
				}
				else if ($text == "/add2cart")
				{
					$this->setState($chat_id, $this->tg_bot, $this->add2cart_state);
				}
				else if ($text == "/show_cart")
				{
					$this->setState($chat_id, $this->tg_bot, $this->show_cart_state);
				}
				else if ($text == "/checkout")
				{
					$this->setState($chat_id, $this->tg_bot, $this->checkout_state);
				}
				else if ($text == "/track_order")
				{
					$this->setState($chat_id, $this->tg_bot, $this->track_order_state);
				}
				else if ($text == "/support")
				{
					$this->setState($chat_id, $this->tg_bot, $this->support_state);
				}
				else if ($text == "/send_email")
				{
					$this->setState($chat_id, $this->tg_bot, $this->send_email_state);
				}
				else return "error 101"; // TODO
			}
		}

		public function handleFacebook()
		{

		}
	}