<?php
// class that handles all Facebook requests.
	require_once("Messenger.php");
	//$api_path = Mage::getModuleDir('', 'Werules_Chatbot') . DS . "Model" . DS . "Api" . DS . "witAI" . DS;
	//require_once($api_path . "witAI.php");

	class MessengerBot extends Messenger
	{
		public $_originalText;
		public $_referral;
		public $_recipientId;
		public $_chatId;
		public $_messageId;
		public $_audioPath;
		public $_isPayload = false;

		public function postMessage($chatId, $message)
		{
			return $this->sendMessage($chatId, $message);
		}
	}

	class Werules_Chatbot_Model_Api_Facebook_Handler extends Werules_Chatbot_Model_Chatdata
	{
		protected $_facebook;

		public function _construct()
		{
			//parent::_construct();
			//$this->_init('chatbot/api_facebook_handler'); // this is location of the resource file.
			$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
			$this->_facebook = new MessengerBot($apikey);
			$this->_chatbotHelper = Mage::helper('werules_chatbot');
		}

		public function foreignMessageFromSupport($chatId, $text)
		{
			// helpers
			$mageHelper = Mage::helper('core');
			$chatbotHelper = $this->_chatbotHelper;
			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata');
			$chatdata->load($chatId, 'facebook_chat_id');
			$chatdata->_apiType = $chatbotHelper->_fbBot;

			if (is_null($chatdata->getFacebookChatId()))
			{ // should't happen
				return false;
			}

			//$chatdata->_apiType = $chatbotHelper->_fbBot;
			$facebook = $this->_facebook;
			if (isset($facebook))
			{
				$message = $mageHelper->__("Message from support") . ":\n" . $text;
				$facebook->postMessage($chatId, $message);
				return true;
			}

			return false;
		}

		public function facebookHandler()
		{
			// Instances the Facebook class
			$facebook = $this->_facebook;

			if (!isset($facebook)) // if no apiKey available, break process
				return json_encode(array("status" => "error"));

			// hub challenge
			$hubToken = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
			$verify = $facebook->verifyWebhook($hubToken);
			if ($verify)
				return $verify;

			// Take text and chat_id from the message
			$facebook->_originalText = $facebook->Text();
			$facebook->_chatId = $facebook->ChatID();
			$facebook->_messageId = $facebook->MessageID();
			$isEcho = $facebook->getEcho();

			// helper
			$mageHelper = Mage::helper('core');

			$enableLog = Mage::getStoreConfig('chatbot_enable/general_config/enable_post_log');
			if ($enableLog == "1") // log all posts
				Mage::log("Post Data:\n" . var_export($facebook->RawData(), true) . "\n\n", null, 'chatbot_facebook.log');

			$appId = $facebook->getAppId();
			$canDisableBot = Mage::getStoreConfig('chatbot_enable/facebook_config/option_disable_bot');
			if (($isEcho == "true") && ($canDisableBot == "1"))
			{
				if (empty($appId)) // dosen't have an app id, so it's a human reply using the page
				{
					$facebook->_recipientId = $facebook->RecipientID();
					$facebook->_originalText = $mageHelper->__("Bot respond is disabled for now because the customer is being replied by a human.");
				}
			}

			$referral = $facebook->getReferralRef();
			if (!empty($referral)) // opened m.me with referral
			{
				$facebook->_referral = $referral;
				$facebook->_originalText = $mageHelper->__("Hi");
			}

			// checking for payload
			$payloadContent = $facebook->getPayload();
			if (!empty($payloadContent) && empty($facebook->_originalText))
			{
				$facebook->_isPayload = true;
				$facebook->_originalText = $payloadContent;
				$facebook->_messageId = $facebook->getMessageTimestamp();
			}

			// quickreply payload
			if (empty($payloadContent))
			{
				$payloadContent = $facebook->getQuickReplyPayload();
				if (!empty($payloadContent))
				{
					//$facebook->_isPayload = true; // just replace original text as payload and it will work
					$facebook->_originalText = $payloadContent;
					$facebook->_messageId = $facebook->getMessageTimestamp();
				}
			}

			if (!empty($facebook->_originalText) && !empty($facebook->_chatId) && ($isEcho != "true" || isset($facebook->_recipientId) || isset($facebook->_referral)))
			{
				return $this->processText();
			}

			return $facebook->respondSuccess();
		}

		public function processText()
		{
			// configs
			//$enableWitai = Mage::getStoreConfig('chatbot_enable/witai_config/enable_witai');
			$enabledBot = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_bot');
			$enableReplies = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_default_replies');
			$enablePredict = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_predict_commands');
			$enableEmptyCategoriesListing = Mage::getStoreConfig('chatbot_enable/general_config/list_empty_categories');
			$enableFinalMessage2Support = Mage::getStoreConfig('chatbot_enable/general_config/enable_support_final_message');
			$supportGroupId = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_support_group');
			$showMore = 0;
			$moreOrders = false;
			$defaultConfidence = 75;
			$listingLimit = 5;
			$listMoreCategories = "show_more_list_cat_";
			$listMoreSearch = "show_more_search_prod_";
			$listMoreOrders = "show_more_order_";
			$replyToCustomerMessage = "reply_to_message";
			$message = "";
			$messageLimit = 640; // Messenger API limit
			$minutes = 1 * 60 * 1000; // 1 minute

			// instance Facebook API
			$facebook = $this->_facebook;

			// Take text and chat_id from the message
			$originalText = $facebook->_originalText;
			$chatId = $facebook->_chatId;
			$messageId = $facebook->_messageId;
			$isPayload = $facebook->_isPayload;
			$text = strtolower($originalText);
			$recipientId = $facebook->_recipientId;

			if (isset($recipientId)) // it's only set when a human respond on the facebook page
				$chatId = $recipientId;

			// Instances facebook user details
			$userData = $facebook->UserData($chatId);
			$username = null;
			if (!empty($userData))
				$username = $userData['first_name'];

			// helpers
			$mageHelper = Mage::helper('core');
			$chatbotHelper = $this->_chatbotHelper;

			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata')->load($chatId, 'facebook_chat_id');
			$chatdata->_apiType = $chatbotHelper->_fbBot;

			//$chatdata->updateChatdata("facebook_processing_request", "0"); // DEBUG
			if ($chatdata->getFacebookProcessingRequest() == "1") // avoid responding to multiple messages in a row
			{
				$updatedAt = strtotime($chatdata->getUpdatedAt());
				$timeNow = time();
				if (($timeNow - $updatedAt) < $minutes)
					return $facebook->respondSuccess();
				else
					$chatdata->updateChatdata("facebook_processing_request", "0");
			}

			if ($chatdata->getFacebookChatId()) // flag that is processing a request
				$chatdata->updateChatdata("facebook_processing_request", "1");


			// Instances the witAI class
			$enableWitai = Mage::getStoreConfig('chatbot_enable/witai_config/enable_witai');
			if ($enableWitai == "1")
			{
				if (!isset($this->_witAi))
				{
					$witApi = Mage::getStoreConfig('chatbot_enable/witai_config/witai_api_key');
					$this->_witAi = new witAI($witApi);
				}

				if (!is_null($facebook->_audioPath))
				{
					$witResponse = $this->_witAi->getAudioResponse($facebook->_audioPath);
					if (isset($witResponse->_text))
						$facebook->_originalText = $witResponse->_text;
					else
						return $chatdata->respondSuccess();
				}
			}

			if ($chatdata->getEnableFacebookAdmin() == "0") // disabled by admin
				return $chatdata->respondSuccess();

			if (isset($recipientId)) // it's only set when a human respond on the facebook page
			{
				$chatdata->updateChatdata('enable_facebook_admin', "0");
				//$text = $mageHelper->__("Bot respond is disabled for now because the customer is being replied by a human.");
				$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_supportState); // force update state to support mode
				//return $chatdata->respondSuccess();
			}

			if ($messageId == $chatdata->getFacebookMessageId() && !($this->_isWitAi)) // prevents to reply the same request twice
				return $chatdata->respondSuccess();
			else if ($chatdata->getFacebookChatId())
				$chatdata->updateChatdata('facebook_message_id', $messageId); // if this fails, it may send the same message twice

			// bot enabled/disabled
			if ($enabledBot != "1")
			{
				$disabledMessage = Mage::getStoreConfig('chatbot_enable/facebook_config/disabled_message');
				if (!empty($disabledMessage))
					$facebook->postMessage($chatId, $disabledMessage);
				return $chatdata->respondSuccess();
			}

			// send feedback to user
			$facebook->sendChatAction($chatId, "typing_on");

			// payload handler, may change the conversation state
			if ($chatdata->getFacebookConvState() == $chatbotHelper->_listProductsState || $chatdata->getFacebookConvState() == $chatbotHelper->_listOrdersState) // listing products
			{
				if ($chatbotHelper->startsWith($text, $listMoreCategories)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listCategoriesState))
					{
						$value = $chatbotHelper->getCommandValue($text, $listMoreCategories);
						$arr = explode(",", $value);
						$text = $arr[0];
						$showMore = (int)$arr[1];
					}
				}
				else if ($chatbotHelper->startsWith($text, $listMoreSearch)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_searchState))
					{
						$value = $chatbotHelper->getCommandValue($text, $listMoreSearch);
						$arr = explode(",", $value);
						$text = $arr[0];
						$showMore = (int)$arr[1];
					}
				}
				else if ($chatbotHelper->startsWith($text, $listMoreOrders)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listOrdersState))
					{
						$value = $chatbotHelper->getCommandValue($text, $listMoreOrders);
						$showMore = (int)$value; // get where listing stopped
						$moreOrders = true;
					}
				}
