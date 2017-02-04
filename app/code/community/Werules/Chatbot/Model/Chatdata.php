<?php
	include("Api/Telegram/Telegram.php");

	class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		// APIs
		public $tg_bot = "telegram";
		public $fb_bot = "facebook";
		public $wapp_bot = "whatsapp";

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

		// COMMANDS
		public $add2card_cmd = "/add2cart";

		public function _construct()
		{
			//parent::_construct();
			$this->_init('chatbot/chatdata'); // this is location of the resource file.
		}

		public function requestHandler($apiType) // handle request
		{
			$apiKey = $this->getApikey($apiType);
			if ($apiType == $this->tg_bot && $apiKey) // telegram api
			{
				// all logic goes here
				$this->telegramHandler($apiKey);
			}
			else if ($apiType == $this->fb_bot && $apiKey) // facebook api
			{
				// all logic goes here
				$this->facebookHandler($apiKey);
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

		public function addProd2Cart($prodId) // TODO add try / except
		{
			// TODO get user session if exists
			$product = Mage::getModel('catalog/product')->load($prodId);
			$cart = Mage::getModel('checkout/cart');
			$cart->init();
			$cart->addProduct($product, array('qty' => '1'));
			$cart->save();
			Mage::getSingleton('checkout/session')->setCartWasUpdated(true);

			$session = Mage::getSingleton('core/session');
			return $session->getEncryptedSessionId();
		}

		public function getCommandValue($text, $cmd)
		{
			return substr($text, strlen($cmd), strlen($text));
		}

		public function checkCommand($text, $cmd)
		{
			return substr($text, 0, strlen($cmd)) == $cmd;
		}

		public function setState($apiType, $state)
		{
			$data = array($apiType => $state); // data to be insert on database
			$this->addData($data);
			$this->save();
		}

		public function telegramHandler($apiKey)
		{
			// Instances the Telegram class
			$telegram = new Telegram($apiKey);

			// Take text and chat_id from the message
			$text = $telegram->Text();
			$chat_id = $telegram->ChatID();

			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'telegram_chat_id');

			if (!is_null($text) && !is_null($chat_id))
			{
				// states
				if ($chatdata->getTelegramConvState() == $this->list_cat_state)
				{
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
					//$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => var_export($_category, true))); // TODO debug
					$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories

					$productIDs = $_category->getProductCollection()->getAllIds();
					foreach ($productIDs as $productID)
					{
						$_product = Mage::getModel('catalog/product')->load($productID);
						$message = $_product->getName() . "\n/add2cart" . $_product->getId(); // TODO
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $message));
					}
					$chatdata->setState('telegram_conv_state', $this->list_prod_state);
				}
				else if ($chatdata->getTelegramConvState() == $this->list_prod_state && $chatdata->checkCommand($text, $this->add2card_cmd)) // TODO
				{
					$sessionId = $chatdata->addProd2Cart($chatdata->getCommandValue($text, $this->add2card_cmd));
					$sessionUrl = Mage::getBaseUrl();
					if (!isset(parse_url($sessionUrl)['SID']))
						$sessionUrl .= "?SID=" . $sessionId; // add session id to url

					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $sessionUrl));
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
					$chatdata->setState('telegram_conv_state', $this->list_cat_state);
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
					$chatdata->setState('telegram_conv_state', $this->list_prod_state);
				}
				else if ($text == "/search")
				{
					$chatdata->setState('telegram_conv_state', $this->search_state);
				}
				else if ($text == "/login")
				{
					$chatdata->setState('telegram_conv_state', $this->login_state);
				}
				else if ($text == "/list_orders")
				{
					$chatdata->setState('telegram_conv_state', $this->list_orders_state);
				}
				else if ($text == "/reorder")
				{
					$chatdata->setState('telegram_conv_state', $this->reorder_state);
				}
				else if ($text == "/add2cart")
				{
					$chatdata->setState('telegram_conv_state', $this->add2cart_state);
				}
				else if ($text == "/show_cart")
				{
					$chatdata->setState('telegram_conv_state', $this->show_cart_state);
				}
				else if ($text == "/checkout")
				{
					$chatdata->setState('telegram_conv_state', $this->checkout_state);
				}
				else if ($text == "/track_order")
				{
					$chatdata->setState('telegram_conv_state', $this->track_order_state);
				}
				else if ($text == "/support")
				{
					$chatdata->setState('telegram_conv_state', $this->support_state);
				}
				else if ($text == "/send_email")
				{
					$chatdata->setState('telegram_conv_state', $this->send_email_state);
				}
				else return "error 101"; // TODO
			}
		}

		public function facebookHandler()
		{

		}

		public function whatsappHandler()
		{

		}
	}