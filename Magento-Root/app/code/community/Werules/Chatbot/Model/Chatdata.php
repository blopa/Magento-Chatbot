<?php
	include("Api/Telegram/Handler.php");
	include("Api/Facebook/Handler.php");
	//include("Api/Whatsapp/Handler.php");
	//include("Api/WeChat/Handler.php");
	include("Api/witAI/witAI.php");

class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		// APIs
		protected $_apiType = "";
		protected $_tgBot = "telegram";
		protected $_fbBot = "facebook";
		protected $_wappBot = "whatsapp";
		protected $_wechatBot = "wechat";

		// CONVERSATION STATES
		protected $_startState = 0;
		protected $_listCategoriesState = 1;
		protected $_listProductsState = 2;
		protected $_searchState = 3;
		protected $_loginState = 4;
		protected $_listOrdersState = 5;
		protected $_reorderState = 6;
		protected $_add2CartState = 7;
		protected $_checkoutState = 9;
		protected $_trackOrderState = 10;
		protected $_supportState = 11;
		protected $_sendEmailState = 12;
		protected $_clearCartState = 13;

		// ADMIN STATES
		protected $_replyToSupportMessageState = 14;

		// COMMANDS
		protected $_cmdList =
		"
			start,
			list_cat,
			search,
			login,
			list_orders,
			reorder,
			add2cart,
			checkout,
			clear_cart,
			track_order,
			support,
			send_email,
			cancel,
			help,
			about,
			logout,
			register
		";
		protected $_startCmd = array();
		protected $_listCategoriesCmd = array();
		protected $_searchCmd = array();
		protected $_loginCmd = array();
		protected $_listOrdersCmd = array();
		protected $_reorderCmd = array();
		protected $_add2CartCmd = array();
		protected $_checkoutCmd = array();
		protected $_clearCartCmd = array();
		protected $_trackOrderCmd = array();
		protected $_supportCmd = array();
		protected $_sendEmailCmd = array();
		protected $_cancelCmd = array();
		protected $_helpCmd = array();
		protected $_aboutCmd = array();
		protected $_logoutCmd = array();
		protected $_registerCmd = array();

	// admin cmds
//		protected $adminCmdList =
//		"
//			messagetoall,
//			endsupport,
//			blocksupport
//		";
		protected $_admSendMessage2AllCmd = "messagetoall";
		protected $_admEndSupportCmd = "endsupport";
		protected $_admBlockSupportCmd = "blocksupport";
		protected $_admEnableSupportCmd = "enablesupport";

		// REGEX
		protected $_unallowedCharacters = "/[^A-Za-z0-9 _]/";
		
		// DEFAULT MESSAGES
		protected $_errorMessage = "";
		protected $_cancelMessage = "";
		protected $_canceledMessage = "";
		protected $_loginFirstMessage = "";
		protected $_positiveMessages = array();

		// URLS
		public $_tgUrl = "https://t.me/";
		public $_fbUrl = "https://m.me/";