//					else
//						$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState);
			}

			// instances conversation state
			$conversationState = $chatdata->getFacebookConvState();

			// init error message
			$chatbotHelper->_errorMessage = $mageHelper->__("Something went wrong, please try again.");

			// handle admin stuff
			//$isAdmin = $chatdata->getIsAdmin();
			// if it's the admin chat id
			if ($chatId == $supportGroupId)// || $isAdmin == "1")
			{
//					if ($isAdmin == "0") // set user as admin
//						$chatdata->updateChatdata('is_admin', "1");

				if ($conversationState == $chatbotHelper->_replyToSupportMessageState) // check if admin is replying to a customer
				{
					$customerChatId = $chatdata->getFacebookSupportReplyChatId(); // get customer chat id
					if (!empty($customerChatId))
					{
						$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState); // set admin to _startState
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model

						if ($customerData->getFacebookConvState() != $chatbotHelper->_supportState) // if user isn't on support, switch to support
						{
							// TODO IMPORTANT remember to switch off all other supports
							$customerData->updateChatdata('facebook_conv_state', $chatbotHelper->_supportState);
							$facebook->postMessage($customerChatId, $mageHelper->__("You're now on support mode."));
						}
						$facebook->postMessage($customerChatId, $mageHelper->__("Message from support") . ":\n" . $text); // send message to customer TODO
						$facebook->postMessage($chatId, $mageHelper->__("Message sent."));
					}
					return $chatdata->respondSuccess();
				}
				else if ($chatbotHelper->startsWith($text, $chatbotHelper->_admSendMessage2AllCmd)) // old checkCommandWithValue
				{
					$message = trim($chatbotHelper->getCommandValue($text, $chatbotHelper->_admSendMessage2AllCmd));
					if (!empty($message))
					{
						$chatbotcollection = Mage::getModel('chatbot/chatdata')->getCollection();
						foreach($chatbotcollection as $chatbot)
						{
							$fbChatId = $chatbot->getFacebookChatId();
							if ($fbChatId)
								$facebook->postMessage($fbChatId, $message); // $magehelper->__("Message from support") . ":\n" .
						}
						$facebook->postMessage($chatId, $mageHelper->__("Message sent."));
					}
					else
						$facebook->postMessage($chatId, $mageHelper->__("Please use") . ' "' . $chatbotHelper->_admSendMessage2AllCmd . " " . $mageHelper->__("your message here.") . '"');
				}
				else if ($isPayload)
				{
					if ($chatbotHelper->startsWith($text, $chatbotHelper->_admEndSupportCmd)) // finish customer support  // old checkCommandWithValue
					{
						$customerChatId = trim($chatbotHelper->getCommandValue($text, $chatbotHelper->_admEndSupportCmd)); // get customer chatId from payload
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model
						$customerData->updateChatdata('facebook_conv_state', $chatbotHelper->_startState); // update conversation state

						$facebook->postMessage($chatId, $mageHelper->__("Done. The customer is no longer on support."));
						$facebook->postMessage($customerChatId, $mageHelper->__("Support ended."));
					}
					else if ($chatbotHelper->startsWith($text, $chatbotHelper->_admBlockSupportCmd)) // block user from using support // old checkCommandWithValue
					{
						$customerChatId = trim($chatbotHelper->getCommandValue($text, $chatbotHelper->_admBlockSupportCmd)); // get customer chatId from payload
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model
						if (!is_null($customerData->getFacebookChatId()))
						{
							if ($customerData->getEnableSupport() == "1")
							{
								$customerData->updateChatdata('enable_support', "0"); // disable support
								$facebook->postMessage($chatId, $mageHelper->__("Done. The customer is no longer able to enter support."));
							}
							else //if ($customerData->getEnableSupport() == "0")
							{
								$customerData->updateChatdata('enable_support', "1"); // enable support
								$facebook->postMessage($chatId, $mageHelper->__("Done. The customer is now able to enter support."));
							}
						}
						else
							$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);

					}
					else if ($chatbotHelper->startsWith($text, $replyToCustomerMessage)) // old checkCommandWithValue
					{
						$customerChatId = trim($chatbotHelper->getCommandValue($text, $replyToCustomerMessage)); // get customer chatId from payload
						$chatdata->updateChatdata('facebook_support_reply_chat_id', $customerChatId);
						$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_replyToSupportMessageState);

						$facebook->postMessage($chatId, $mageHelper->__("Ok, send me the message and I'll forward it to the customer."));
					}
					else if ($chatbotHelper->startsWith($text, $chatbotHelper->_admEnableBotCmd)) // old checkCommandWithValue
					{
						$customerChatId = trim($chatbotHelper->getCommandValue($text, $chatbotHelper->_admEnableBotCmd)); // get customer chatId from payload
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model

						if (!is_null($customerData->getFacebookChatId()))
						{
							if ($customerData->getEnableFacebookAdmin() == "1")
							{
								$customerData->updateChatdata('enable_facebook_admin', "0"); // disable bot response
								$facebook->postMessage($chatId, $mageHelper->__("Done. The bot will no longer send messages to this customer."));
							}
							else //if ($customerData->getEnableFacebookAdmin() == "0")
							{
								$customerData->updateChatdata('enable_facebook_admin', "1"); // enable bot response
								$facebook->postMessage($chatId, $mageHelper->__("Done. The bot will now start sending messages to this customer."));
							}
						}
						else
							$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					}
					else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_admSendMessage2AllCmd)) // TODO
					{

					}

					return $chatdata->respondSuccess();
				}
			}

			// ALL CUSTOMER HANDLERS GOES AFTER HERE

			if ($chatdata->getIsLogged() == "1") // check if customer is logged
			{
				if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId()) // if is a valid customer id
				{
					if ($chatdata->getEnableFacebook() != "1")
					{
						$facebook->postMessage($chatId, $mageHelper->__("To talk with me, please enable Facebook Messenger on your account chatbot settings."));
						$facebook->sendChatAction($chatId, "typing_off");
						return $chatdata->respondSuccess();
					}
				}
			}

			$blockerStates = (
				$conversationState == $chatbotHelper->_listCategoriesState ||
				$conversationState == $chatbotHelper->_searchState ||
				$conversationState == $chatbotHelper->_supportState ||
				$conversationState == $chatbotHelper->_sendEmailState ||
				$conversationState == $chatbotHelper->_trackOrderState
			);

			// user isnt registred HERE
			if (is_null($chatdata->getFacebookChatId())) // if user isn't registred
			{
				$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_welcome_msg'); // TODO
				if (!empty($message)) // TODO
				{
					if ($username)
						$message = str_replace("{customername}", $username, $message);
					$facebook->postMessage($chatId, $message);
				}
				try
				{
					$hash = substr(md5(uniqid($chatId, true)), 0, 150); // TODO
					$chatdata // using magento model to insert data into database the proper way
					->setFacebookChatId($chatId)
						->setHashKey($hash) // TODO
						->setCreatedAt(date('Y-m-d H:i:s'))
						->save();
					//$chatdata->updateChatdata('facebook_chat_id', $chatId);
					//$chatdata->updateChatdata('hash_key', $hash);
				}
				catch (Exception $e)
				{
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage); // TODO
				}
				//$facebook->sendChatAction($chatId, "typing_off");
				//return $chatdata->respondSuccess(); // commented to keep processing the message
			}

			// referral handler
			if (isset($facebook->_referral))
			{
				$refMessage = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_referral_message');
				if (!empty($refMessage) && empty($message)) // only if haven't sent the welcome message
				{
					if ($username)
						$refMessage = str_replace("{customername}", $username, $refMessage);
					$facebook->postMessage($chatId, $refMessage);
				}
				$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState); // back to start state
				return $chatdata->respondSuccess();
			}

			// init commands
			//$chatbotHelper->_startCmd['command'] = "Start";
			$chatbotHelper->_listCategoriesCmd = $chatdata->getCommandString(1);
			$chatbotHelper->_searchCmd = $chatdata->getCommandString(2);
			$chatbotHelper->_loginCmd = $chatdata->getCommandString(3);
			$chatbotHelper->_listOrdersCmd = $chatdata->getCommandString(4);
			$chatbotHelper->_reorderCmd = $chatdata->getCommandString(5);
			$chatbotHelper->_add2CartCmd = $chatdata->getCommandString(6);
			$chatbotHelper->_checkoutCmd = $chatdata->getCommandString(7);
			$chatbotHelper->_clearCartCmd = $chatdata->getCommandString(8);
			$chatbotHelper->_trackOrderCmd = $chatdata->getCommandString(9);
			$chatbotHelper->_supportCmd = $chatdata->getCommandString(10);
			$chatbotHelper->_sendEmailCmd = $chatdata->getCommandString(11);
			$chatbotHelper->_cancelCmd = $chatdata->getCommandString(12);
			$chatbotHelper->_helpCmd = $chatdata->getCommandString(13);
			$chatbotHelper->_aboutCmd = $chatdata->getCommandString(14);
			$chatbotHelper->_logoutCmd = $chatdata->getCommandString(15);
			$chatbotHelper->_registerCmd = $chatdata->getCommandString(16);
			if (!$chatbotHelper->_cancelCmd['command']) $chatbotHelper->_cancelCmd['command'] = "cancel"; // it must always have a cancel command

			// init messages
			$chatbotHelper->_cancelMessage = $mageHelper->__("To cancel, send") . ' "' . $chatbotHelper->_cancelCmd['command'] . '"';
			$chatbotHelper->_canceledMessage = $mageHelper->__("Ok, canceled.");
			$chatbotHelper->_loginFirstMessage = $mageHelper->__("Please login first.");
			array_push($chatbotHelper->_positiveMessages, $mageHelper->__("Ok"), $mageHelper->__("Okay"), $mageHelper->__("Cool"), $mageHelper->__("Awesome"));
			// $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)]

			if ($enablePredict == "1" && !$isPayload) // prediction is enabled and itsn't payload
			{
				if ($conversationState == $chatbotHelper->_startState)
				{
					$cmdarray = array(
						$chatbotHelper->_startCmd['command'],
						$chatbotHelper->_listCategoriesCmd['command'],
						$chatbotHelper->_searchCmd['command'],
						$chatbotHelper->_loginCmd['command'],
						$chatbotHelper->_listOrdersCmd['command'],
						$chatbotHelper->_reorderCmd['command'],
						$chatbotHelper->_add2CartCmd['command'],
						$chatbotHelper->_checkoutCmd['command'],
						$chatbotHelper->_clearCartCmd['command'],
						$chatbotHelper->_trackOrderCmd['command'],
						$chatbotHelper->_supportCmd['command'],
						$chatbotHelper->_sendEmailCmd['command'],
						$chatbotHelper->_cancelCmd['command'],
						$chatbotHelper->_helpCmd['command'],
						$chatbotHelper->_aboutCmd['command'],
						$chatbotHelper->_logoutCmd['command'],
						$chatbotHelper->_registerCmd['command']
					);

					foreach ($cmdarray as $cmd)
					{
						if (strpos($text, $cmd) !== false)
						{
							$text = $cmd;
							break;
						}
					}
				}
			}
			
			// help command
			if ($chatbotHelper->checkCommand($text, $chatbotHelper->_helpCmd))
			{
				$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_help_msg'); // TODO
				if (!empty($message)) // TODO
				{
					$facebook->postMessage($chatId, $message);
					$cmdListing = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_help_command_list');
					if ($cmdListing == "1")
					{
						$content = $chatdata->listFacebookCommandsMessage();
						$facebook->sendQuickReply($chatId, $content[0], $content[1]);
					}
				}

				$facebook->sendChatAction($chatId, "typing_off");
				return $chatdata->respondSuccess();
			}

			// about command
			if ($chatbotHelper->checkCommand($text, $chatbotHelper->_aboutCmd))
			{
				$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_about_msg'); // TODO
				if (!empty($message))
					$facebook->postMessage($chatId, $message);

				$facebook->sendChatAction($chatId, "typing_off");
				return $chatdata->respondSuccess();
			}

			// cancel command
			if ($chatbotHelper->checkCommand($text, $chatbotHelper->_cancelCmd))
			{
				if ($conversationState == $chatbotHelper->_listCategoriesState)
				{
					$message = $chatbotHelper->_canceledMessage;
				}
				else if ($conversationState == $chatbotHelper->_supportState)
				{
					$message = $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("exiting support mode.");
				}
				else if ($conversationState == $chatbotHelper->_searchState)
				{
					$message = $chatbotHelper->_canceledMessage;
				}
				else if ($conversationState == $chatbotHelper->_sendEmailState)
				{
					$message = $chatbotHelper->_canceledMessage;
				}
				else if ($conversationState == $chatbotHelper->_listProductsState)
				{
					$message = $chatbotHelper->_canceledMessage;
				}
				else if ($conversationState == $chatbotHelper->_listOrdersState)
				{
					$message = $chatbotHelper->_canceledMessage;
				}
				else
					$message = $chatbotHelper->_errorMessage;

				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else
					$facebook->postMessage($chatId, $message);
				$facebook->sendChatAction($chatId, "typing_off");
				return $chatdata->respondSuccess();
			}

			// add2cart commands
			if ($chatbotHelper->startsWith($text, $chatbotHelper->_add2CartCmd['command'])) // ignore alias // old checkCommandWithValue
			{
				$errorFlag = false;
				$notInStock = false;
				$cmdvalue = $chatbotHelper->getCommandValue($text, $chatbotHelper->_add2CartCmd['command']);
				if ($cmdvalue) // TODO
				{
					$product = Mage::getModel('catalog/product')->load($cmdvalue);
					if ($product->getId())
					{
						$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();
						if ($stock > 0)
						{
							$productName = $product->getName();
							if (empty($productName))
								$productName = $mageHelper->__("this product");
							$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("adding %s to your cart.", $productName));
							$facebook->sendChatAction($chatId, "typing_on");
							if ($chatdata->addProd2Cart($cmdvalue))
								$facebook->postMessage($chatId, $mageHelper->__("Added. To checkout send") . ' "' . $chatbotHelper->_checkoutCmd['command'] . '"');
							else
								$errorFlag = true;
						}
						else
							$notInStock = true;
					}
					else
						$errorFlag = true;
				}
				else
					$errorFlag = true;

				if ($errorFlag)
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else if ($notInStock)
					$facebook->postMessage($chatId, $mageHelper->__("This product is not in stock."));

				return $chatdata->respondSuccess();
			}

			// states
			if ($conversationState == $chatbotHelper->_listCategoriesState) // TODO show only in stock products
			{
				$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);

				if ($_category)
					$categoryName = $_category->getName();
				else
					$categoryName = $mageHelper->__("this caytegory");

				if ($showMore == 0) // show only in the first time
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather all products from %s for you.", $categoryName));
				else
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

				$facebook->sendChatAction($chatId, "typing_on");
				$errorFlag = false;
				if ($_category) // check if variable isn't false/empty
				{
					if ($_category->getId()) // check if is a valid category
					{
						$noProductFlag = false;
						$productCollection = $_category->getProductCollection()
							->addAttributeToSelect('*')
							->addAttributeToFilter('visibility', 4)
							->addAttributeToFilter('type_id', 'simple');
						Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
						$productIDs = $productCollection->getAllIds();

						$elements = array();
						if ($productIDs)
						{
							$i = 0;
							$total = count($productIDs);

							if ($showMore < $total)
							{
								if ($showMore == 0)
								{
									if ($total == 1)
										$facebook->postMessage($chatId, $mageHelper->__("Done. This category has only one product."));
									else
										$facebook->postMessage($chatId, $mageHelper->__("Done. This category has %s products.", $total));
								}

								$placeholder = Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
								foreach ($productIDs as $productID)
								{
									if ($i >= $showMore)
									{
										$product = Mage::getModel('catalog/product')->load($productID);
										$productUrl = $product->getProductUrl();
										$productImage = $product->getImageUrl();
										if (empty($productImage))
											$productImage = $placeholder;

										$button = array(
											array(
												'type' => 'postback',
												'title' => $mageHelper->__("Add to cart"),
												'payload' => $chatbotHelper->_add2CartCmd['command'] . $productID
											),
											array(
												'type' => 'web_url',
												'url' => $productUrl,
												'title' => $mageHelper->__("Visit product's page")
											)
										);
										$element = array(
											'title' => $product->getName(),
											'item_url' => $productUrl,
											'image_url' => $productImage,
											'subtitle' => $chatbotHelper->excerpt($product->getShortDescription(), 60),
											'buttons' => $button
										);
										array_push($elements, $element);

										if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
										{
											// TODO add option to list more products
											$button = array(
												array(
													'type' => 'postback',
													'title' => $mageHelper->__("Show more"),
													'payload' => $listMoreCategories . $text . "," . (string)($i + 1)
												)
											);
											$element = array(
												'title' => Mage::app()->getStore()->getName(),
												'item_url' => Mage::getBaseUrl(),
												'image_url' => $placeholder,
												'subtitle' => $chatbotHelper->excerpt(Mage::getStoreConfig('design/head/default_description'), 60),
												'buttons' => $button
											);
											array_push($elements, $element);
											if ($chatdata->getFacebookConvState() != $chatbotHelper->_listProductsState)
												if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listProductsState))
													$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
											break;
										}
										else if (($i + 1) == $total) // if it's the last one, back to _startState
										{
											//$facebook->postMessage($chatId, $mageHelper->__("And that was the last one.")); // uneeded message for facebook
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
												$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
										}
									}
									$i++;
								}
								if ($i == 0)
									$noProductFlag = true;
							}
							else
								$errorFlag = true;
						}
						else
							$noProductFlag = true;

						if ($noProductFlag)
							$facebook->postMessage($chatId, $mageHelper->__("Sorry, no products found in this category."));
						else
							$facebook->sendGenericTemplate($chatId, $elements);
					}
					else
						$errorFlag = true;
				}
				else
					$errorFlag = true;

				if ($errorFlag)
				{
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState);
				}
				return $chatdata->respondSuccess();
			}
			else if ($conversationState == $chatbotHelper->_searchState)
			{
				if ($showMore == 0) // show only in the first time
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I search for '%s' for you.", $text));
				else
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

				$facebook->sendChatAction($chatId, "typing_on");
				$errorFlag = false;
				$noProductFlag = false;
				$productIDs = $chatbotHelper->getProductIdsBySearch($text);
				$elements = array();
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
				{
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					return $chatdata->respondSuccess();
				}
				else if ($productIDs)
				{
					$i = 0;
					$total = count($productIDs);
					if ($showMore < $total)
					{
						if ($showMore == 0)
						{
							if ($total == 1)
								$facebook->postMessage($chatId, $mageHelper->__("Done. I've found only one product for your criteria."));
							else
								$facebook->postMessage($chatId, $mageHelper->__("Done. I've found %s products for your criteria.", $total));
						}

						$placeholder = Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
						foreach ($productIDs as $productID)
						{
							$message = $chatbotHelper->prepareFacebookProdMessages($productID);
							//Mage::helper('core')->__("Add to cart") . ": " . $this->_add2CartCmd['command'] . $product->getId();
							if (!empty($message)) // TODO
							{
								if ($i >= $showMore)
								{
									$product = Mage::getModel('catalog/product')->load($productID);
									$productUrl = $product->getProductUrl();
									$productImage = $product->getImageUrl();
									if (empty($productImage))
										$productImage = $placeholder;

									$button = array(
										array(
											'type' => 'postback',
											'title' => $mageHelper->__("Add to cart"),
											'payload' => $chatbotHelper->_add2CartCmd['command'] . $productID
										),
										array(
											'type' => 'web_url',
											'url' => $productUrl,
											'title' => $mageHelper->__("Visit product's page")
										)
									);
									$element = array(
										'title' => $product->getName(),
										'item_url' => $productUrl,
										'image_url' => $productImage,
										'subtitle' => $chatbotHelper->excerpt($product->getShortDescription(), 60),
										'buttons' => $button
									);
									array_push($elements, $element);

									if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
									{
										// TODO add option to list more products
										$button = array(
											array(
												'type' => 'postback',
												'title' => $mageHelper->__("Show more"),
												'payload' => $listMoreSearch . $text . "," . (string)($i + 1)
											)
										);
										$element = array(
											'title' => Mage::app()->getStore()->getName(),
											'item_url' => Mage::getBaseUrl(),
											'image_url' => $placeholder,
											'subtitle' => $chatbotHelper->excerpt(Mage::getStoreConfig('design/head/default_description'), 60),
											'buttons' => $button
										);
										array_push($elements, $element);
										if ($chatdata->getFacebookConvState() != $chatbotHelper->_listProductsState)
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listProductsState))
												$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
										break;
									}
									else if (($i + 1) == $total) // if it's the last one, back to _startState
									{
										//$facebook->postMessage($chatId, $mageHelper->__("And that was the last one.")); // uneeded message for facebook
										if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
											$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
									}
								}
								$i++;
							}
						}
						if ($i == 0)
							$noProductFlag = true;
					}
					else
						$errorFlag = true;
				}
				else
					$noProductFlag = true;

				if ($noProductFlag)
					$facebook->postMessage($chatId, $mageHelper->__("Sorry, no products found for this criteria."));
				else if ($errorFlag)
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listProductsState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else
					$facebook->sendGenericTemplate($chatId, $elements);

				return $chatdata->respondSuccess();
			}
			else if ($conversationState == $chatbotHelper->_supportState)
			{
				$errorFlag = true;
				if (!empty($supportGroupId))
				{
					if ($supportGroupId == $chatbotHelper->_tgBot)
					{
						if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chatId, $originalText, $chatdata->_apiType, $username)) // send chat id, original text, "facebook" and username
							$errorFlag = false;
					}
					else // probably have the admin chat id set
					{
						if (!isset($recipientId)) // it's only set when a human respond on the facebook page
						{
							$buttons = array(
								array(
									'type' => 'postback',
									'title' => $mageHelper->__("End support"),
									'payload' => $chatbotHelper->_admEndSupportCmd . $chatId

								),
								array(
									'type' => 'postback',
									'title' => $mageHelper->__("Enable/Disable support"),
									'payload' => $chatbotHelper->_admBlockSupportCmd . $chatId

								),
								array(
									'type' => 'postback',
									'title' => $mageHelper->__("Reply this message"),
									'payload' => $replyToCustomerMessage . $chatId

								)
							);
							$message = $mageHelper->__("From") . ": " . $username . "\n" . $mageHelper->__("ID") . ": " . $chatId . "\n" . $text;
						}
						else // if a human is responding
						{
							$buttons = array(
								array(
									'type' => 'postback',
									'title' => $mageHelper->__("Enable/Disable Bot Replies"),
									'payload' => $chatbotHelper->_admEnableBotCmd . $chatId
								)
							);
							$message = $text;
						}

						$facebook->sendButtonTemplate($supportGroupId, $message, $buttons);
						$errorFlag = false;
					}
				}

				if ($errorFlag)
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else if (!isset($recipientId)) // it's only set when a human respond on the facebook page
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("we have sent your message to support."));
				return $chatdata->respondSuccess();
			}
			else if ($conversationState == $chatbotHelper->_sendEmailState)
			{
				$facebook->postMessage($chatId, $mageHelper->__("Trying to send the email..."));
				if ($chatdata->sendEmail($text, $username))
				{
					$facebook->postMessage($chatId, $mageHelper->__("Done."));
				}
				else
					$facebook->postMessage($chatId, $mageHelper->__("Sorry, I wasn't able to send an email this time. Please try again later."));
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				return $chatdata->respondSuccess();
			}
			else if ($conversationState == $chatbotHelper->_trackOrderState)
			{
				$errorFlag = false;
				if ($chatdata->getIsLogged() == "1")
				{
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I check the status for order %s.", $text));
					$facebook->sendChatAction($chatId, "typing_on");
					$order = Mage::getModel('sales/order')->loadByIncrementId($text);
					if ($order->getId())
					{
						if ($order->getCustomerId() == $chatdata->getCustomerId()) // not a problem if customer dosen't exist
						{
							$facebook->postMessage($chatId, $mageHelper->__("Your order status is") . " " . $mageHelper->__($order->getStatus()));
						}
						else
							$errorFlag = true;
					}
					else
						$errorFlag = true;
				}
				else
					$facebook->postMessage($chatId, $chatbotHelper->_loginFirstMessage);
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else if ($errorFlag)
					$facebook->postMessage($chatId, $mageHelper->__("Sorry, we couldn't find any order with this information."));
				return $chatdata->respondSuccess();
			}

			//general commands
			if ($chatbotHelper->checkCommand($text, $chatbotHelper->_listCategoriesCmd))
			{
				$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather all categories for you."));
				$facebook->sendChatAction($chatId, "typing_on");

				$categoryHelper = Mage::helper('catalog/category');
				$categories = $categoryHelper->getStoreCategories(); // TODO test with a store without categories
				$i = 0;
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listCategoriesState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else if ($categories)
				{
					$replies = array();
					foreach ($categories as $_category) // TODO fix buttons max size
					{
						//array_push($option, $_category->getName());
						if ($enableEmptyCategoriesListing != "1") // unallow empty categories listing
						{
							$category = Mage::getModel('catalog/category')->load($_category->getId()); // reload category because EAV Entity
							$productIDs = $category->getProductCollection()
								->addAttributeToSelect('*')
								->addAttributeToFilter('visibility', 4)
								->addAttributeToFilter('type_id', 'simple')
								->getAllIds()
							;
						}
						else
							$productIDs = true;
						if (!empty($productIDs)) // category with no products
						{
							$catName = $_category->getName();
							if (!empty($catName))
							{
								$reply = array(
									'content_type' => 'text',
									'title' => $catName,
									'payload' => $catName// 'list_category_' . $_category->getId() // TODO
								);
								array_push($replies, $reply);
								$i++;
							}
						}
					}
					if (!empty($replies))
					{
						$message = $mageHelper->__("Select a category") . ". " . $chatbotHelper->_cancelMessage;
						$facebook->sendQuickReply($chatId, $message, $replies);
					}
				}
				else if ($i == 0)
				{
					$facebook->postMessage($chatId, $mageHelper->__("No categories available at the moment, please try again later."));
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				}
				else
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);

				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_checkoutCmd))
			{
				$sessionId = null;
				$quoteId = null;
				$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I prepare the checkout for you."));
				$facebook->sendChatAction($chatId, "typing_on");
				if ($chatdata->getIsLogged() == "1")
				{
					if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId())
					{
						// if user is set as logged, then login using magento singleton
						$customerSession = Mage::getSingleton('customer/session');
						$customerSession->loginById((int)$chatdata->getCustomerId());
						// then set current quote as customer quote
						$customer = Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId());
						$quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
						// set quote and session ids from logged user
						$quoteId = $quote->getId();
						$sessionId = $customerSession->getEncryptedSessionId();
					}
				}
				if (!($sessionId && $quoteId))
				{
					// set quote and session ids from chatbot class
					$sessionId = $chatdata->getSessionId();
					$quoteId = $chatdata->getQuoteId();
				}
				$emptyCart = true;
				if ($sessionId && $quoteId)
				{
					$cartUrl = Mage::helper('checkout/cart')->getCartUrl();
					if (!isset(parse_url($cartUrl)['SID']))
						$cartUrl .= "?SID=" . $sessionId; // add session id to url

					$cart = Mage::getModel('checkout/cart')->setQuote(Mage::getModel('sales/quote')->loadByIdWithoutStore((int)$quoteId));
					$ordersubtotal = $cart->getQuote()->getSubtotal();
					if ($ordersubtotal > 0)
					{
						$buttons = array(
							array(
								'type' => 'web_url',
								'url' => $cartUrl,
								'title' => $mageHelper->__("Checkout")
							)
						);
						$emptyCart = false;
						$message = $mageHelper->__("Products on cart") . ":\n";
						foreach ($cart->getQuote()->getItemsCollection() as $item) // TODO
						{
							$message .= $item->getQty() . "x " . $item->getProduct()->getName() . "\n" .
								$mageHelper->__("Price") . ": " . Mage::helper('core')->currency($item->getProduct()->getPrice(), true, false) . "\n\n";
						}
						$message .= $mageHelper->__("Total") . ": " . Mage::helper('core')->currency($ordersubtotal, true, false);

						if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_checkoutState))
							$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
						else
							$facebook->sendButtonTemplate($chatId, $message, $buttons);
					}
					else if (!$chatdata->clearCart()) // try to clear cart
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				}
				if ($emptyCart)
					$facebook->postMessage($chatId, $mageHelper->__("Your cart is empty."));
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_clearCartCmd))
			{
				$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I clear your cart."));
				$facebook->sendChatAction($chatId, "typing_on");
				$errorFlag = false;
				if ($chatdata->clearCart())
				{
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_clearCartState))
						$errorFlag = true;
					else
						$facebook->postMessage($chatId, $mageHelper->__("Cart cleared."));
				}
				else
					$errorFlag = true;
				if ($errorFlag)
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_searchCmd))
			{
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_searchState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("what do you want to search for?") . " " . $chatbotHelper->_cancelMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_loginCmd))
			{
				if ($chatdata->getIsLogged() != "1") // customer not logged
				{
					$hashUrl = Mage::getUrl('chatbot/settings/index/'); // get base module URL
					$hashUrl = strtok($hashUrl, '?') . "hash" . DS . $chatdata->getHashKey(); // remove magento parameters
					$buttons = array(
						array(
							'type' => 'web_url',
							'url' => $hashUrl,
							'title' => $mageHelper->__("Login")
						)
					);
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_loginState))
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					else
					{
						$facebook->sendButtonTemplate(
							$chatId, $mageHelper->__("To login to your account, access the link below") . ". " .
							$mageHelper->__("If you want to logout from your account, just send") . ' "' . $chatbotHelper->_logoutCmd['command'] . '"', $buttons
						);
					}
				}
				else
					$facebook->postMessage($chatId, $mageHelper->__("You're already logged."));
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_logoutCmd)) // TODO
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$facebook->postMessage($chatId, $mageHelper->__("Ok, logging out."));
					$errorFlag = false;
					try
					{
						$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState);
						$chatdata->updateChatdata('is_logged', "0");
						$chatdata->updateChatdata('customer_id', ""); // TODO null?
						$chatdata->clearCart();
					}
					catch (Exception $e)
					{
						$errorFlag = true;
					}

					if ($errorFlag)
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					else
						$facebook->postMessage($chatId, $mageHelper->__("Done."));
				}
				else
					$facebook->postMessage($chatId, $mageHelper->__("You're not logged."));

				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_registerCmd)) // TODO
			{
				$registerUrl = strtok(Mage::getUrl('customer/account/create'), '?');
				if (!empty($registerUrl))
					$facebook->postMessage($chatId, $mageHelper->__("Access %s to register a new account on our shop.", $registerUrl));
				else
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_listOrdersCmd) || $moreOrders)
			{
				if ($chatdata->getIsLogged() == "1")
				{
					if ($showMore == 0) // show only in the first time
						$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather your orders for listing."));
					else
						$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

					$facebook->sendChatAction($chatId, "typing_on");
					$ordersIDs = $chatbotHelper->getOrdersIdsFromCustomer($chatdata->getCustomerId());
					$i = 0;
					if ($ordersIDs)
					{
						$flagBreak = false;
						$total = count($ordersIDs);
						if ($showMore < $total)
						{
							if ($showMore == 0)
							{
								if ($total == 1)
									$facebook->postMessage($chatId, $mageHelper->__("Done. You've only one order."));
								else
									$facebook->postMessage($chatId, $mageHelper->__("Done. I've found %s orders.", $total));
							}

							$replies = array();
							foreach($ordersIDs as $orderID)
							{
								//$message = $chatbotHelper->prepareFacebookOrderMessages($orderID);
								$payload = $chatbotHelper->prepareFacebookOrderPayload($orderID);
								if (!empty($payload)) // TODO
								{
									$order = Mage::getModel('sales/order')->load($orderID);
									$orderNumber = $order->getIncrementId();
									$reply = array(
										'content_type' => 'text',
										'title' => $orderNumber,
										'payload' => $chatbotHelper->_reorderCmd['command'] . $orderID
									);
									array_push($replies, $reply);
									if ($i >= $showMore)
									{
										if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
										{
											$reply = array(
												'content_type' => 'text',
												'title' => $mageHelper->__("Show more orders"),
												'payload' => $listMoreOrders . (string)($i + 1)
											);
											array_push($replies, $reply);
											if ($chatdata->getFacebookConvState() != $chatbotHelper->_listOrdersState)
												if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listOrdersState))
													$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
											$flagBreak = true;
										}
										else if (($i + 1) == $total) // if it's the last one, back to _startState
										{
											$facebook->postMessage($chatId, $mageHelper->__("And that was the last one."));
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState))
												$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
										}

										$facebook->sendReceiptTemplate($chatId, $payload);
										if ($flagBreak)
										{
											if (!empty($replies))
											{
												$message = $mageHelper->__("If you want to reorder one of these orders, choose it below, or choose '%s' to list more orders.", $mageHelper->__("Show more orders"));
												$facebook->sendQuickReply($chatId, $message, $replies);
											}
											break;
										}
									}
									$i++;
								}
							}
							if ($i == 0)
								$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
