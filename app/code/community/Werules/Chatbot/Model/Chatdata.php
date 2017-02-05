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
		public $checkout_state = 9;
		public $track_order_state = 10;
		public $support_state = 11;
		public $send_email_state = 12;
		public $clear_cart_state = 13;

		// COMMANDS
		public $add2cart_cmd = "/add2cart";

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
			$checkout = Mage::getSingleton('checkout/session');
			$cart = Mage::getModel("checkout/cart");
			if ($this->getSessionId() && $this->getQuoteId())
			{
				$cart->setQuote(Mage::getModel('sales/quote')->loadByIdWithoutStore((int)$this->getQuoteId()));
				$checkout->setSessionId($this->getSessionId());
			}
			$cart->addProduct($prodId);
			$cart->save();
			$checkout->setCartWasUpdated(true);

			$sessionId = $checkout->getEncryptedSessionId();
			$data = array(
				"session_id" => $sessionId,
				"quote_id" => $checkout->getQuote()->getId()
			);
			$this->addData($data);
			$this->save();
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

		public function prepareProdMessages($productID)
		{
			$_product = Mage::getModel('catalog/product')->load($productID);
			$message = $_product->getName() . "\n" .
				$this->excerpt($_product->getShortDescription(), 60) . "\n" .
				"Add To Cart: " . $this->add2cart_cmd . $_product->getId();
			return $message;
		}

		public function loadImageContent($productID)
		{
			$absolutePath =
				Mage::getBaseDir('media') .
				"/catalog/product" .
				Mage::getModel('catalog/product')->load($productID)->getSmallImage();

			return curl_file_create($absolutePath, 'image/jpg');
		}

		public function excerpt($text, $size)
		{
			if (strlen($text) > $size)
			{
				$text = substr($text, 0, $size);
				$text = substr($text, 0, strrpos($text, " "));
				$etc = " ...";
				$text = $text . $etc;
			}
			return $text;
		}

		public function getProductIdsBySearch($searchstring)
		{
			$ids = array();
			// Code to Search Product by $searchstring and get Product IDs
			$product_collection = Mage::getResourceModel('catalog/product_collection')
				->addAttributeToSelect('*')
				->addAttributeToFilter('name', array('like' => '%'.$searchstring.'%'))
				->load();

			foreach ($product_collection as $product) {
				$ids[] = $product->getId();
			}
			//return array of product ids
			return $ids;
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
				// supreme commands
				if ($chatdata->checkCommand($text, $this->add2cart_cmd)) // && $chatdata->getTelegramConvState() == $this->list_prod_state TODO
				{
					$chatdata->addProd2Cart($chatdata->getCommandValue($text, $this->add2cart_cmd));

					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Added! Send /checkout to checkout."));
					return;
				}

				// states
				if ($chatdata->getTelegramConvState() == $this->list_cat_state)
				{
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
					//$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => var_export($_category, true))); // TODO debug

					$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories
					$productIDs = $_category->getProductCollection()->getAllIds();
					foreach ($productIDs as $productID)
					{
						$message = $this->prepareProdMessages($productID);
						$image = $this->loadImageContent($productID);
						$telegram->sendPhoto(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'photo' => $image, 'caption' => $message));
					}
					$chatdata->setState('telegram_conv_state', $this->list_prod_state);
					return;
				}
				else if ($chatdata->getTelegramConvState() == $this->search_state) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->start_state);
					$productIDs = $this->getProductIdsBySearch($text);
					foreach ($productIDs as $productID)
					{
						$message = $this->prepareProdMessages($productID);
						$image = $this->loadImageContent($productID);
						$telegram->sendPhoto(array('chat_id' => $chat_id, 'photo' => $image, 'caption' => $message));
					}
					return;
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
					return;
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
					return;
				}
				else if ($text == "/checkout") // TODO
				{
					$sessionId = $chatdata->getSessionId();
					$quoteId = $chatdata->getQuoteId();
					if ($sessionId && $quoteId)
					{
						$cartUrl = Mage::helper('checkout/cart')->getCartUrl();
						if (!isset(parse_url($cartUrl)['SID']))
							$cartUrl .= "?SID=" . $sessionId; // add session id to url

						$cart = Mage::getModel('checkout/cart')->setQuote(Mage::getModel('sales/quote')->loadByIdWithoutStore((int)$quoteId));
						$message = "Products on cart:\n";
						foreach ($cart->getQuote()->getItemsCollection() as $item) // TODO
						{
							$message .= $item->getQty() . "x " . $item->getProduct()->getName() . "\n" .
								"Price: " . Mage::helper('core')->currency($item->getProduct()->getPrice(), true, false) . "\n\n";
						}
						$message .= "Total: " . Mage::helper('core')->currency($cart->getQuote()->getSubtotal(), true, false);

						$chatdata->setState('telegram_conv_state', $this->checkout_state);
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $cartUrl));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Your cart is empty"));
					return;
				}
				else if ($text == "/clear_cart")
				{
					$data = array(
						"session_id" => "",
						"quote_id" => ""
					);
					$chatdata->addData($data);
					$chatdata->save();

					$chatdata->setState('telegram_conv_state', $this->clear_cart_state);
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Cart cleared!"));
					return;
				}
				else if ($text == "/search")
				{
					$chatdata->setState('telegram_conv_state', $this->search_state);
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Okay, search for what?"));
					return;
				}
				else if ($text == "/login") // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->login_state);
					return;
				}
				else if ($text == "/list_orders") // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->list_orders_state);
					return;
				}
				else if ($text == "/reorder") // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->reorder_state);
					return;
				}
				else if ($text == "/track_order") // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->track_order_state);
					return;
				}
				else if ($text == "/support") // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->support_state);
					return;
				}
				else if ($text == "/send_email") // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->send_email_state);
					return;
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