//		protected $_wappUrl = "";
//		protected $_wechatUrl = "";

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
			if ($webhook && $apiKey && $action == $this->_tgBot) // set telegram webhook
			{
				$magehelper = Mage::helper('core');
				$telegram = new Telegram($apiKey);
				//$webhookUrl = str_replace("http://", "https://", Mage::getUrl('*/*/*', array('_use_rewrite' => true, '_forced_secure' => true)));
				// replace http by https, and remove all url parameters with strok
				$webhookUrl = str_replace("http://", "https://", strtok(Mage::getUrl('chatbot/chatdata/' . $this->_tgBot, array('_forced_secure' => true)), '?'));
				try {
					$telegram->setWebhook($webhookUrl);
				}
				catch (Exception $e) {
					return $magehelper->__("Something went wrong, please try again.");
				}

				//return var_dump(array('url' => $webhookUrl));
				$tgGetWebhook = "<a href='https://api.telegram.org/bot" . $apiKey . "/getWebhookInfo' target='_blank'>" . $magehelper->__("here") . "</a>";
				$tgSetWebhook = "<a href='https://api.telegram.org/bot" . $apiKey . "/setWebhook?url=" . $webhookUrl . "' target='_blank'>" . $magehelper->__("here") . "</a>";
				$message = $magehelper->__("Webhook for Telegram configured.") .
					$magehelper->__("Webhook URL") . ": " .
					$webhookUrl . "<br>" .
					$magehelper->__("Click %s to check that information on Telegram website. If a wrong URL is set, try reloading this page or click %s.", $tgGetWebhook, $tgSetWebhook)
				;
				return $message;
			}
			else if ($webhook && $apiKey && $action == $this->_fbBot) // set facebook webhook
			{
				$magehelper = Mage::helper('core');
				// replace http by https, and remove all url parameters with strok
				$webhookUrl = str_replace("http://", "https://", strtok(Mage::getUrl('chatbot/chatdata/' . $this->_fbBot, array('_forced_secure' => true)), '?'));

				$message = $magehelper->__("To configure Facebook webhook access") .
					" https://developers.facebook.com/apps/(FACEBOOK_APP_ID)/webhooks/" .
					$magehelper->__("and set the webhook URL as") . " " . $webhookUrl
				;
				return $message;
			} // start to handle conversation
			else if ($action == $this->_tgBot && $apiKey) // telegram api
			{
				// all logic goes here
				return Mage::getModel('chatbot/api_telegram_handler')->telegramHandler($apiKey);
			}
			else if ($action == $this->_fbBot && $apiKey) // facebook api
			{
				// all logic goes here
				return Mage::getModel('chatbot/api_facebook_handler')->facebookHandler($apiKey);
			}
			else
				return "Nothing to see here"; // TODO
		}

		protected function getApikey($apiType) // check if bot integration is enabled
		{
			if ($apiType == $this->_tgBot) // telegram api
			{
				//$enabled = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
				$apikey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
				//if ($enabled == 1 && $apikey) // is enabled and has API
				if ($apikey) // has API
					return $apikey;
			}
			else if ($apiType == $this->_fbBot)
			{
				//$enabled = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_bot');
				$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
				//if ($enabled == 1 && $apikey) // is enabled and has API
				if ($apikey) // has API
					return $apikey;
			}
			return null;
		}

		protected function sendEmail($text, $username)
		{
			$storeName = Mage::app()->getStore()->getName();
			$storeEmail = Mage::getStoreConfig('trans_email/ident_general/email');// TODO
			$magehelper = Mage::helper('core');

			$url = $magehelper->__("Not informed");
			$customerEmail = $magehelper->__("Not informed");
			if ($username)
				$customerName = $username;
			else
				$customerName = $magehelper->__("Not informed");

			$mail = new Zend_Mail('UTF-8');

			if ($this->_apiType == $this->_tgBot)
			{
				$url = $this->_tgUrl . $this->getTelegramChatId();
				if ($this->getCustomerId())
				{
					$customer = Mage::getModel('customer/customer')->load((int)$this->getCustomerId());
					if ($customer->getId())
					{
						$customerEmail = $customer->getEmail();
						$customerName = $customer->getName();
						$mail->setReplyTo($customerEmail);
					}
				}
			}
			else if ($this->_apiType == $this->_fbBot)
			{
				// code here etc
			}

			$emailBody =
				$magehelper->__("Message from chatbot customer") . "<br><br>" .
				$magehelper->__("Customer name") . ": " .
				$customerName . "<br>" .
				$magehelper->__("Message") . ":<br>" .
				$text . "<br><br>" .
				$magehelper->__("Contacts") . ":<br>" .
				$magehelper->__("Chatbot") . ": " . $url . "<br>" .
				$magehelper->__("Email") . ": " . $customerEmail . "<br>";

			$mail->setBodyHtml($emailBody);
			$mail->setFrom($storeEmail, $storeName);
			$mail->addTo($storeEmail, $storeName);
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

		protected function addProd2Cart($prodId) // TODO add expiration date for sessions
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
				$hasQuote = $this->getSessionId() && $this->getQuoteId(); // has class quote and session ids
				if ($this->getIsLogged() == "1")
				{
					$customer = Mage::getModel('customer/customer')->load((int)$this->getCustomerId());
					if ($customer->getId())
					{
						// if user is set as logged, then login using magento singleton
						Mage::getSingleton('customer/session')->loginById($this->getCustomerId());
						if (!$hasQuote)
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
				if ($hasQuote)
				{
					// init quote and session from chatbot class
					$cart->setQuote(Mage::getModel('sales/quote')->loadByIdWithoutStore((int)$this->getQuoteId()));
					$checkout->setSessionId($this->getSessionId());
				}
				// add product and save cart
//				$product = Mage::getModel('catalog/product')->load($prodId);
//				$product->setSkipCheckRequiredOption(true);
//				$cart->addProduct($product);
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
				//Mage::logException($e);
				return false;
			}

			return true;
		}

		protected function getCommandString($cmdId)
		{
			$rep = "";
			if ($this->_apiType == $this->_tgBot)
			{
				$rep = "_";
				$confpath = 'chatbot_enable/telegram_config/';
			}
			else if ($this->_apiType == $this->_fbBot)
			{
				$rep = " ";
				$confpath = 'chatbot_enable/facebook_config/';
			}
//			else if ($this->_apiType == $this->_wappBot)
//				$confpath = 'chatbot_enable/whatsapp_config/';

			$config = Mage::getStoreConfig($confpath . 'enabled_commands');
			$enabledCmds = explode(',', $config);
			if (is_array($enabledCmds))
			{
				if (in_array($cmdId, $enabledCmds))
				{
					$defaultCmds = explode(',', $this->_cmdList);
					if (is_array($defaultCmds))
					{
						$cmdCode = "";
						$alias = array();
						$config = Mage::getStoreConfig($confpath . 'commands_list');
						if ($config)
						{
							$commands = unserialize($config);
							if (is_array($commands))
							{
								foreach($commands as $cmd)
								{
									if ($cmd['command_id'] == $cmdId)
									{
										$cmdCode = $cmd['command_code'];
										$alias = array_map('strtolower', explode(',', $cmd['command_alias_list']));
										break;
									}
								}
								if (empty($cmdCode)) // if no command found, return the default
									$cmdCode = $defaultCmds[$cmdId];
							}
							else // if no command found, return the default
								$cmdCode = $defaultCmds[$cmdId];
						}
						else // if no command found, return the default
							$cmdCode = $defaultCmds[$cmdId];

						$cmdCode = preg_replace( // remove all non-alphanumerics
							$this->_unallowedCharacters,
							'',
							str_replace( // replace whitespace for underscore
								' ',
								$rep,
								trim($cmdCode)
							)
						);

						return array('command' => strtolower($cmdCode), 'alias' => $alias);
					}
				}
			}
			return array('command' => null, 'alias' => null);
		}

		protected function getCommandValue($text, $cmd)
		{
			if (strlen($text) > strlen($cmd))
				return substr($text, strlen($cmd), strlen($text));
			return null;
		}

		protected function checkCommand($text, $cmd)
		{
			if ($cmd['command'])
			{
				$t = strtolower($text);
				if ($t == $cmd['command'])
					return true;
				else if ($cmd['alias'])
				{
					//$alias = explode(",", $cmd['alias']);
					$alias = $cmd['alias'];
					if (is_array($alias))
					{
						foreach ($alias as $al)
						{
							if (!empty($al))
								if (strpos($t, $al) !== false)
									return true;
						}
					}
				}
			}

			return false;
		}

		protected function checkCommandWithValue($text, $cmd)
		{
			if ($cmd)
				return substr($text, 0, strlen($cmd)) == $cmd;
			return false;
		}

		protected function clearCart()
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
				$data = array(
					$datatype => $state,
					"updated_at" => date('Y-m-d H:i:s')
				); // data to be insert on database
				$this->addData($data);
				$this->save();
			}
			catch (Exception $e)
			{
				return false;
			}

			return true;
		}

		protected function excerpt($text, $size)
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

		protected function getOrdersIdsFromCustomer()
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

		protected function getProductIdsBySearch($searchstring)
		{
			// Code to Search Product by $searchstring and get Product IDs
			$product_collection_ids = Mage::getResourceModel('catalog/product_collection')
				->addAttributeToSelect('*')
				->addAttributeToFilter('visibility', 4)
				->addAttributeToFilter('type_id', 'simple')
				->addAttributeToFilter(
					array(
						array('attribute' => 'sku', 'like' => '%' . $searchstring .'%'),
						array('attribute' => 'name', 'like' => '%' . $searchstring .'%')
					)
				)
				->getAllIds();

			if (!empty($product_collection_ids))
				return $product_collection_ids;

			return false;
		}

		protected function loadImageContent($productID)
		{
			$imagepath = Mage::getModel('catalog/product')->load($productID)->getSmallImage();
			if ($imagepath && $imagepath != "no_selection")
			{
				$absolutePath =
					Mage::getBaseDir('media') .
					DS . "catalog" . DS . "product" .
					$imagepath;

				return curl_file_create($absolutePath, 'image/jpg');
			}
			return null;
		}

		// TELEGRAM FUNCTIONS
		protected function validateTelegramCmd($cmd)
		{
			if ($cmd == "/")
				return null;
			return $cmd;
		}

		protected function prepareTelegramOrderMessages($orderID) // TODO add link to product name
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
				if ($this->_reorderCmd['command'])
					$message .= "\n\n" . Mage::helper('core')->__("Reorder") . ": " . $this->_reorderCmd['command'] . $orderID;
				return $message;
			}
			return null;
		}

		protected function prepareTelegramProdMessages($productID) // TODO add link to product name
		{
			$product = Mage::getModel('catalog/product')->load($productID);
			if ($product->getId())
			{
				if ($product->getStockItem()->getIsInStock() > 0)
				{
					$message = $product->getName() . "\n" .
						$this->excerpt($product->getShortDescription(), 60) . "\n" .
						Mage::helper('core')->__("Add to cart") . ": " . $this->_add2CartCmd['command'] . $product->getId();
					return $message;
				}
			}
			return null;
		}

		protected function prepareFacebookProdMessages($productID) // TODO add link to product name
		{
			$product = Mage::getModel('catalog/product')->load($productID);
			if ($product->getId())
			{
				if ($product->getStockItem()->getIsInStock() > 0)
				{
					$message = $product->getName() . "\n" .
						$this->excerpt($product->getShortDescription(), 60);
					return $message;
				}
			}
			return null;
		}

	protected function prepareFacebookOrderMessages($orderID) // TODO add link to product name
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

			return $message;
		}
		return null;
	}

//		// WHATSAPP FUNCTIONS
//		public function whatsappHandler($apiKey)
//		{
//
//		}

		// WECHAT FUNCTIONS (maybe)
//		public function wechatHandler($apiKey)
//		{
//
//		}
	}