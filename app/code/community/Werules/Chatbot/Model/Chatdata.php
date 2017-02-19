<?php
	include("Api/Telegram/Telegram.php");
	include("Api/Facebook/Messenger.php");

	class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		// APIs
		private $api_type = "";
		private $tg_bot = "telegram";
		private $fb_bot = "facebook";
		private $wapp_bot = "whatsapp";
		private $wechat_bot = "wechat";

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
		private $cmd_list = "start,list_cat,search,login,list_orders,reorder,add2cart,checkout,clear_cart,track_order,support,send_email,cancel,help,about";
		private $start_cmd = "";
		private $listacateg_cmd = "";
		private $search_cmd = "";
		private $login_cmd = "";
		private $listorders_cmd = "";
		private $reorder_cmd = "";
		private $add2cart_cmd = "";
		private $checkout_cmd = "";
		private $clearcart_cmd = "";
		private $trackorder_cmd = "";
		private $support_cmd = "";
		private $sendemail_cmd = "";
		private $cancel_cmd = "";
		private $help_cmd = "";
		private $about_cmd = "";

		// REGEX
		private $unallowed_characters = "/[^A-Za-z0-9 _]/";
		
		// DEFAULT MESSAGES
		private $errormsg = "";
		private $cancelmsg = "";
		private $canceledmsg = "";
		private $loginfirstmsg = "";
		private $positivemsg = array();

		// URLS
		private $tg_url = "https://t.me/";
		private $fb_url = "https://m.me/";
