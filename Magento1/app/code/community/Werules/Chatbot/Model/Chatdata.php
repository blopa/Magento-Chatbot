<?php
	// this is the main module, which contains all the data from the customer and make calls to the APIs handlers
//	require_once("Api/Telegram/Handler.php");
//	require_once("Api/Facebook/Handler.php");
//	require_once("Api/Whatsapp/Handler.php");
//	require_once("Api/WeChat/Handler.php");
	require_once("Api/witAI/witAI.php");

class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
	{
		//APIs
		protected $_apiType;
		protected $_apiKey;
		protected $_chatbotHelper;

		// WITAI
		protected $_isWitAi = false;
		protected $_witAi;

		public function _construct()
		{
			//parent::_construct();
			$this->_init('chatbot/chatdata'); // this is location of the resource file.
			$this->_chatbotHelper = Mage::helper('werules_chatbot');
		}

		// GENERAL FUNCTIONS
		public function requestHandler($action, $webhook) // handle request
		{
			// handle webhook configuration
			$chatbotHelper = $this->_chatbotHelper;
			if (!empty($webhook) && $action == $chatbotHelper->_tgBot) // set telegram webhook
			{
				$mageHelper = Mage::helper('core');
				$apiKey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
				//$telegram = new Telegram($apiKey);
				$telegram = Mage::getModel('chatbot/api_telegram_handler')->_telegram;
				$customKey = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
				//$webhookUrl = str_replace("http://", "https://", Mage::getUrl('*/*/*', array('_use_rewrite' => true, '_forced_secure' => true)));
				// replace http by https, and remove all url parameters with strok
				$webhookUrl = str_replace("http://", "https://", strtok(Mage::getUrl('chatbot/chatdata/' . $chatbotHelper->_tgBot, array('_forced_secure' => true)), '?') . "key" . DS . $customKey . DS);
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
			else if (!empty($webhook) && $action == $chatbotHelper->_fbBot) // set facebook webhook
			{
				$mageHelper = Mage::helper('core');
				$customKey = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
				// replace http by https, and remove all url parameters with strok
				$webhookUrl = str_replace("http://", "https://", strtok(Mage::getUrl('chatbot/chatdata/' . $chatbotHelper->_fbBot, array('_forced_secure' => true)), '?') . "key" . DS . $customKey . DS);

				$message = $mageHelper->__("To configure Facebook webhook access") .
					" https://developers.facebook.com/apps/(FACEBOOK_APP_ID)/webhooks/ " .
					$mageHelper->__("use your Custom Key (%s) as your Verify Token", $webhook) . " " .
					$mageHelper->__("and set the webhook URL as") . " " . $webhookUrl
				;
				return $message;
			} // start to handle conversation
			else if ($action == $chatbotHelper->_tgBot) // telegram api
			{
				// all logic goes here
				$handler = Mage::getModel('chatbot/api_telegram_handler');
				return $handler->telegramHandler();
			}
			else if ($action == $chatbotHelper->_fbBot) // facebook api
			{
				// all logic goes here
				$handler = Mage::getModel('chatbot/api_facebook_handler');
				return $handler->facebookHandler();
			}
			else
				return json_encode(array("status" => "error")); // TODO
		}

		protected function respondSuccess()
		{
			$chatbotHelper = $this->_chatbotHelper;
			if ($this->_apiType == $chatbotHelper->_tgBot)
			{
				$this->updateChatdata("telegram_processing_request", "0");
			}
			else if ($this->_apiType == $chatbotHelper->_fbBot)
			{
				$this->updateChatdata("facebook_processing_request", "0");
			}
			// TODO add other apis

			http_response_code(200);
			return json_encode(array("status" => "success"));
		}
		protected function sendEmail($text, $username)
		{
			$storeName = Mage::app()->getStore()->getName();
			$storeEmail = Mage::getStoreConfig('trans_email/ident_general/email');// TODO
			// helpers
			$mageHelper = Mage::helper('core');
			$chatbotHelper = $this->_chatbotHelper;

			$url = $mageHelper->__("Not informed");
			$customerEmail = $mageHelper->__("Not informed");
			if ($username)
				$customerName = $username;
			else
				$customerName = $mageHelper->__("Not informed");

			$mail = new Zend_Mail('UTF-8');

			if ($this->_apiType == $chatbotHelper->_tgBot)
			{
				$url = $chatbotHelper->_tgUrl . $this->getTelegramChatId();
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
			else if ($this->_apiType == $chatbotHelper->_fbBot)
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
			$chatbotHelper = $this->_chatbotHelper;
			$rep = "";
			$confPath = "";
			if ($this->_apiType == $chatbotHelper->_tgBot)
			{
				$rep = "_";
				$confPath = 'chatbot_enable/telegram_config/';
			}
			else if ($this->_apiType == $chatbotHelper->_fbBot)
			{
				$rep = " ";
				$confPath = 'chatbot_enable/facebook_config/';
			}

			$defaultCmds = explode(',', $chatbotHelper->_cmdList);
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
					$chatbotHelper->_unallowedCharacters,
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

		// TELEGRAM FUNCTIONS
		public function foreignMessageToTelegramSupport($chatId, $text, $apiName, $customerName)
		{
			//$chatdata = Mage::getModel('chatbot/chatdata');
			$chatdata = $this;
			$chatbotHelper = $this->_chatbotHelper;
//			if ($apiName == $chatbotHelper->_fbBot && $chatId)
//			{
//				$chatdata->load($chatId, 'facebook_chat_id');
//				if (is_null($chatdata->getFacebookChatId()))
//				{ // should't happen
//					$chatdata->updateChatdata("facebook_chat_id", $chatId);
//				}
//			}

			//$chatdata->_apiType = $chatbotHelper->_tgBot;
			//$telegram = $this->_telegram;
			$telegram = Mage::getModel('chatbot/api_telegram_handler')->_telegram; // TODO
			if (isset($telegram))
			{
				$mageHelper = Mage::helper('core');
				$supportgroup = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_support_group');
				if (!empty($supportgroup))
				{
					try{
						if ($supportgroup[0] == "g") // remove the 'g' from groupd id, and add '-'
							$supportgroup = "-" . ltrim($supportgroup, "g");

						if (!$customerName)
							$customerName = $mageHelper->__("Not informed");

						$message = $mageHelper->__("Message via") . " " . $apiName . ":\n" . $mageHelper->__("From") . ": " . $customerName . "\n" . $text;
						$result = $telegram->postMessage($supportgroup, $message);
						$mid = $result['result']['message_id'];
						if (!empty($mid))
						{
							$chatdata->updateChatdata("last_support_message_id", $mid);
							$chatdata->updateChatdata("last_support_chat", $apiName);
						}
					}
					catch (Exception $e){
						return false;
					}

					return true;
				}
			}

			return false;
		}

		protected function listTelegramCommandsMessage()
		{
			$chatbotHelper = $this->_chatbotHelper;
			$mageHelper = Mage::helper('core');

			$message = "\n\n" . $mageHelper->__("Command list") . ":\n";
			if ($chatbotHelper->_listCategoriesCmd['command']) $message .= $chatbotHelper->_listCategoriesCmd['command'] . " - " . $mageHelper->__("List store categories.") . "\n";
			if ($chatbotHelper->_searchCmd['command']) $message .= $chatbotHelper->_searchCmd['command'] . " - " . $mageHelper->__("Search for products.") . "\n";
			if ($chatbotHelper->_loginCmd['command']) $message .= $chatbotHelper->_loginCmd['command'] . " - " . $mageHelper->__("Login into your account.") . "\n";
			if ($chatbotHelper->_logoutCmd['command']) $message .= $chatbotHelper->_logoutCmd['command'] . " - " . $mageHelper->__("Logout from your account.") . "\n";
			if ($chatbotHelper->_registerCmd['command']) $message .= $chatbotHelper->_registerCmd['command'] . " - " . $mageHelper->__("Create a new account.") . "\n";
			if ($chatbotHelper->_listOrdersCmd['command']) $message .= $chatbotHelper->_listOrdersCmd['command'] . " - " . $mageHelper->__("List your personal orders.") . "\n";
			//$message .= $chatbotHelper->_reorderCmd['command'] . " - " . $magehelper->__("Reorder a order.") . "\n";
			//$message .= $chatbotHelper->_add2CartCmd['command'] . " - " . $magehelper->__("Add product to cart.") . "\n";
			if ($chatbotHelper->_checkoutCmd['command']) $message .= $chatbotHelper->_checkoutCmd['command'] . " - " . $mageHelper->__("Checkout your order.") . "\n";
			if ($chatbotHelper->_clearCartCmd['command']) $message .= $chatbotHelper->_clearCartCmd['command'] . " - " . $mageHelper->__("Clear your cart.") . "\n";
			if ($chatbotHelper->_trackOrderCmd['command']) $message .= $chatbotHelper->_trackOrderCmd['command'] . " - " . $mageHelper->__("Track your order status.") . "\n";
			if ($chatbotHelper->_supportCmd['command']) $message .= $chatbotHelper->_supportCmd['command'] . " - " . $mageHelper->__("Send message to support.") . "\n";
			if ($chatbotHelper->_sendEmailCmd['command']) $message .= $chatbotHelper->_sendEmailCmd['command'] . " - " . $mageHelper->__("Send email.") . "\n";
			//$message .= $chatbotHelper->_cancelCmd['command'] . " - " . $magehelper->__("Cancel.");
			if ($chatbotHelper->_helpCmd['command']) $message .= $chatbotHelper->_helpCmd['command'] . " - " . $mageHelper->__("Get help.") . "\n";
			if ($chatbotHelper->_aboutCmd['command']) $message .= $chatbotHelper->_aboutCmd['command'] . " - " . $mageHelper->__("About.") . "\n";

			return $message;
		}

		// FACEBOOK FUNCTIONS
		protected function listFacebookCommandsMessage()
		{
			$chatbotHelper = $this->_chatbotHelper;
			$mageHelper = Mage::helper('core');

			$message = "\n\n" . $mageHelper->__("Command list") . ":\n";
			$replies = array(); // quick replies limit is 10 options
			$content = array();
			// some commands are commented because of the 10 limit from Facebook
			// just getting the command string, not checking the command
			if ($chatbotHelper->_listCategoriesCmd['command']) // 1
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_listCategoriesCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_listCategoriesCmd['command'])));
				$message .= '"' . $chatbotHelper->_listCategoriesCmd['command'] . '"' . " - " . $mageHelper->__("List store categories.") . "\n";
			}
			if ($chatbotHelper->_searchCmd['command']) // 2
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_searchCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_searchCmd['command'])));
				$message .= '"' . $chatbotHelper->_searchCmd['command'] . '"' . " - " . $mageHelper->__("Search for products.") . "\n";
			}
			if ($chatbotHelper->_loginCmd['command']) // 3
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_loginCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_loginCmd['command'])));
				$message .= '"' . $chatbotHelper->_loginCmd['command'] . '"' . " - " . $mageHelper->__("Login into your account.") . "\n";
			}
			if ($chatbotHelper->_logoutCmd['command']) // 4
			{
				//array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_logoutCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_loginCmd['command'])));
				$message .= '"' . $chatbotHelper->_logoutCmd['command'] . '"' . " - " . $mageHelper->__("Logout from your account.") . "\n";
			}
			if ($chatbotHelper->_registerCmd['command']) // 5
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_registerCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_registerCmd['command'])));
				$message .= '"' . $chatbotHelper->_registerCmd['command'] . '"' . " - " . $mageHelper->__("Create a new account.") . "\n";
			}
			if ($chatbotHelper->_listOrdersCmd['command']) // 6
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_listOrdersCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_listOrdersCmd['command'])));
				$message .= '"' . $chatbotHelper->_listOrdersCmd['command'] . '"' . " - " . $mageHelper->__("List your personal orders.") . "\n";
			}
			//$message .= '"' . $chatbotHelper->_reorderCmd['command'] . '"' . " - " . $magehelper->__("Reorder a order.") . "\n";
			//$message .= '"' . $chatbotHelper->_add2CartCmd['command'] . '"' . " - " . $magehelper->__("Add product to cart.") . "\n";
			if ($chatbotHelper->_checkoutCmd['command']) // 7
			{
				//array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_checkoutCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_checkoutCmd['command'])));
				$message .= '"' . $chatbotHelper->_checkoutCmd['command'] . '"' . " - " . $mageHelper->__("Checkout your order.") . "\n";
			}
			if ($chatbotHelper->_clearCartCmd['command']) // 8
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_clearCartCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_clearCartCmd['command'])));
				$message .= '"' . $chatbotHelper->_clearCartCmd['command'] . '"' . " - " . $mageHelper->__("Clear your cart.") . "\n";
			}
			if ($chatbotHelper->_trackOrderCmd['command']) // 9
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_trackOrderCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_trackOrderCmd['command'])));
				$message .= '"' . $chatbotHelper->_trackOrderCmd['command'] . '"' . " - " . $mageHelper->__("Track your order status.") . "\n";
			}
			if ($chatbotHelper->_supportCmd['command']) // 10
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_supportCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_supportCmd['command'])));
				$message .= '"' . $chatbotHelper->_supportCmd['command'] . '"' . " - " . $mageHelper->__("Send message to support.") . "\n";
			}
			if ($chatbotHelper->_sendEmailCmd['command']) // 11
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_sendEmailCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_sendEmailCmd['command'])));
				$message .= '"' . $chatbotHelper->_sendEmailCmd['command'] . '"' . " - " . $mageHelper->__("Send email.") . "\n";
			}
			//$message .= '"' . $chatbotHelper->_cancelCmd['command'] . '"' . " - " . $magehelper->__("Cancel.");
			if ($chatbotHelper->_aboutCmd['command']) // 12
			{
				array_push($replies, array('content_type' => 'text', 'title' => $chatbotHelper->_aboutCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_aboutCmd['command'])));
				$message .= '"' . $chatbotHelper->_aboutCmd['command'] . '"' . " - " . $mageHelper->__("About.") . "\n";
			}
			if ($chatbotHelper->_helpCmd['command']) // 13
			{
				//array_push($replies, array('content_type' => 'text', 'title' => $this->_helpCmd['command'], 'payload' => str_replace(' ', '_', $chatbotHelper->_helpCmd['command'])));
				$message .= '"' . $chatbotHelper->_helpCmd['command'] . '"' . " - " . $mageHelper->__("Get help.") . "\n";
			}

			array_push($content, $message); // $content[0] -> $message
			array_push($content, $replies); // $content[1] -> $replies
			return $content;
		}
	}