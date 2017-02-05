<?php
	include("Api/Telegram/Telegram.php");

	class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		// APIs
		private $tg_bot = "telegram";
		private $fb_bot = "facebook";
		private $wapp_bot = "whatsapp";

		// CONVERSATION STATES
		private $start_state = 0;
		private $list_cat_state = 1;
		private $list_prod_state = 2;
		private $search_state = 3;
		private $login_state = 4;
		private $list_orders_state = 5;
		private $reorder_state = 6;
		private $add2cart_state = 7;
		private $checkout_state = 9;
		private $track_order_state = 10;
		private $support_state = 11;
		private $send_email_state = 12;
		private $clear_cart_state = 13;

		// COMMANDS
		private $cmd_list = "start,list_cat,search,login,list_orders,reorder,add2cart,checkout,clear_cart,track_order,support,send_email";
		private $start_cmd = "/start";
		private $listacateg_cmd = "/list_cat";
		private $search_cmd = "/search";
		private $login_cmd = "/login";
		private $listorders_cmd = "/list_orders";
		private $reorder_cmd = "/reorder";
		private $add2cart_cmd = "/add2cart";
		private $checkout_cmd = "/checkout";
		private $clearcart_cmd = "/clear_cart";
		private $trackorder_cmd = "/track_order";
		private $support_cmd = "/support";
		private $sendemail_cmd = "/send_email";

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

		private function getApikey($apiType) // check if bot integration is enabled
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
			return null;
		}

		private function addProd2Cart($prodId) // TODO add try / except
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

		private function getCommandString($cmd)
		{

		}

		private function getCommandValue($text, $cmd)
		{
			return substr($text, strlen($cmd), strlen($text));
		}

		private function checkCommand($text, $cmd)
		{
			return substr($text, 0, strlen($cmd)) == $cmd;
		}

		private function setState($apiType, $state) // TODO add try
		{
			$data = array($apiType => $state); // data to be insert on database
			$this->addData($data);
			$this->save();
		}

		private function prepareProdMessages($productID)
		{
			$_product = Mage::getModel('catalog/product')->load($productID);
			if ($_product)
			{
				$message = $_product->getName() . "\n" .
					$this->excerpt($_product->getShortDescription(), 60) . "\n" .
					"Add To Cart: " . $this->add2cart_cmd . $_product->getId();
				return $message;
			}
			return null;
		}

		private function loadImageContent($productID)
		{
			$imagepath = Mage::getModel('catalog/product')->load($productID)->getSmallImage();
			if ($imagepath && $imagepath != "no_selection")
			{
				$absolutePath =
					Mage::getBaseDir('media') .
					"/catalog/product" .
					$imagepath;

				return curl_file_create($absolutePath, 'image/jpg');
			}
			return null;
		}

		private function excerpt($text, $size)
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

		private function getProductIdsBySearch($searchstring)
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

		private function telegramHandler($apiKey)
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
				if ($chatdata->getTelegramConvState() == $this->list_cat_state) // TODO show only in stock products
				{
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);

					if ($_category)
					{
						$productIDs = $_category->getProductCollection()->getAllIds();
						if ($productIDs)
						{
							$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories
							foreach ($productIDs as $productID)
							{
								$message = $this->prepareProdMessages($productID);
								$image = $this->loadImageContent($productID);
								if ($image)
									$telegram->sendPhoto(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'photo' => $image, 'caption' => $message));
								else
									$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $message));
							}
							$chatdata->setState('telegram_conv_state', $this->list_prod_state);
						}
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Sorry, no products found in this category."));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Something went wrong, please try again."));
					return;
				}
				else if ($chatdata->getTelegramConvState() == $this->search_state) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->start_state);
					$productIDs = $this->getProductIdsBySearch($text);
					if ($productIDs)
					{
						foreach ($productIDs as $productID)
						{
							$message = $this->prepareProdMessages($productID);
							$image = $this->loadImageContent($productID);
							if ($image)
								$telegram->sendPhoto(array('chat_id' => $chat_id, 'photo' => $image, 'caption' => $message));
							else
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
						}
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Sorry, no products found."));

					return;
				}

				// commands
				if ($text == $this->start_cmd)
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
				else if ($text == $this->listacateg_cmd)
				{
					$chatdata->setState('telegram_conv_state', $this->list_cat_state);
					$helper = Mage::helper('catalog/category');
					$categories = $helper->getStoreCategories();
					if ($categories)
					{
						$option = array();
						foreach ($categories as $_category) // TODO fix buttons max size
						{
							array_push($option, $_category->getName());
						}

						$keyb = $telegram->buildKeyBoard(array($option));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Pick a Category"));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Something went wrong, try again.")); // TODO
					return;
				}
				else if ($text == $this->checkout_cmd) // TODO
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
						$message .= "Total: " .
							Mage::helper('core')->currency($cart->getQuote()->getSubtotal(), true, false) . "\n\n" .
							"[Checkout Here](" . $cartUrl . ")";

						$chatdata->setState('telegram_conv_state', $this->checkout_state);
						$telegram->sendMessage(array('chat_id' => $chat_id, 'parse_mode' => 'Markdown', 'text' => $message));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Your cart is empty"));
					return;
				}
				else if ($text == $this->clearcart_cmd)
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
				else if ($text == $this->search_cmd)
				{
					$chatdata->setState('telegram_conv_state', $this->search_state);
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Okay, search for what?"));
					return;
				}
				else if ($text == $this->login_cmd) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->login_state);
					return;
				}
				else if ($text == $this->listorders_cmd) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->list_orders_state);
					return;
				}
				else if ($text == $this->reorder_cmd) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->reorder_state);
					return;
				}
				else if ($text == $this->trackorder_cmd) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->track_order_state);
					return;
				}
				else if ($text == $this->support_cmd) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->support_state);
					return;
				}
				else if ($text == $this->sendemail_cmd) // TODO
				{
					$chatdata->setState('telegram_conv_state', $this->send_email_state);
					return;
				}
				else return "error 101"; // TODO
			}
		}

		private function facebookHandler()
		{

		}

		private function whatsappHandler()
		{

		}
	}