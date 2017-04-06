<?php
	include("Api/Telegram/Handler.php");
	include("Api/Facebook/Handler.php");
	//include("Api/Whatsapp/Handler.php");
	//include("Api/WeChat/Handler.php");
	include("Api/witAI/witAI.php");

class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		// WITAI
		protected $_isWitAi = false;

		// APIs
		protected $_apiType = "";
		protected $_apiKey = "";
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
			list_categories,
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
			// handle webhook configuration
			if (!empty($webhook) && $action == $this->_tgBot) // set telegram webhook
			{
				$mageHelper = Mage::helper('core');
				$apiKey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
				$telegram = new Telegram($apiKey);
				$customKey = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
				//$webhookUrl = str_replace("http://", "https://", Mage::getUrl('*/*/*', array('_use_rewrite' => true, '_forced_secure' => true)));
				// replace http by https, and remove all url parameters with strok
				$webhookUrl = str_replace("http://", "https://", strtok(Mage::getUrl('chatbot/chatdata/' . $this->_tgBot, array('_forced_secure' => true)), '?') . "key" . DS . $customKey . DS);
				try {
					$telegram->setWebhook($webhookUrl);
				}
				catch (Exception $e) {
					return $mageHelper->__("Something went wrong, please try again.");
				}

				//return var_dump(array('url' => $webhookUrl));
				$tgGetWebhook = "<a href='https://api.telegram.org/bot" . $apiKey . "/getWebhookInfo' target='_blank'>" . $mageHelper->__("here") . "</a>";
				$tgSetWebhook = "<a href='https://api.telegram.org/bot" . $apiKey . "/setWebhook?url=" . $webhookUrl . "' target='_blank'>" . $mageHelper->__("here") . "</a>";
				$message = $mageHelper->__("Webhook for Telegram configured.") .
					$mageHelper->__("Webhook URL") . ": " .
					$webhookUrl . "<br>" .
					$mageHelper->__("Click %s to check that information on Telegram website. If a wrong URL is set, try reloading this page or click %s.", $tgGetWebhook, $tgSetWebhook)
				;
				return $message;
			}
			else if (!empty($webhook) && $action == $this->_fbBot) // set facebook webhook
			{
				$mageHelper = Mage::helper('core');
				$customKey = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
				// replace http by https, and remove all url parameters with strok
				$webhookUrl = str_replace("http://", "https://", strtok(Mage::getUrl('chatbot/chatdata/' . $this->_fbBot, array('_forced_secure' => true)), '?') . "key" . DS . $customKey . DS);

				$message = $mageHelper->__("To configure Facebook webhook access") .
					" https://developers.facebook.com/apps/(FACEBOOK_APP_ID)/webhooks/ " .
					$mageHelper->__("use your Custom Key (%s) as your Verify Token", $webhook) . " " .
					$mageHelper->__("and set the webhook URL as") . " " . $webhookUrl
				;
				return $message;
			} // start to handle conversation
			else if ($action == $this->_tgBot) // telegram api
			{
				// all logic goes here
				$handler = Mage::getModel('chatbot/api_telegram_handler');
				return $handler->telegramHandler();
			}
			else if ($action == $this->_fbBot) // facebook api
			{
				// all logic goes here
				$handler = Mage::getModel('chatbot/api_facebook_handler');
				return $handler->facebookHandler();
			}
			else
				return json_encode(array("status" => "error")); // TODO
		}

		protected function sendEmail($text, $username)
		{
			$storeName = Mage::app()->getStore()->getName();
			$storeEmail = Mage::getStoreConfig('trans_email/ident_general/email');// TODO
			$mageHelper = Mage::helper('core');

			$url = $mageHelper->__("Not informed");
			$customerEmail = $mageHelper->__("Not informed");
			if ($username)
				$customerName = $username;
			else
				$customerName = $mageHelper->__("Not informed");

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
				$mageHelper->__("Message from chatbot customer") . "<br><br>" .
				$mageHelper->__("Customer name") . ": " .
				$customerName . "<br>" .
				$mageHelper->__("Message") . ":<br>" .
				$text . "<br><br>" .
				$mageHelper->__("Contacts") . ":<br>" .
				$mageHelper->__("Chatbot") . ": " . $url . "<br>" .
				$mageHelper->__("Email") . ": " . $customerEmail . "<br>";

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
			$confPath = "";
			if ($this->_apiType == $this->_tgBot)
			{
				$rep = "_";
				$confPath = 'chatbot_enable/telegram_config/';
			}
			else if ($this->_apiType == $this->_fbBot)
			{
				$rep = " ";
				$confPath = 'chatbot_enable/facebook_config/';
			}

			$defaultCmds = explode(',', $this->_cmdList);
			if (is_array($defaultCmds)) // should never fail
			{
				$cmdCode = "";
				$alias = array();
				$config = Mage::getStoreConfig($confPath . 'commands_list');
				if (!empty($config))
				{
					$commands = unserialize($config);
					if (is_array($commands))
					{
						foreach($commands as $cmd)
						{
							if ($cmd['command_id'] == $cmdId && $cmd['enable_command'] == "1")
							{
								if (empty($cmd['command_code']))
								{
									$cmdCode = $defaultCmds[$cmdId];
									break;
								}

								$cmdCode = $cmd['command_code'];
								$alias = array_map('strtolower', explode(',', $cmd['command_alias_list']));
								break;
							}
						}
					}
					else // if no command found, return the default
						$cmdCode = $defaultCmds[$cmdId];
				}
				else // if no command found, return the default
					$cmdCode = $defaultCmds[$cmdId];

				if (empty($cmdCode)) // if no command enabled found, return null
					return array('command' => null, 'alias' => null);

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

		protected function startsWith($haystack, $needle)
		{
			if ($needle)
				return substr($haystack, 0, strlen($needle)) == $needle;
			return false;
		}

		protected function endsWith($haystack, $needle)
		{
			$length = strlen($needle);
			if ($length == 0)
				return true;

			return (substr($haystack, -$length) === $needle);
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

		public function updateChatdata($dataType, $state)
		{
			try
			{
				$data = array(
					$dataType => $state,
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

		protected function getProductIdsBySearch($searchString)
		{
			// Code to Search Product by $searchstring and get Product IDs
			$productCollection = Mage::getResourceModel('catalog/product_collection')
				->addAttributeToSelect('*')
				->addAttributeToFilter('visibility', 4)
				->addAttributeToFilter('type_id', 'simple')
				->addAttributeToFilter(
					array(
						array('attribute' => 'sku', 'like' => '%' . $searchString .'%'),
						array('attribute' => 'name', 'like' => '%' . $searchString .'%')
					)
				);
				//->getAllIds();
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
			$productIDs = $productCollection->getAllIds();

			if (!empty($productIDs))
				return $productIDs;

			return false;
		}

		protected function transcribeAudio()
		{
			$googleSpeechURL = "https://speech.googleapis.com/v1beta1/speech:syncrecognize?key=xxxxxxxxxxxx";
			$upload = file_get_contents("1.wav");
			$fileData = base64_encode($upload);

			$data = array(
				"config" => array(
					"encoding" => "LINEAR16",
					"sample_rate" => 16000,
					"language_code" => "pt-BR"
				),
				"audio" => array(
					"content" => base64_encode($fileData)
				)
			);

			$dataString = json_encode($data);

			$ch = curl_init($googleSpeechURL);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($dataString))
			);

			$result = curl_exec($ch);

			return json_decode($result, true);
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

		protected function listTelegramCommandsMessage()
		{
			$mageHelper = Mage::helper('core');

			$message = "\n\n" . $mageHelper->__("Command list") . ":\n";
			if ($this->_listCategoriesCmd['command']) $message .= $this->_listCategoriesCmd['command'] . " - " . $mageHelper->__("List store categories.") . "\n";
			if ($this->_searchCmd['command']) $message .= $this->_searchCmd['command'] . " - " . $mageHelper->__("Search for products.") . "\n";
			if ($this->_loginCmd['command']) $message .= $this->_loginCmd['command'] . " - " . $mageHelper->__("Login into your account.") . "\n";
			if ($this->_logoutCmd['command']) $message .= $this->_logoutCmd['command'] . " - " . $mageHelper->__("Logout from your account.") . "\n";
			if ($this->_registerCmd['command']) $message .= $this->_registerCmd['command'] . " - " . $mageHelper->__("Create a new account.") . "\n";
			if ($this->_listOrdersCmd['command']) $message .= $this->_listOrdersCmd['command'] . " - " . $mageHelper->__("List your personal orders.") . "\n";
			//$message .= $chatdata->_reorderCmd['command'] . " - " . $magehelper->__("Reorder a order.") . "\n";
			//$message .= $chatdata->_add2CartCmd['command'] . " - " . $magehelper->__("Add product to cart.") . "\n";
			if ($this->_checkoutCmd['command']) $message .= $this->_checkoutCmd['command'] . " - " . $mageHelper->__("Checkout your order.") . "\n";
			if ($this->_clearCartCmd['command']) $message .= $this->_clearCartCmd['command'] . " - " . $mageHelper->__("Clear your cart.") . "\n";
			if ($this->_trackOrderCmd['command']) $message .= $this->_trackOrderCmd['command'] . " - " . $mageHelper->__("Track your order status.") . "\n";
			if ($this->_supportCmd['command']) $message .= $this->_supportCmd['command'] . " - " . $mageHelper->__("Send message to support.") . "\n";
			if ($this->_sendEmailCmd['command']) $message .= $this->_sendEmailCmd['command'] . " - " . $mageHelper->__("Send email.") . "\n";
			//$message .= $chatdata->_cancelCmd['command'] . " - " . $magehelper->__("Cancel.");
			if ($this->_helpCmd['command']) $message .= $this->_helpCmd['command'] . " - " . $mageHelper->__("Get help.") . "\n";
			if ($this->_aboutCmd['command']) $message .= $this->_aboutCmd['command'] . " - " . $mageHelper->__("About.") . "\n";

			return $message;
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
					$mageHelper = Mage::helper('core');
					$message = $product->getName() . "\n" .
						$mageHelper->__("Price") . ": " . Mage::helper('core')->currency($product->getPrice(), true, false) . "\n" .
						$this->excerpt($product->getShortDescription(), 60) . "\n" .
						$mageHelper->__("Add to cart") . ": " . $this->_add2CartCmd['command'] . $product->getId();
					return $message;
				}
			}
			return null;
		}

		// FACEBOOK FUNCTIONS
		protected function listFacebookCommandsMessage()
		{
			$mageHelper = Mage::helper('core');

			$message = "\n\n" . $mageHelper->__("Command list") . ":\n";
			$replies = array(); // quick replies limit is 10 options
			$content = array();
			// some commands are commented because of the 10 limit from Facebook
			// just getting the command string, not checking the command
			if ($this->_listCategoriesCmd['command']) // 1
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_listCategoriesCmd['command'], 'payload' => str_replace(' ', '_', $this->_listCategoriesCmd['command'])));
				$message .= '"' . $this->_listCategoriesCmd['command'] . '"' . " - " . $mageHelper->__("List store categories.") . "\n";
			}
			if ($this->_searchCmd['command']) // 2
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_searchCmd['command'], 'payload' => str_replace(' ', '_', $this->_searchCmd['command'])));
				$message .= '"' . $this->_searchCmd['command'] . '"' . " - " . $mageHelper->__("Search for products.") . "\n";
			}
			if ($this->_loginCmd['command']) // 3
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_loginCmd['command'], 'payload' => str_replace(' ', '_', $this->_loginCmd['command'])));
				$message .= '"' . $this->_loginCmd['command'] . '"' . " - " . $mageHelper->__("Login into your account.") . "\n";
			}
			if ($this->_logoutCmd['command']) // 4
			{
				//array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_logoutCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_loginCmd['command'])));
				$message .= '"' . $this->_logoutCmd['command'] . '"' . " - " . $mageHelper->__("Logout from your account.") . "\n";
			}
			if ($this->_registerCmd['command']) // 5
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_registerCmd['command'], 'payload' => str_replace(' ', '_', $this->_registerCmd['command'])));
				$message .= '"' . $this->_registerCmd['command'] . '"' . " - " . $mageHelper->__("Create a new account.") . "\n";
			}
			if ($this->_listOrdersCmd['command']) // 6
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_listOrdersCmd['command'], 'payload' => str_replace(' ', '_', $this->_listOrdersCmd['command'])));
				$message .= '"' . $this->_listOrdersCmd['command'] . '"' . " - " . $mageHelper->__("List your personal orders.") . "\n";
			}
			//$message .= '"' . $chatdata->_reorderCmd['command'] . '"' . " - " . $magehelper->__("Reorder a order.") . "\n";
			//$message .= '"' . $chatdata->_add2CartCmd['command'] . '"' . " - " . $magehelper->__("Add product to cart.") . "\n";
			if ($this->_checkoutCmd['command']) // 7
			{
				//array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_checkoutCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_checkoutCmd['command'])));
				$message .= '"' . $this->_checkoutCmd['command'] . '"' . " - " . $mageHelper->__("Checkout your order.") . "\n";
			}
			if ($this->_clearCartCmd['command']) // 8
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_clearCartCmd['command'], 'payload' => str_replace(' ', '_', $this->_clearCartCmd['command'])));
				$message .= '"' . $this->_clearCartCmd['command'] . '"' . " - " . $mageHelper->__("Clear your cart.") . "\n";
			}
			if ($this->_trackOrderCmd['command']) // 9
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_trackOrderCmd['command'], 'payload' => str_replace(' ', '_', $this->_trackOrderCmd['command'])));
				$message .= '"' . $this->_trackOrderCmd['command'] . '"' . " - " . $mageHelper->__("Track your order status.") . "\n";
			}
			if ($this->_supportCmd['command']) // 10
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_supportCmd['command'], 'payload' => str_replace(' ', '_', $this->_supportCmd['command'])));
				$message .= '"' . $this->_supportCmd['command'] . '"' . " - " . $mageHelper->__("Send message to support.") . "\n";
			}
			if ($this->_sendEmailCmd['command']) // 11
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_sendEmailCmd['command'], 'payload' => str_replace(' ', '_', $this->_sendEmailCmd['command'])));
				$message .= '"' . $this->_sendEmailCmd['command'] . '"' . " - " . $mageHelper->__("Send email.") . "\n";
			}
			//$message .= '"' . $chatdata->_cancelCmd['command'] . '"' . " - " . $magehelper->__("Cancel.");
			if ($this->_aboutCmd['command']) // 12
			{
				array_push($replies, array('content_type' => 'text', 'title' => $this->_aboutCmd['command'], 'payload' => str_replace(' ', '_', $this->_aboutCmd['command'])));
				$message .= '"' . $this->_aboutCmd['command'] . '"' . " - " . $mageHelper->__("About.") . "\n";
			}
			if ($this->_helpCmd['command']) // 13
			{
				//array_push($replies, array('content_type' => 'text', 'title' => $this->_helpCmd['command'], 'payload' => str_replace(' ', '_', $this->_helpCmd['command'])));
				$message .= '"' . $this->_helpCmd['command'] . '"' . " - " . $mageHelper->__("Get help.") . "\n";
			}

			array_push($content, $message); // $content[0] -> $message
			array_push($content, $replies); // $content[1] -> $replies
			return $content;
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