//		private $wapp_url = "";
//		private $wechat_url = "";

		public function _construct()
		{
			//parent::_construct();
			$this->_init('chatbot/chatdata'); // this is location of the resource file.
		}

		// GENERAL FUNCTIONS
		public function requestHandler($action, $webhook) // handle request
		{
			$apiKey = $this->getApikey($action);
			// handle webhook configuration
			if ($webhook && $apiKey && $action == $this->tg_bot) // set telegram webhook
			{
				try
				{
					$telegram = new Telegram($apiKey);
					$telegram->setWebhook(Mage::getUrl('chatbot/chatdata/', array('_forced_secure' => true)) . $this->tg_bot);
				}
				catch (Exception $e)
				{
					return Mage::helper('core')->__("Something went wrong, please try again.");
				}

				return Mage::helper('core')->__("Webhook for Telegram configured.");
			} // start to handle conversation
			else if ($action == $this->tg_bot && $apiKey) // telegram api
			{
				// all logic goes here
				return $this->telegramHandler($apiKey);
			}
			else if ($action == $this->fb_bot && $apiKey) // facebook api
			{
				// all logic goes here
				return $this->facebookHandler($apiKey);
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
				$enabled = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_bot');
				$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
				if ($enabled == 1 && $apikey) // is enabled and has API
					return $apikey;
			}
			return null;
		}

		private function sendEmail($text)
		{
			$storename = Mage::app()->getStore()->getName();
			$storeemail = Mage::getStoreConfig('trans_email/ident_general/email');// TODO
			$magehelper = Mage::helper('core');

			$url = $magehelper->__("Not informed");
			$customer_email = $magehelper->__("Not informed");
			$customer_name = $magehelper->__("Not informed");

			$mail = new Zend_Mail('UTF-8');

			if ($this->api_type == $this->tg_bot)
			{
				$url = $this->tg_url . $this->getTelegramChatId();
				if ($this->getCustomerId())
				{
					$customer = Mage::getModel('customer/customer')->load((int)$this->getCustomerId());
					if ($customer->getId())
					{
						$customer_email = $customer->getEmail();
						$customer_name = $customer->getName();
						$mail->setReplyTo($customer_email);
					}
				}
			}
			else if ($this->api_type == $this->fb_bot)
			{
				// code here etc
			}

			$email_body =
				$magehelper->__("Message from chatbot customer") . "<br><br>" .
				$magehelper->__("Customer name") . ": " .
				$customer_name . "<br>" .
				$magehelper->__("Message") . ":<br>" .
				$text . "<br><br>" .
				$magehelper->__("Contacts") . ":<br>" .
				$magehelper->__("Chatbot") . ": " . $url . "<br>" .
				$magehelper->__("Email") . ": " . $customer_email . "<br>";

			$mail->setBodyHtml($email_body);
			$mail->setFrom($storeemail, $storename);
			$mail->addTo($storeemail, $storename);
			$mail->setSubject(Mage::helper('core')->__("Contact from chatbot"));

			try
			{
				$mail->send();
				return true;
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		private function addProd2Cart($prodId) // TODO add expiration date for sessions
		{
			$stock = Mage::getModel('cataloginventory/stock_item')
				->loadByProduct($prodId)
				->getIsInStock();
			if ($stock <= 0) // if not in stock
				return false;
			$checkout = Mage::getSingleton('checkout/session');
			$cart = Mage::getModel("checkout/cart");
			try
			{
				$hasquote = $this->getSessionId() && $this->getQuoteId(); // has class quote and session ids
				if ($this->getIsLogged() == "1")
				{
					$customer = Mage::getModel('customer/customer')->load((int)$this->getCustomerId());
					if ($customer->getId())
					{
						// if user is set as logged, then login using magento singleton
						Mage::getSingleton('customer/session')->loginById($this->getCustomerId());
						if (!$hasquote)
						{ // if class still dosen't have quote and session ids, init here
							// set current quote as customer quote
							$quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
							$cart->setQuote($quote);
							// attach checkout session to logged customer
							$checkout->setCustomer($customer);
							//$checkout->setSessionId($customersssion->getEncryptedSessionId());
							//$quote = $checkout->getQuote();
							//$quote->setCustomer($customer);
						}
					}
				}
				if ($hasquote)
				{
					// init quote and session from chatbot class
					$cart->setQuote(Mage::getModel('sales/quote')->loadByIdWithoutStore((int)$this->getQuoteId()));
					$checkout->setSessionId($this->getSessionId());
				}
				// add product and save cart
				$cart->addProduct($prodId);
				$cart->save();
				$checkout->setCartWasUpdated(true);

				// update chatdata class data with quote and session ids
				$data = array(
					"session_id" => $checkout->getEncryptedSessionId(),
					"quote_id" => $checkout->getQuote()->getId()
				);
				$this->addData($data);
				$this->save();
			}
			catch (Exception $e)
			{
				return false;
			}

			return true;
		}

		private function getCommandString($cmdId)
		{
			if ($this->api_type == $this->tg_bot)
				$confpath = 'chatbot_enable/telegram_config/';
			else if ($this->api_type == $this->fb_bot)
				$confpath = 'chatbot_enable/facebook_config/';
//			else if ($this->api_type == $this->wapp_bot)
//				$confpath = 'chatbot_enable/whatsapp_config/';

			$config = Mage::getStoreConfig($confpath . 'enabled_commands');
			$enabledCmds = explode(',', $config);
			if (in_array($cmdId, $enabledCmds))
			{
				$config = Mage::getStoreConfig($confpath . 'commands_code');
				$commands = explode("\n", $config); // command codes split by linebreak
				$defaultCmds = explode(',', $this->cmd_list);
				if (count($commands) < count($defaultCmds))
				{
					return preg_replace( // remove all non-alphanumerics
						$this->unallowed_characters,
						'',
						str_replace( // replace whitespace for underscore
							' ',
							'_',
							trim(
								array_merge( // merge arrays
									$commands,
									array_slice(
										$defaultCmds,
										count($commands)
									)
								)[$cmdId - 1]
							)
						)
					);
				}
				return preg_replace( // remove all non-alphanumerics
					$this->unallowed_characters,
					'',
					str_replace( // replace whitespace for underscore
						' ',
						'_',
						trim(
							$commands[$cmdId - 1]
						)
					)
				);
			}
			return "";
		}

		private function getCommandValue($text, $cmd)
		{
			if (strlen($text) > strlen($cmd))
				return substr($text, strlen($cmd), strlen($text));
			return null;
		}

		private function checkCommand($text, $cmd)
		{
			if ($cmd)
				return substr($text, 0, strlen($cmd)) == $cmd;
			return false;
		}

		private function clearCart()
		{
			try
			{
				if ($this->getIsLogged() == "1")
				{
					if (Mage::getModel('customer/customer')->load((int)$this->getCustomerId())->getId())
					{
						// if user is set as logged, then login using magento singleton
						//Mage::getSingleton('customer/session')->loginById($this->getCustomerId());
						// load quote from logged user and delete it
						Mage::getModel('sales/quote')->loadByCustomer((int)$this->getCustomerId())->delete();
						// clear checout session quote
						//Mage::getSingleton('checkout/session')->setQuoteId(null);
						//Mage::getSingleton('checkout/cart')->truncate()->save();
					}
				}
				$data = array(
					"session_id" => "",
					"quote_id" => ""
				);
				$this->addData($data);
				$this->save();
			}
			catch (Exception $e)
			{
				return false;
			}

			return true;
		}

		public function updateChatdata($datatype, $state)
		{
			try
			{
				$data = array($datatype => $state); // data to be insert on database
				$this->addData($data);
				$this->save();
			}
			catch (Exception $e)
			{
				return false;
			}

			return true;
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

		private function getOrdersIdsFromCustomer()
		{
			$ids = array();
			$orders = Mage::getResourceModel('sales/order_collection')
				->addFieldToSelect('*')
				->addFieldToFilter('customer_id', $this->getCustomerId()) // not a problem if customer dosen't exist
				->setOrder('created_at', 'desc');
			foreach ($orders as $_order)
			{
				array_push($ids, $_order->getId());
			}
			if ($ids)
				return $ids;
			return false;
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

		// TELEGRAM FUNCTIONS
		private function validateTelegramCmd($cmd)
		{
			if ($cmd == "/")
				return null;
			return $cmd;
		}

		private function prepareTelegramOrderMessages($orderID) // TODO add link to product name
		{
			$order = Mage::getModel('sales/order')->load($orderID);
			if ($order->getId())
			{
				$message = Mage::helper('core')->__("Order") . " # " . $order->getIncrementId() . "\n\n";
				$items = $order->getAllVisibleItems();
				foreach($items as $item)
				{
					$message .= (int)$item->getQtyOrdered() . "x " .
						$item->getName() . "\n" .
						Mage::helper('core')->__("Price") . ": " . Mage::helper('core')->currency($item->getPrice(), true, false) . "\n\n";
				}
				$message .= Mage::helper('core')->__("Total") . ": " . Mage::helper('core')->currency($order->getGrandTotal(), true, false) . "\n" .
					Mage::helper('core')->__("Zipcode") . ": " . $order->getShippingAddress()->getPostcode();
				if ($this->reorder_cmd)
					$message .= "\n\n" . Mage::helper('core')->__("Reorder") . ": " . $this->reorder_cmd . $orderID;
				return $message;
			}
			return null;
		}

		private function prepareTelegramProdMessages($productID) // TODO add link to product name
		{
			$product = Mage::getModel('catalog/product')->load($productID);
			if ($product->getId())
			{
				if ($product->getStockItem()->getIsInStock() > 0)
				{
					$message = $product->getName() . "\n" .
						$this->excerpt($product->getShortDescription(), 60) . "\n" .
						Mage::helper('core')->__("Add to cart") . ": " . $this->add2cart_cmd . $product->getId();
					return $message;
				}
			}
			return null;
		}

		private function telegramHandler($apiKey)
		{
			// Instances the Telegram class
			$telegram = new Telegram($apiKey);

			// Take text and chat_id from the message
			$text = $telegram->Text();
			$chat_id = $telegram->ChatID();
			$message_id = $telegram->MessageID();

			if (!is_null($text) && !is_null($chat_id))
			{
				// Instances the model class
				$chatdata = $this->load($chat_id, 'telegram_chat_id');
				$chatdata->api_type = $this->tg_bot;

				if ($message_id == $chatdata->getTelegramMessageId()) // prevents to reply the same request twice
					return $telegram->respondSuccess();
				else
					$chatdata->updateChatdata('telegram_message_id', $message_id); // if this fails, it may send the same message twice

				// send feedback to user
				$telegram->sendChatAction(array('chat_id' => $chat_id, 'action' => 'typing'));

				// mage helper
				$magehelper = Mage::helper('core');

				$supportgroup = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_support_group');
				if ($supportgroup[0] == "g") // remove the 'g' from groupd id, and add '-'
					$supportgroup = "-" . ltrim($supportgroup, "g");

				// if it's a group message
				if ($telegram->messageFromGroup())
				{
					if ($chat_id == $supportgroup) // if the group sending the message is the support group
					{
						if ($telegram->ReplyToMessageID()) // if the message is replying another message
						{
							$reply_from_user = $telegram->ReplyToMessageFromUserID();
							if (!is_null($reply_from_user))
							{
								$telegram->sendMessage(array('chat_id' => $reply_from_user, 'text' => $magehelper->__("Message from support") . ":\n" . $text)); // TODO
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Message sent."))); // TODO
							}
							else if ($text == "/sendmessagetoall") // TODO
							{
								// TODO
							}
						}
						return $telegram->respondSuccess();
					}
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("I don't work with groups."))); // TODO
					return $telegram->respondSuccess(); // ignore all group messages
				}

				if ($chatdata->getIsLogged() == "1") // check if customer is logged
				{
					if (Mage::getModel('customer/customer')->load((int)$this->getCustomerId())->getId()) // if is a valid customer id
					{
						if ($chatdata->getEnableTelegram() != "1")
						{
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("To talk with me, please enable Telegram on your account chatbot settings.")));
							return $telegram->respondSuccess();
						}
					}
				}

				if (is_null($chatdata->getTelegramChatId()) && !$chatdata->checkCommand($text, $chatdata->start_cmd)) // if user isn't registred, and not using the start command
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg'); // TODO
					if ($message) // TODO
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
					try
					{
						$hash = substr(md5(uniqid($chat_id, true)), 0, 150); // TODO
						$chatdata // using magento model to insert data into database the proper way
						->setTelegramChatId($chat_id)
							->setHashKey($hash) // TODO
							->save();
					}
					catch (Exception $e)
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg)); // TODO
					}
					return $telegram->respondSuccess();
				}

				// init commands
				$chatdata->start_cmd = "/start";
				$chatdata->listacateg_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(1));
				$chatdata->search_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(2));
				$chatdata->login_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(3));
				$chatdata->listorders_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(4));
				$chatdata->reorder_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(5));
				$chatdata->add2cart_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(6));
				$chatdata->checkout_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(7));
				$chatdata->clearcart_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(8));
				$chatdata->trackorder_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(9));
				$chatdata->support_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(10));
				$chatdata->sendemail_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(11));
				$chatdata->cancel_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(12));
				$chatdata->help_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(13));
				$chatdata->about_cmd = $this->validateTelegramCmd("/" . $chatdata->getCommandString(14));

				if (!$chatdata->cancel_cmd) $chatdata->cancel_cmd = "/cancel"; // it must always have a cancel command

				// init messages
				$this->errormsg = $magehelper->__("Something went wrong, please try again.");
				$this->cancelmsg = $magehelper->__("To cancel, send") . " " . $chatdata->cancel_cmd;
				$this->canceledmsg = $magehelper->__("Ok, canceled.");
				$this->loginfirstmsg =  $magehelper->__("Please login first.");
				array_push($this->positivemsg, $magehelper->__("Ok"), $magehelper->__("Okay"), $magehelper->__("Cool"), $magehelper->__("Awesome"));
				// $this->positivemsg[array_rand($this->positivemsg)]

				// TODO DEBUG COMMANDS