//							else if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listOrdersState))
//								$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
						}
					}
					else
					{
						$facebook->postMessage($chatId, $mageHelper->__("This account has no orders."));
						return $chatdata->respondSuccess();
					}
				}
				else
					$facebook->postMessage($chatId, $chatbotHelper->_loginFirstMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->startsWith($text, $chatbotHelper->_reorderCmd['command'])) // ignore alias // old checkCommandWithValue
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("please wait while I add the products from this order to your cart."));
					$facebook->sendChatAction($chatId, "typing_on");
					$errorFlag = false;
					$cmdvalue = $chatbotHelper->getCommandValue($text, $chatbotHelper->_reorderCmd['command']);
					if ($cmdvalue)
					{
						if ($chatdata->clearCart())
						{
							$order = Mage::getModel('sales/order')->load($cmdvalue);
							if ($order->getId())
							{
								foreach($order->getAllVisibleItems() as $item) {
									if (!$chatdata->addProd2Cart($item->getProductId()))
										$errorFlag = true;
								}
							}
							else
								$errorFlag = true;
						}
						else
							$errorFlag = true;
					}
					else
						$errorFlag = true;

					if ($errorFlag)
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					else if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_reorderState))
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					else // success!!
						$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("to checkout send") . ' "' . $chatbotHelper->_checkoutCmd['command'] . '"');
				}
				else
					$facebook->postMessage($chatId, $chatbotHelper->_loginFirstMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_trackOrderCmd))
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$ordersIDs = $chatbotHelper->getOrdersIdsFromCustomer($chatdata->getCustomerId());
					if ($ordersIDs)
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_trackOrderState))
							$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
						else
							$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("send the order number."));
					}
					else
						$facebook->postMessage($chatId, $mageHelper->__("Your account dosen't have any orders."));
				}
				else
					$facebook->postMessage($chatId, $chatbotHelper->_loginFirstMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_supportCmd))
			{
				$supportEnabled = $chatdata->getEnableSupport();
				$errorFlag = false;
				if ($supportEnabled == "1")
				{
					if ($chatdata->getTelegramConvState() != $chatbotHelper->_supportState) // TODO
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_supportState))
							$errorFlag = true;
						else
							$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("what do you need support for?") . " " . $chatbotHelper->_cancelMessage);
					}
					else
						$facebook->postMessage($chatId, $mageHelper->__("You're already on support in other chat application, please close it before opening a new one."));
				}
				else
					$facebook->postMessage($chatId, $mageHelper->__("I'm sorry, you can't ask for support now. Please try again later."));

				if ($errorFlag)
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				return $chatdata->respondSuccess();
			}
			else if ($chatbotHelper->checkCommand($text, $chatbotHelper->_sendEmailCmd))
			{
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_sendEmailState))
					$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
				else
				{
					$facebook->postMessage($chatId, $chatbotHelper->_positiveMessages[array_rand($chatbotHelper->_positiveMessages)] . ", " . $mageHelper->__("write the email content."));
					$facebook->postMessage($chatId, $mageHelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $chatbotHelper->_cancelMessage);
				}
				return $chatdata->respondSuccess();
			}
			else // fallback
			{
				// handle default replies
				if ($enableReplies == "1" && !$blockerStates)
				{
					$defaultReplies = Mage::getStoreConfig('chatbot_enable/facebook_config/default_replies');
					if ($defaultReplies)
					{
						$replies = unserialize($defaultReplies);
						if (is_array($replies))
						{
							$hasWitaiReplies = false;
							foreach($replies as $reply)
							{
//								MODES
//								0 =>'Similarity'
//								1 =>'Starts With'
//								2 =>'Ends With'
//								3 =>'Contains'
//								4 =>'Match Regular Expression'
//								5 =>'Equals to'
//								6 =>'wit.ai'

								$matched = false;
								$match = $reply["match_sintax"];
								$matchMode = $reply["match_mode"];

								if ($reply["match_case"] == "0")
								{
									$match = strtolower($match);
									$textToMatch = strtolower($text);
								}
								else
									$textToMatch = $text;

								if ($matchMode == "0") // Similarity
								{
									$similarity = $reply["similarity"];
									if (is_numeric($similarity))
									{
										if (!($similarity >= 1 && $similarity <= 100))
											$similarity = 100;
									}
									else
										$similarity = 100;

									similar_text($textToMatch, $match, $percent);
									if ($percent >= $similarity)
										$matched = true;
								}
								else if ($matchMode == "1") // Starts With
								{
									if ($chatbotHelper->startsWith($textToMatch, $match))
										$matched = true;
								}
								else if ($matchMode == "2") // Ends With
								{
									if ($chatbotHelper->endsWith($textToMatch, $match))
										$matched = true;
								}
								else if ($matchMode == "3") // Contains
								{
									if (strpos($textToMatch, $match) !== false)
										$matched = true;
								}
								else if ($matchMode == "4") // Match Regular Expression
								{
									if (preg_match($match, $textToMatch))
										$matched = true;
								}
								else if ($matchMode == "5") // Equals to
								{
									if ($textToMatch == $match)
										$matched = true;
								}
								else if (($matchMode == "6") && (isset($this->_witAi))) // wit.ai and witAi is set
								{
									$witAiConfidence = Mage::getStoreConfig('chatbot_enable/witai_config/witai_confidence');
									if (!is_numeric($witAiConfidence) || (int)$witAiConfidence > 100)
										$witAiConfidence = $defaultConfidence; // default acceptable confidence percentage

									if (!$hasWitaiReplies) // avoid multiple posts to witai with the same $text
									{
										$witResponse = $this->_witAi->getTextResponse($text);
										$hasWitaiReplies = true;
									}

									if (!empty($witResponse))
									{
										if (property_exists($witResponse->entities, $match))
										{
											foreach ($witResponse->entities->{$match} as $m)
											{
												if (((float)$m->confidence * 100) < (float)$witAiConfidence)
													continue;

												$matched = true;
												break;
											}
										}
									}
								}

								if ($matched)
								{
									$message = $reply["reply_phrase"];
									if ($username)
										$message = str_replace("{customername}", $username, $message);
									if ($reply['reply_mode'] == "1") // Text and Command
									{
										$cmdId = $reply['command_id'];
										if (!empty($cmdId))
											$text = $chatdata->getCommandString($cmdId)['command']; // 'transform' original text into a known command
										if (!empty($message))
										{
											$count = strlen($message);
											if ($count > $messageLimit)
											{
												$total = ceil($count / $messageLimit);
												$start = 0;
												for ($i = 1; $i <= $total; $i++) // loop to send big messages
												{
													$cut = ($count / $total) * $i;
													if ($cut >= $count) // if cut is equal or bigger to message itself
														$end = $count;
													else
														$end = strpos($message, ' ', $cut);
													$tempMessage = substr($message, $start, $end);
													$facebook->postMessage($chatId, $tempMessage);
													$start = $end;
												}
											}
											else
												$facebook->postMessage($chatId, $message);
										}
									}
									else //if ($reply['reply_mode'] == "0") // Text Only
									{
										if (!empty($message))
										{
											$count = strlen($message);
											if ($count > $messageLimit)
											{
												$total = ceil($count / $messageLimit);
												$start = 0;
												for ($i = 1; $i <= $total; $i++) // loop to send big messages
												{
													$cut = ($count / $total) * $i;
													if ($cut >= $count) // if cut is equal or bigger to message itself
														$end = $count;
													else
														$end = strpos($message, ' ', $cut);
													$tempMessage = substr($message, $start, $end);
													$facebook->postMessage($chatId, $tempMessage);
													$start = $end;
												}
											}
											else
												$facebook->postMessage($chatId, $message);

											if ($reply["stop_processing"] == "1")
												return $chatdata->respondSuccess();
										}
									}
									break;
								}
							}
						}
					}
				}

				$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_startState); // back to start state
				if ($enableFinalMessage2Support == "1")
				{
					$errorFlag = true;
					if ($supportGroupId == $chatbotHelper->_tgBot)
						if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chatId, $originalText, $chatdata->_apiType, $username)) // send chat id, original text, "facebook" and username
						{
//								if ($chatdata->getTelegramConvState() != $chatbotHelper->_supportState) // TODO
//									$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_supportState);
							$errorFlag = false;
						}

					if ($errorFlag)
						$facebook->postMessage($chatId, $chatbotHelper->_errorMessage);
					else
						$facebook->postMessage($chatId,
							$mageHelper->__("Sorry, I didn't understand that.") . " " .
							$mageHelper->__("Please wait while our support check your message so you can talk to a real person.") . " " .
							$chatbotHelper->_cancelMessage
						); // TODO
					return $chatdata->respondSuccess();
				}
				else // process cases where the customer message wasn't understandable
				{
					if (isset($this->_witAi) && !($this->_isWitAi))
					{
						$witAiConfidence = Mage::getStoreConfig('chatbot_enable/witai_config/witai_confidence');
						if (!is_numeric($witAiConfidence) || (int)$witAiConfidence > 100)
							$witAiConfidence = $defaultConfidence; // default acceptable confidence percentage

						$witResponse = $this->_witAi->getTextResponse($text);
						$hasIntent = false;

						if (!empty($witResponse))
						{
							if (property_exists($witResponse->entities, "facebook_intent"))
							{
								$intents = $witResponse->entities->facebook_intent;
								$hasIntent = true;
							}
							else if (property_exists($witResponse->entities, "intent"))
							{
								$intents = $witResponse->entities->intent;
								$hasIntent = true;
							}
						}

						if ($hasIntent)
						{
							$enableConfirmMessage = Mage::getStoreConfig('chatbot_enable/witai_config/confirmation_message');
							$messages = array(
								"Okay, so you want me to list the categories for you.",			//_listCategoriesCmd
								"Okay, so you want to search for a product.",					//_searchCmd
								"Okay, so you want to login.",									//_loginCmd
								"Okay, so you want to list orders.",							//_listOrdersCmd
								"Okay, so you want to reorder.",								//_reorderCmd shouldn't be used
								"Okay, so you want to add a product to the cart.",				//_add2CartCmd shouldn't be used
								"Okay, so you want to checkout.",								//_checkoutCmd
								"Okay, so you want to clear your cart.",						//_clearCartCmd
								"Okay, so you want to track your order.",						//_trackOrderCmd
								"Okay, so you want support.",									//_supportCmd
								"Okay, so you want to send us an email.",						//_sendEmailCmd
								"Okay, so you want to cancel.",									//_cancelCmd shouldn't be used
								"Okay, so you want to help.",									//_helpCmd
								"Okay, so you want to know more about us.",						//_aboutCmd
								"Okay, so you want to logout.",									//_logoutCmd
								"Okay, so you want to register to our store."					//registerCmd
							);

							$i = 1;
							$hasKeyword = false;
							$break = false;

							foreach ($messages as $message)
							{
								$key = $chatdata->getCommandString($i)['command'];
								foreach ($intents as $intent)
								{
									if ($intent->value == $key && (((float)$intent->confidence * 100) >= (float)$witAiConfidence))
									{
										if (property_exists($witResponse->entities, "keyword"))
										{
											if (isset($witResponse->entities->keyword))
											{
												foreach ($witResponse->entities->keyword as $keyword)
												{
													if (((float)$keyword->confidence * 100) < (float)$witAiConfidence)
														continue;
													if ($intent->value == $chatbotHelper->_searchCmd['command'])
													{
														$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_searchState);
														//$facebook->_originalText = $listMoreSearch . $witResponse->entities->keyword . ",1";
														$facebook->_originalText = $keyword->value;
														$hasKeyword = true;
														break;
													}
													else if ($intent->value == $chatbotHelper->_listCategoriesCmd['command'])
													{
														$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $keyword->value);
														if ($_category) // check if variable isn't false/empty
														{
															if ($_category->getId()) // check if is a valid category
															{
																$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_listCategoriesState);
																//$facebook->_originalText = $listMoreCategories . $witResponse->entities->keyword . ",1";
																$facebook->_originalText = $keyword->value;
																$hasKeyword = true;
															}
														}
														break;
													}
													else if ($intent->value == $chatbotHelper->_trackOrderCmd['command'])
													{
														if ($chatdata->getIsLogged() == "1")
														{
															$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_trackOrderState);
															$facebook->_originalText = $keyword->value;
															$hasKeyword = true;
														}
														else
														{
															$facebook->postMessage(array('chat_id' => $chatId, 'text' => $chatbotHelper->_loginFirstMessage));
															$break = true;
															return $chatdata->respondSuccess();
														}
														break;
													}
													else if ($intent->value == $chatbotHelper->_supportCmd['command'])
													{
														$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_supportState);
														$facebook->_originalText = $keyword->value;
														$hasKeyword = true;
														break;
													}
													else if ($intent->value == $chatbotHelper->_sendEmailCmd['command'])
													{
														$chatdata->updateChatdata('facebook_conv_state', $chatbotHelper->_sendEmailState);
														$facebook->_originalText = $keyword->value;
														$hasKeyword = true;
														break;
													}
												}
												if ($break)
													break;
											}
										}
										if (!$hasKeyword)
										{
											$facebook->_originalText = $key; // replace text with command
											if ($enableConfirmMessage == "1")
												$facebook->postMessage($chatId, $mageHelper->__($message));
										}

										$this->_isWitAi = true;
										return $this->processText();
										break;
									}
								}
								$i++;
							}
						}
					}
					if (!$this->_isWitAi)
					{
						$message = $mageHelper->__("Sorry, I didn't understand that.");
						$fallbackQty = 0;

						$fallbackLimit = Mage::getStoreConfig('chatbot_enable/facebook_config/fallback_message_quantity');
						if (!empty($fallbackLimit))
						{
							$fallbackQty = (int)$chatdata->getFacebookFallbackQty();
							$fallbackQty++;
							if (!is_numeric($fallbackLimit))
								$fallbackLimit = 3;
							if ($fallbackQty >= (int)$fallbackLimit)
							{
								$fallbackMessage = Mage::getStoreConfig('chatbot_enable/facebook_config/fallback_message');
								if (!empty($fallbackMessage))
								{
									$fallbackQty = 0;
									$message = $fallbackMessage;
								}
							}
						}

						$chatdata->updateChatdata("facebook_fallback_qty", (string)$fallbackQty);
						$facebook->postMessage($chatId, $message); // TODO

						$cmdListingOnError = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_error_command_list');
						if ($cmdListingOnError == "1")
						{
							$message = $mageHelper->__("Please try one of the following commands.");
							$content = $chatdata->listFacebookCommandsMessage();
							$facebook->sendQuickReply($chatId, $message . $content[0], $content[1]);
						}
					}
				}
			}
			$chatdata->respondSuccess();
		}
	}

?>