//				$temp_var = $this->start_cmd . " - " .
//				$this->listacateg_cmd . " - " .
//				$this->search_cmd . " - " .
//				$this->login_cmd . " - " .
//				$this->listorders_cmd . " - " .
//				$this->reorder_cmd . " - " .
//				$this->add2cart_cmd . " - " .
//				$this->checkout_cmd . " - " .
//				$this->clearcart_cmd . " - " .
//				$this->trackorder_cmd . " - " .
//				$this->support_cmd . " - " .
//				$this->sendemail_cmd;
//				$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $temp_var));
//				$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->getTelegramConvState()));

				// start command
				if ($chatdata->checkCommand($text, $chatdata->start_cmd))
				//if ($text == $chatdata->start_cmd)
				{
					$startdata = explode(" ", $text);
					if (count($startdata) > 1) // has hash parameter
					{
						$chat_hash =  $this->load(trim($startdata[1]), 'hash_key');
						if ($chat_hash->getHashKey())
						{
							try
							{
								$chat_hash->addData(array("telegram_chat_id" => $chat_id));
								$chat_hash->save();
							}catch (Exception $e){}
							$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg'); // TODO
							if ($message) // TODO
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
						}
					}
					else if ($chatdata->getTelegramChatId()) // TODO
					{
						$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_about_msg'); // TODO
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));

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
						if ($message) // TODO
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
						try
						{
							$hash = substr(md5(uniqid($chat_id, true)), 0, 150); // TODO
							Mage::getModel('chatbot/chatdata') // using magento model to insert data into database the proper way
							->setTelegramChatId($chat_id)
								->setHashKey($hash) // TODO
								->save();
						}
						catch (Exception $e)
						{
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg)); // TODO
						}
					}
					return $telegram->respondSuccess();
				}

				// help command
				if ($chatdata->help_cmd && $text == $chatdata->help_cmd)
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_help_msg'); // TODO
					if ($message) // TODO
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
					return $telegram->respondSuccess();
				}

				// about command
				if ($chatdata->about_cmd && $text == $chatdata->about_cmd)
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_about_msg'); // TODO
					$cmdlisting = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_command_list');
					if ($cmdlisting == 1)
					{
						$message .= "\n\n" . $magehelper->__("Command list") . ":\n";
						if ($chatdata->listacateg_cmd) $message .= $chatdata->listacateg_cmd . " - " . $magehelper->__("List store categories.") . "\n";
						if ($chatdata->search_cmd) $message .= $chatdata->search_cmd . " - " . $magehelper->__("Search for products.") . "\n";
						if ($chatdata->login_cmd) $message .= $chatdata->login_cmd . " - " . $magehelper->__("Login into your account.") . "\n";
						if ($chatdata->listorders_cmd) $message .= $chatdata->listorders_cmd . " - " . $magehelper->__("List your personal orders.") . "\n";
						//$message .= $chatdata->reorder_cmd . " - " . $magehelper->__("Reorder a order.") . "\n";
						//$message .= $chatdata->add2cart_cmd . " - " . $magehelper->__("Add product to cart.") . "\n";
						if ($chatdata->checkout_cmd) $message .= $chatdata->checkout_cmd . " - " . $magehelper->__("Checkout your order.") . "\n";
						if ($chatdata->clearcart_cmd) $message .= $chatdata->clearcart_cmd . " - " . $magehelper->__("Clear your cart.") . "\n";
						if ($chatdata->trackorder_cmd) $message .= $chatdata->trackorder_cmd . " - " . $magehelper->__("Track your order status.") . "\n";
						if ($chatdata->support_cmd) $message .= $chatdata->support_cmd . " - " . $magehelper->__("Send message to support.") . "\n";
						if ($chatdata->sendemail_cmd) $message .= $chatdata->sendemail_cmd . " - " . $magehelper->__("Send email.") . "\n";
						//$message .= $chatdata->cancel_cmd . " - " . $magehelper->__("Cancel.");
						if ($chatdata->help_cmd) $message .= $chatdata->help_cmd . " - " . $magehelper->__("Get help.") . "\n";
						//$message .= $chatdata->about_cmd . " - " . $magehelper->__("About.");
					}

					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
					return $telegram->respondSuccess();
				}

				// cancel command
				if ($chatdata->cancel_cmd && $text == $chatdata->cancel_cmd) // TODO
				{
					if ($chatdata->getTelegramConvState() == $this->list_cat_state)
					{
						$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories
						$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $this->canceledmsg);
					}
					else if ($chatdata->getTelegramConvState() == $this->support_state)
					{
						$content = array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("exiting support mode."));
						//$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Done.")));
					}
					else if ($chatdata->getTelegramConvState() == $this->search_state)
					{
						$content = array('chat_id' => $chat_id, 'text' => $this->canceledmsg);
					}
					else if ($chatdata->getTelegramConvState() == $this->send_email_state)
					{
						$content = array('chat_id' => $chat_id, 'text' => $this->canceledmsg);
					}
					else
						$content = array('chat_id' => $chat_id, 'text' => $this->errormsg);

					if (!$chatdata->updateChatdata('telegram_conv_state', $this->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else
						$telegram->sendMessage($content);
					return $telegram->respondSuccess();
				}

				// add2cart commands
				if ($chatdata->checkCommand($text, $chatdata->add2cart_cmd)) // && $chatdata->getTelegramConvState() == $this->list_prod_state TODO
				{
					$cmdvalue = $chatdata->getCommandValue($text, $chatdata->add2cart_cmd);
					if ($cmdvalue) // TODO
					{
						if ($chatdata->addProd2Cart($cmdvalue))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Added. To checkout send") . " " . $chatdata->checkout_cmd));
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					}
					return $telegram->respondSuccess();
				}

				// states
				if ($chatdata->getTelegramConvState() == $this->list_cat_state) // TODO show only in stock products
				{
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
					$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories

					if ($_category) // this works, no need to get the id
					{
						$noprodflag = false;
						$productIDs = $_category->getProductCollection()->getAllIds();
						if ($productIDs)
						{
							$i = 0;
							foreach ($productIDs as $productID)
							{
								$message = $this->prepareTelegramProdMessages($productID);
								if ($message) // TODO
								{
									$i++;
									$image = $this->loadImageContent($productID);
									if ($image)
										$telegram->sendPhoto(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'photo' => $image, 'caption' => $message));
									else
										$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $message));
								}
							}
							if ($i == 0)
								$noprodflag = true;
							if (!$chatdata->updateChatdata('telegram_conv_state', $this->list_prod_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
						}
						else
							$noprodflag = true;

						if ($noprodflag)
							$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $magehelper->__("Sorry, no products found in this category.")));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $this->errormsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->getTelegramConvState() == $this->search_state) // TODO
				{
					$noprodflag = false;
					$productIDs = $this->getProductIdsBySearch($text);
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else if ($productIDs)
					{
						$i = 0;
						foreach ($productIDs as $productID)
						{
							$message = $this->prepareTelegramProdMessages($productID);
							if ($message) // TODO
							{
								$i++;
								$image = $this->loadImageContent($productID);
								if ($image)
									$telegram->sendPhoto(array('chat_id' => $chat_id, 'photo' => $image, 'caption' => $message));
								else
									$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
							}
						}
						if ($i == 0)
							$noprodflag = true;
					}
					else
						$noprodflag = true;

					if ($noprodflag)
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Sorry, no products found for this criteria.")));

					return $telegram->respondSuccess();
				}
				else if ($chatdata->getTelegramConvState() == $this->support_state)
				{
					$telegram->forwardMessage(array('chat_id' => $supportgroup, 'from_chat_id' => $chat_id, 'message_id' => $telegram->MessageID()));
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("we have sent your message to support.")));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->getTelegramConvState() == $this->send_email_state)
				{
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Trying to send the email...")));
					if ($chatdata->sendEmail($text))
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Done.")));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => "Sorry, I wasn't able to send an email this time. Please try again later."));
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->getTelegramConvState() == $this->track_order_state)
				{
					$errorflag = false;
					if ($chatdata->getIsLogged() == "1")
					{
						$order = Mage::getModel('sales/order')->loadByIncrementId($text);
						if ($order->getId())
						{
							if ($order->getCustomerId() == $chatdata->getCustomerId()) // not a problem if customer dosen't exist
							{
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Your order status is") . " " . $order->getStatus()));
							}
							else
								$errorflag = true;
						}
						else
							$errorflag = true;
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->loginfirstmsg));
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else if ($errorflag)
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Sorry, we couldn't find any order with this information.")));
					return $telegram->respondSuccess();
				}

				// commands
				if ($chatdata->listacateg_cmd && $text == $chatdata->listacateg_cmd)
				{
					$helper = Mage::helper('catalog/category');
					$categories = $helper->getStoreCategories(); // TODO test with a store without categories
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->list_cat_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else if ($categories)
					{
						$option = array();
						foreach ($categories as $_category) // TODO fix buttons max size
						{
							array_push($option, $_category->getName());
						}

						$keyb = $telegram->buildKeyBoard(array($option));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $magehelper->__("Select a category")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $this->cancelmsg));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkout_cmd && $text == $chatdata->checkout_cmd) // TODO
				{
					if ($chatdata->getIsLogged() == "1")
					{
						if (Mage::getModel('customer/customer')->load((int)$this->getCustomerId())->getId())
						{
							// if user is set as logged, then login using magento singleton
							$customersssion = Mage::getSingleton('customer/session');
							$customersssion->loginById((int)$chatdata->getCustomerId());
							// then set current quote as customer quote
							$customer = Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId());
							$quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
							// set quote and session ids from logged user
							$quoteId = $quote->getId();
							$sessionId = $customersssion->getEncryptedSessionId();
						}
					}
					if (!($sessionId && $quoteId))
					{
						// set quote and session ids from chatbot class
						$sessionId = $chatdata->getSessionId();
						$quoteId = $chatdata->getQuoteId();
					}
					$emptycart = true;
					if ($sessionId && $quoteId)
					{
						$cartUrl = Mage::helper('checkout/cart')->getCartUrl();
						if (!isset(parse_url($cartUrl)['SID']))
							$cartUrl .= "?SID=" . $sessionId; // add session id to url

						$cart = Mage::getModel('checkout/cart')->setQuote(Mage::getModel('sales/quote')->loadByIdWithoutStore((int)$quoteId));
						$ordersubtotal = $cart->getQuote()->getSubtotal();
						if ($ordersubtotal > 0)
						{
							$emptycart = false;
							$message = $magehelper->__("Products on cart") . ":\n";
							foreach ($cart->getQuote()->getItemsCollection() as $item) // TODO
							{
								$message .= $item->getQty() . "x " . $item->getProduct()->getName() . "\n" .
									$magehelper->__("Price") . ": " . Mage::helper('core')->currency($item->getProduct()->getPrice(), true, false) . "\n\n";
							}
							$message .= $magehelper->__("Total") . ": " .
								Mage::helper('core')->currency($ordersubtotal, true, false) . "\n\n" .
								"[" . $magehelper->__("Checkout Here") . "](" . $cartUrl . ")";

							if (!$chatdata->updateChatdata('telegram_conv_state', $this->checkout_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
							else
								$telegram->sendMessage(array('chat_id' => $chat_id, 'parse_mode' => 'Markdown', 'text' => $message));
						}
						else if (!$chatdata->clearCart()) // try to clear cart
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					}
					if ($emptycart)
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Your cart is empty.")));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->clearcart_cmd && $text == $chatdata->clearcart_cmd)
				{
					if ($chatdata->clearCart())
					{
						if (!$chatdata->updateChatdata('telegram_conv_state', $this->clear_cart_state))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Cart cleared.")));
					}
					return $telegram->respondSuccess();
				}
				else if ($chatdata->search_cmd && $text == $chatdata->search_cmd)
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->search_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("what do you want to search for?")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->cancelmsg));
					}
					return $telegram->respondSuccess();
				}
				else if ($chatdata->login_cmd && $text == $chatdata->login_cmd) // TODO
				{
					$hashlink = Mage::getUrl('chatbot/settings/index/') . "hash/" . $chatdata->getHashKey();
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->login_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("To login to your account, access this link") . ": " . $hashlink));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->listorders_cmd && $text == $chatdata->listorders_cmd) // TODO
				{
					if ($chatdata->getIsLogged() == "1")
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("let me fetch that for you.")));
						$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
						$i = 0;
						if ($ordersIDs)
						{
							foreach($ordersIDs as $orderID)
							{
								$message = $chatdata->prepareTelegramOrderMessages($orderID);
								if ($message) // TODO
								{
									$i++;
									$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
								}
							}
						}
						else
						{
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("This account has no orders.")));
							return $telegram->respondSuccess();
						}
						if ($i == 0)
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
						else if (!$chatdata->updateChatdata('telegram_conv_state', $this->list_orders_state))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->loginfirstmsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->reorder_cmd)) // TODO
				{
					if ($this->getIsLogged() == "1")
					{
						$errorflag = false;
						$cmdvalue = $chatdata->getCommandValue($text, $chatdata->reorder_cmd);
						if ($cmdvalue)
						{
							if ($chatdata->clearCart())
							{
								$order = Mage::getModel('sales/order')->load($cmdvalue);
								if ($order->getId())
								{
									foreach($order->getAllVisibleItems() as $item) {
										if (!$chatdata->addProd2Cart($item->getProductId()))
											$errorflag = true;
									}
								}
								else
									$errorflag = true;
							}
							else
								$errorflag = true;
						}
						else
							$errorflag = true;

						if ($errorflag)
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
						else if (!$chatdata->updateChatdata('telegram_conv_state', $this->reorder_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
						else // success!!
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("to checkout send") . " " . $chatdata->checkout_cmd));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->loginfirstmsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->trackorder_cmd && $text == $chatdata->trackorder_cmd) // TODO
				{
					if ($chatdata->getIsLogged() == "1")
					{
						$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
						if ($ordersIDs)
						{
							if (!$chatdata->updateChatdata('telegram_conv_state', $this->track_order_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
							else
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("send the order number.")));
						}
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Your account dosen't have any orders.")));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->loginfirstmsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->support_cmd && $text == $chatdata->support_cmd) // TODO
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->support_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("what do you need support for?")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->cancelmsg));
					}
					return $telegram->respondSuccess();
				}
				else if ($chatdata->sendemail_cmd && $text == $chatdata->sendemail_cmd) // TODO
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $this->send_email_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->errormsg));
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $this->positivemsg[array_rand($this->positivemsg)] . ", " . $magehelper->__("write the email content.")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $this->cancelmsg));
					}
					return $telegram->respondSuccess();
				}
				else
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Sorry, I didn't understand that."))); // TODO
			}
			return $telegram->respondSuccess();
		}

		// FACEBOOK FUNCTIONS
		private function facebookHandler($apiKey)
		{
			// Instances the Facebook class
			$facebook = new Messenger($apiKey);

			$hub_token = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
			$verify = $facebook->verifyWebhook($hub_token);
			if ($verify)
			{
				// TODO fix this!!! this is SO UGLY it hurts me inside, please let me know if you know a better way to do this
				$path = Mage::getBaseDir() . "/chatbot/chatdata/facebook/index.php";
				if (!file_exists(dirname($path)))
					mkdir(dirname($path), 0777, true);
				file_put_contents($path, "<?php $" . "hub_token = '" . $hub_token ."'; $" . "root = '" . Mage::getBaseDir() ."'; if ($" . "_REQUEST['hub_verify_token'] == $" . "hub_token){ echo $" . "_REQUEST['hub_challenge']; $" . "del = true;} if ($" . "del == true) {unlink('index.php'); rmdir($" . "root . '/chatbot/chatdata/facebook/'); rmdir($" . "root . '/chatbot/chatdata/'); rmdir($" . "root . '/chatbot/');} ?>");

				return $verify;
			}

			// Take text and chat_id from the message
			$text = $facebook->Text();
			$chat_id = $facebook->ChatID();
			$message_id = $facebook->EntryID();

			$message = "";
			$result = "";

			if (!is_null($text) && !is_null($chat_id))
			{
				$message = $text;
				$result = $facebook->sendMessage($chat_id, $message);
			}
		}

//		// WHATSAPP FUNCTIONS
//		private function whatsappHandler($apiKey)
//		{
//
//		}

		// WECHAT FUNCTIONS (maybe)
//		private function wechatHandler($apiKey)
//		{
//
//		}
	}