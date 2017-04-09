<?php
	include("Messenger.php");
	//$api_path = Mage::getModuleDir('', 'Werules_Chatbot') . DS . "Model" . DS . "Api" . DS . "witAI" . DS;
	//include($api_path . "witAI.php");

	class MessengerBot extends Messenger
	{
		public $_originalText;
		public $_chatId;
		public $_messageId;
		public $_isPayload = false;
	}

	class Werules_Chatbot_Model_Api_Facebook_Handler extends Werules_Chatbot_Model_Chatdata
	{
		protected $_facebook;
		protected $_witAi;

		public function _construct()
		{
			//parent::_construct();
			//$this->_init('chatbot/api_facebook_handler'); // this is location of the resource file.
			$apikey = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_api_key');
			$this->_facebook = new MessengerBot($apikey);
		}

		public function foreignMessageFromSupport($chatId, $text)
		{
			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata');
			$chatdata->load($chatId, 'facebook_chat_id');
			$chatdata->_apiType = $chatdata->_fbBot;

			if (is_null($chatdata->getFacebookChatId()))
			{ // should't happen
				return false;
			}

			// mage helper
			$mageHelper = Mage::helper('core');

			//$chatdata->_apiType = $chatdata->_fbBot;
			$facebook = $this->_facebook;
			if (isset($facebook))
			{
				$message = $mageHelper->__("Message from support") . ":\n" . $text;
				$facebook->sendMessage($chatId, $message);
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

			$enableLog = Mage::getStoreConfig('chatbot_enable/general_config/enable_post_log');
			if ($enableLog == "1") // log all posts
				Mage::log("Post Data:\n" . var_export($facebook->RawData(), true) . "\n\n", null, 'chatbot_facebook.log');

			// checking for payload
			$payloadContent = $facebook->getPayload();
			if (!empty($payloadContent) && empty($facebook->_originalText))
			{
				$facebook->_isPayload = true;
				$facebook->_originalText = $payloadContent;
				$facebook->_messageId = $facebook->getMessageTimestamp();
			}

			if (!empty($facebook->_originalText) && !empty($facebook->_chatId) && $isEcho != "true")
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

			// Take text and chat_id from the message
			$facebook = $this->_facebook;
			$originalText = $facebook->_originalText;
			$chatId = $facebook->_chatId;
			$messageId = $facebook->_messageId;
			$isPayload = $facebook->_isPayload;

			// Instances facebook user details
			$userData = $facebook->UserData($chatId);
			$username = null;
			if (!empty($userData))
				$username = $userData['first_name'];

			// Instances the witAI class
			$enableWitai = Mage::getStoreConfig('chatbot_enable/witai_config/enable_witai');
			if ($enableWitai == "1")
			{
				if (!isset($this->_witAi))
				{
					$witApi = Mage::getStoreConfig('chatbot_enable/witai_config/witai_api_key');
					$this->_witAi = new witAI($witApi);
				}
			}

			$text = strtolower($originalText);

			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata')->load($chatId, 'facebook_chat_id');
			$chatdata->_apiType = $chatdata->_fbBot;

			if ($messageId == $chatdata->getFacebookMessageId() && !($this->_isWitAi)) // prevents to reply the same request twice
				return $facebook->respondSuccess();
			else if ($chatdata->getFacebookChatId())
				$chatdata->updateChatdata('facebook_message_id', $messageId); // if this fails, it may send the same message twice

			// bot enabled/disabled
			if ($enabledBot != "1")
			{
				$disabledMessage = Mage::getStoreConfig('chatbot_enable/facebook_config/disabled_message');
				if (!empty($disabledMessage))
					$facebook->sendMessage($chatId, $disabledMessage);
				return $facebook->respondSuccess();
			}

			// send feedback to user
			$facebook->sendChatAction($chatId, "typing_on");

			// payload handler, may change the conversation state
			if ($chatdata->getFacebookConvState() == $chatdata->_listProductsState || $chatdata->getFacebookConvState() == $chatdata->_listOrdersState) // listing products
			{
				if ($chatdata->startsWith($text, $listMoreCategories)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->_listCategoriesState))
					{
						$value = $this->getCommandValue($text, $listMoreCategories);
						$arr = explode(",", $value);
						$text = $arr[0];
						$showMore = (int)$arr[1];
					}
				}
				else if ($chatdata->startsWith($text, $listMoreSearch)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->_searchState))
					{
						$value = $this->getCommandValue($text, $listMoreSearch);
						$arr = explode(",", $value);
						$text = $arr[0];
						$showMore = (int)$arr[1];
					}
				}
				else if ($chatdata->startsWith($text, $listMoreOrders)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->_listOrdersState))
					{
						$value = $this->getCommandValue($text, $listMoreOrders);
						$showMore = (int)$value; // get where listing stopped
						$moreOrders = true;
					}
				}
//					else
//						$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState);
			}

			// instances conversation state
			$conversationState = $chatdata->getFacebookConvState();

			// mage helper
			$mageHelper = Mage::helper('core');

			// handle admin stuff
			//$isAdmin = $chatdata->getIsAdmin();
			// if it's the admin chat id
			if ($chatId == $supportGroupId)// || $isAdmin == "1")
			{
//					if ($isAdmin == "0") // set user as admin
//						$chatdata->updateChatdata('is_admin', "1");

				if ($conversationState == $chatdata->_replyToSupportMessageState) // check if admin is replying to a customer
				{
					$customerChatId = $chatdata->getFacebookSupportReplyChatId(); // get customer chat id
					if (!empty($customerChatId))
					{
						$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState); // set admin to _startState
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model

						if ($customerData->getFacebookConvState() != $chatdata->_supportState) // if user isn't on support, switch to support
						{
							// TODO IMPORTANT remember to switch off all other supports
							$customerData->updateChatdata('facebook_conv_state', $chatdata->_supportState);
							$facebook->sendMessage($customerChatId, $mageHelper->__("You're now on support mode."));
						}
						$facebook->sendMessage($customerChatId, $mageHelper->__("Message from support") . ":\n" . $text); // send message to customer TODO
						$facebook->sendMessage($chatId, $mageHelper->__("Message sent."));
					}
					return $facebook->respondSuccess();
				}
				else if ($chatdata->startsWith($text, $chatdata->_admSendMessage2AllCmd)) // old checkCommandWithValue
				{
					$message = trim($chatdata->getCommandValue($text, $chatdata->_admSendMessage2AllCmd));
					if (!empty($message))
					{
						$chatbotcollection = Mage::getModel('chatbot/chatdata')->getCollection();
						foreach($chatbotcollection as $chatbot)
						{
							$fbChatId = $chatbot->getFacebookChatId();
							if ($fbChatId)
								$facebook->sendMessage($fbChatId, $message); // $magehelper->__("Message from support") . ":\n" .
						}
						$facebook->sendMessage($chatId, $mageHelper->__("Message sent."));
					}
					else
						$facebook->sendMessage($chatId, $mageHelper->__("Please use") . ' "' . $chatdata->_admSendMessage2AllCmd . " " . $mageHelper->__("your message here.") . '"');
				}
				else if ($isPayload)
				{
					if ($chatdata->startsWith($text, $chatdata->_admEndSupportCmd)) // finish customer support  // old checkCommandWithValue
					{
						$customerChatId = trim($chatdata->getCommandValue($text, $chatdata->_admEndSupportCmd)); // get customer chatId from payload
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model
						$customerData->updateChatdata('facebook_conv_state', $chatdata->_startState); // update conversation state

						$facebook->sendMessage($chatId, $mageHelper->__("Done. The customer is no longer on support."));
						$facebook->sendMessage($customerChatId, $mageHelper->__("Support ended."));
					}
					else if ($chatdata->startsWith($text, $chatdata->_admBlockSupportCmd)) // block user from using support // old checkCommandWithValue
					{
						$customerChatId = trim($chatdata->getCommandValue($text, $chatdata->_admBlockSupportCmd)); // get customer chatId from payload
						$customerData = Mage::getModel('chatbot/chatdata')->load($customerChatId, 'facebook_chat_id'); // load chatdata model
						if ($customerData->getEnableSupport() == "1")
						{
							$customerData->updateChatdata('enable_support', "0"); // disable support
							$facebook->sendMessage($chatId, $mageHelper->__("Done. The customer is no longer able to enter support."));
						}
						else //if ($customerData->getEnableSupport() == "0")
						{
							$customerData->updateChatdata('enable_support', "1"); // enable support
							$facebook->sendMessage($chatId, $mageHelper->__("Done. The customer is now able to enter support."));
						}

					}
					else if ($chatdata->startsWith($text, $replyToCustomerMessage)) // old checkCommandWithValue
					{
						$customerChatId = trim($chatdata->getCommandValue($text, $replyToCustomerMessage)); // get customer chatId from payload
						$chatdata->updateChatdata('facebook_support_reply_chat_id', $customerChatId);
						$chatdata->updateChatdata('facebook_conv_state', $chatdata->_replyToSupportMessageState);

						$facebook->sendMessage($chatId, $mageHelper->__("Ok, send me the message and I'll forward it to the customer."));
					}
					else if ($chatdata->checkCommand($text, $chatdata->_admSendMessage2AllCmd)) // TODO
					{

					}

					return $facebook->respondSuccess();
				}
			}

			// ALL CUSTOMER HANDLERS GOES AFTER HERE

			if ($chatdata->getIsLogged() == "1") // check if customer is logged
			{
				if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId()) // if is a valid customer id
				{
					if ($chatdata->getEnableFacebook() != "1")
					{
						$facebook->sendMessage($chatId, $mageHelper->__("To talk with me, please enable Facebook Messenger on your account chatbot settings."));
						$facebook->sendChatAction($chatId, "typing_off");
						return $facebook->respondSuccess();
					}
				}
			}

			$blockerStates = (
				$conversationState == $chatdata->_listCategoriesState ||
				$conversationState == $chatdata->_searchState ||
				$conversationState == $chatdata->_supportState ||
				$conversationState == $chatdata->_sendEmailState ||
				$conversationState == $chatdata->_trackOrderState
			);

			// handle default replies
			if ($enableReplies == "1" && !$blockerStates)
			{
				$defaultReplies = Mage::getStoreConfig('chatbot_enable/facebook_config/default_replies');
				if ($defaultReplies)
				{
					$replies = unserialize($defaultReplies);
					if (is_array($replies))
					{
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
								if ($chatdata->startsWith($textToMatch, $match))
									$matched = true;
							}
							else if ($matchMode == "2") // Ends With
							{
								if ($chatdata->endsWith($textToMatch, $match))
									$matched = true;
							}
							else if ($matchMode == "3") // Contains
							{
								if (strpos($textToMatch, $match) !== false)
									$matched = true;
							}
							else if ($matchMode == "4") // Match Regular Expression
							{
//									if ($match[0] != "/")
//										$match = "/" . $match;
//									if ((substr($match, -1) != "/") && ($match[strlen($match) - 2] != "/"))
//										$match .= "/";
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

								$witResponse = $this->_witAi->getWitAIResponse($text);
								if (property_exists($witResponse->entities, $match))
								{
									foreach ($witResponse->entities->{$match} as $m)
									{
										if (((float)$m->confidence * 100) < (float)$witAiConfidence)
											continue;

										$matched = true;
									}
								}
							}

							if ($matched)
							{
								$message = $reply["reply_phrase"];
								if ($reply['reply_mode'] == "1")
								{
									$cmdId = $reply['command_id'];
									if (!empty($cmdId))
										$text = $chatdata->getCommandString($cmdId)['command']; // 'transform' original text into a known command
									if (!empty($message))
										$facebook->sendMessage($chatId, $message);
								}
								else //if ($reply['reply_mode'] == "0")
								{
									if (!empty($message))
									{
										$facebook->sendMessage($chatId, $message);
										if ($reply["stop_processing"] == "1")
											return $facebook->respondSuccess();
									}
								}
								break;
							}
						}
					}
				}
			}

			// user isnt registred HERE
			if (is_null($chatdata->getFacebookChatId())) // if user isn't registred
			{
				$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_welcome_msg'); // TODO
				if ($message) // TODO
					$facebook->sendMessage($chatId, $message);
				try
				{
					$hash = substr(md5(uniqid($chatId, true)), 0, 150); // TODO
					$chatdata // using magento model to insert data into database the proper way
					->setFacebookChatId($chatId)
						->setHashKey($hash) // TODO
						->setCreatedAt(date('Y-m-d H:i:s'))
						->save();
					//$chatdata->updateChatdata('facebook_chat_id', $chat_id);
					//$chatdata->updateChatdata('hash_key', $hash);
				}
				catch (Exception $e)
				{
					$facebook->sendMessage($chatId, $chatdata->_errorMessage); // TODO
				}
				//$facebook->sendChatAction($chatId, "typing_off");
				//return $facebook->respondSuccess(); // commented to keep processing the message
			}

			// init commands
			//$chatdata->_startCmd['command'] = "Start";
			$chatdata->_listCategoriesCmd = $chatdata->getCommandString(1);
			$chatdata->_searchCmd = $chatdata->getCommandString(2);
			$chatdata->_loginCmd = $chatdata->getCommandString(3);
			$chatdata->_listOrdersCmd = $chatdata->getCommandString(4);
			$chatdata->_reorderCmd = $chatdata->getCommandString(5);
			$chatdata->_add2CartCmd = $chatdata->getCommandString(6);
			$chatdata->_checkoutCmd = $chatdata->getCommandString(7);
			$chatdata->_clearCartCmd = $chatdata->getCommandString(8);
			$chatdata->_trackOrderCmd = $chatdata->getCommandString(9);
			$chatdata->_supportCmd = $chatdata->getCommandString(10);
			$chatdata->_sendEmailCmd = $chatdata->getCommandString(11);
			$chatdata->_cancelCmd = $chatdata->getCommandString(12);
			$chatdata->_helpCmd = $chatdata->getCommandString(13);
			$chatdata->_aboutCmd = $chatdata->getCommandString(14);
			$chatdata->_logoutCmd = $chatdata->getCommandString(15);
			$chatdata->_registerCmd = $chatdata->getCommandString(16);
			if (!$chatdata->_cancelCmd['command']) $chatdata->_cancelCmd['command'] = "cancel"; // it must always have a cancel command

			// init messages
			$chatdata->_errorMessage = $mageHelper->__("Something went wrong, please try again.");
			$chatdata->_cancelMessage = $mageHelper->__("To cancel, send") . ' "' . $chatdata->_cancelCmd['command'] . '"';
			$chatdata->_canceledMessage = $mageHelper->__("Ok, canceled.");
			$chatdata->_loginFirstMessage = $mageHelper->__("Please login first.");
			array_push($chatdata->_positiveMessages, $mageHelper->__("Ok"), $mageHelper->__("Okay"), $mageHelper->__("Cool"), $mageHelper->__("Awesome"));
			// $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)]

			if ($enablePredict == "1" && !$isPayload) // prediction is enabled and itsn't payload
			{
				if ($conversationState == $chatdata->_startState)
				{
					$cmdarray = array(
						$chatdata->_startCmd['command'],
						$chatdata->_listCategoriesCmd['command'],
						$chatdata->_searchCmd['command'],
						$chatdata->_loginCmd['command'],
						$chatdata->_listOrdersCmd['command'],
						$chatdata->_reorderCmd['command'],
						$chatdata->_add2CartCmd['command'],
						$chatdata->_checkoutCmd['command'],
						$chatdata->_clearCartCmd['command'],
						$chatdata->_trackOrderCmd['command'],
						$chatdata->_supportCmd['command'],
						$chatdata->_sendEmailCmd['command'],
						$chatdata->_cancelCmd['command'],
						$chatdata->_helpCmd['command'],
						$chatdata->_aboutCmd['command'],
						$chatdata->_logoutCmd['command'],
						$chatdata->_registerCmd['command']
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

			// cancel command
			if ($chatdata->checkCommand($text, $chatdata->_cancelCmd))
			{
				if ($conversationState == $chatdata->_listCategoriesState)
				{
					$message = $chatdata->_canceledMessage;
				}
				else if ($conversationState == $chatdata->_supportState)
				{
					$message = $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("exiting support mode.");
				}
				else if ($conversationState == $chatdata->_searchState)
				{
					$message = $chatdata->_canceledMessage;
				}
				else if ($conversationState == $chatdata->_sendEmailState)
				{
					$message = $chatdata->_canceledMessage;
				}
				else if ($conversationState == $chatdata->_listProductsState)
				{
					$message = $chatdata->_canceledMessage;
				}
				else if ($conversationState == $chatdata->_listOrdersState)
				{
					$message = $chatdata->_canceledMessage;
				}
				else
					$message = $chatdata->_errorMessage;

				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else
					$facebook->sendMessage($chatId, $message);
				$facebook->sendChatAction($chatId, "typing_off");
				return $facebook->respondSuccess();
			}

			// add2cart commands
			if ($chatdata->startsWith($text, $chatdata->_add2CartCmd['command'])) // ignore alias // old checkCommandWithValue
			{
				$errorFlag = false;
				$notInStock = false;
				$cmdvalue = $chatdata->getCommandValue($text, $chatdata->_add2CartCmd['command']);
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
							$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("adding %s to your cart.", $productName));
							$facebook->sendChatAction($chatId, "typing_on");
							if ($chatdata->addProd2Cart($cmdvalue))
								$facebook->sendMessage($chatId, $mageHelper->__("Added. To checkout send") . ' "' . $chatdata->_checkoutCmd['command'] . '"');
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
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else if ($notInStock)
					$facebook->sendMessage($chatId, $mageHelper->__("This product is not in stock."));

				return $facebook->respondSuccess();
			}

			// help command
			if ($chatdata->checkCommand($text, $chatdata->_helpCmd))
			{
				$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_help_msg'); // TODO
				if (!empty($message)) // TODO
				{
					$facebook->sendMessage($chatId, $message);
					$cmdListing = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_help_command_list');
					if ($cmdListing == "1")
					{
						$content = $chatdata->listFacebookCommandsMessage();
						$facebook->sendQuickReply($chatId, $content[0], $content[1]);
					}
				}

				$facebook->sendChatAction($chatId, "typing_off");
				return $facebook->respondSuccess();
			}

			// about command
			if ($chatdata->checkCommand($text, $chatdata->_aboutCmd))
			{
				$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_about_msg'); // TODO
				if (!empty($message))
					$facebook->sendMessage($chatId, $message);

				$facebook->sendChatAction($chatId, "typing_off");
				return $facebook->respondSuccess();
			}

			// states
			if ($conversationState == $chatdata->_listCategoriesState) // TODO show only in stock products
			{
				if ($showMore == 0) // show only in the first time
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather all products from %s for you.", $text));
				else
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

				$facebook->sendChatAction($chatId, "typing_on");
				$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
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
										$facebook->sendMessage($chatId, $mageHelper->__("Done. This category has only one product."));
									else
										$facebook->sendMessage($chatId, $mageHelper->__("Done. This category has %s products.", $total));
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
												'payload' => $chatdata->_add2CartCmd['command'] . $productID
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
											'subtitle' => $chatdata->excerpt($product->getShortDescription(), 60),
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
												'subtitle' => $chatdata->excerpt(Mage::getStoreConfig('design/head/default_description'), 60),
												'buttons' => $button
											);
											array_push($elements, $element);
											if ($chatdata->getFacebookConvState() != $chatdata->_listProductsState)
												if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listProductsState))
													$facebook->sendMessage($chatId, $chatdata->_errorMessage);
											break;
										}
										else if (($i + 1) == $total) // if it's the last one, back to _startState
										{
											//$facebook->sendMessage($chatId, $mageHelper->__("And that was the last one.")); // uneeded message for facebook
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
												$facebook->sendMessage($chatId, $chatdata->_errorMessage);
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
							$facebook->sendMessage($chatId, $mageHelper->__("Sorry, no products found in this category."));
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
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState);
				}
				return $facebook->respondSuccess();
			}
			else if ($conversationState == $chatdata->_searchState)
			{
				if ($showMore == 0) // show only in the first time
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I search for '%s' for you.", $text));
				else
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

				$facebook->sendChatAction($chatId, "typing_on");
				$errorFlag = false;
				$noProductFlag = false;
				$productIDs = $chatdata->getProductIdsBySearch($text);
				$elements = array();
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
				{
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					return $facebook->respondSuccess();
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
								$facebook->sendMessage($chatId, $mageHelper->__("Done. I've found only one product for your criteria."));
							else
								$facebook->sendMessage($chatId, $mageHelper->__("Done. I've found %s products for your criteria.", $total));
						}

						$placeholder = Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
						foreach ($productIDs as $productID)
						{
							$message = $chatdata->prepareFacebookProdMessages($productID);
							//Mage::helper('core')->__("Add to cart") . ": " . $this->_add2CartCmd['command'] . $product->getId();
							if ($message) // TODO
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
											'payload' => $chatdata->_add2CartCmd['command'] . $productID
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
										'subtitle' => $chatdata->excerpt($product->getShortDescription(), 60),
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
											'subtitle' => $chatdata->excerpt(Mage::getStoreConfig('design/head/default_description'), 60),
											'buttons' => $button
										);
										array_push($elements, $element);
										if ($chatdata->getFacebookConvState() != $chatdata->_listProductsState)
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listProductsState))
												$facebook->sendMessage($chatId, $chatdata->_errorMessage);
										break;
									}
									else if (($i + 1) == $total) // if it's the last one, back to _startState
									{
										//$facebook->sendMessage($chatId, $mageHelper->__("And that was the last one.")); // uneeded message for facebook
										if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
											$facebook->sendMessage($chatId, $chatdata->_errorMessage);
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
					$facebook->sendMessage($chatId, $mageHelper->__("Sorry, no products found for this criteria."));
				else if ($errorFlag)
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listProductsState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else
					$facebook->sendGenericTemplate($chatId, $elements);

				return $facebook->respondSuccess();
			}
			else if ($conversationState == $chatdata->_supportState)
			{
				$errorFlag = true;
				if (!empty($supportGroupId))
				{
					if ($supportGroupId == $chatdata->_tgBot)
					{
						if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chatId, $originalText, $chatdata->_apiType, $username)) // send chat id, original text, "facebook" and username
							$errorFlag = false;
					}
					else // probably have the admin chat id set
					{
						$buttons = array(
							array(
								'type' => 'postback',
								'title' => $mageHelper->__("End support"),
								'payload' => $chatdata->_admEndSupportCmd . $chatId

							),
							array(
								'type' => 'postback',
								'title' => $mageHelper->__("Enable/Disable support"),
								'payload' => $chatdata->_admBlockSupportCmd . $chatId

							),
							array(
								'type' => 'postback',
								'title' => $mageHelper->__("Reply this message"),
								'payload' => $replyToCustomerMessage . $chatId

							)
						);

						$message = $mageHelper->__("From") . ": " . $username . "\n" . $mageHelper->__("ID") . ": " . $chatId . "\n" . $text;
						$facebook->sendButtonTemplate($supportGroupId, $message, $buttons);
						$errorFlag = false;
					}
				}

				if ($errorFlag)
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("we have sent your message to support."));
				return $facebook->respondSuccess();
			}
			else if ($conversationState == $chatdata->_sendEmailState)
			{
				$facebook->sendMessage($chatId, $mageHelper->__("Trying to send the email..."));
				if ($chatdata->sendEmail($text, $username))
				{
					$facebook->sendMessage($chatId, $mageHelper->__("Done."));
				}
				else
					$facebook->sendMessage($chatId, $mageHelper->__("Sorry, I wasn't able to send an email this time. Please try again later."));
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				return $facebook->respondSuccess();
			}
			else if ($conversationState == $chatdata->_trackOrderState)
			{
				$errorFlag = false;
				if ($chatdata->getIsLogged() == "1")
				{
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I check the status for order %s.", $text));
					$facebook->sendChatAction($chatId, "typing_on");
					$order = Mage::getModel('sales/order')->loadByIncrementId($text);
					if ($order->getId())
					{
						if ($order->getCustomerId() == $chatdata->getCustomerId()) // not a problem if customer dosen't exist
						{
							$facebook->sendMessage($chatId, $mageHelper->__("Your order status is") . " " . $order->getStatus());
						}
						else
							$errorFlag = true;
					}
					else
						$errorFlag = true;
				}
				else
					$facebook->sendMessage($chatId, $chatdata->_loginFirstMessage);
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else if ($errorFlag)
					$facebook->sendMessage($chatId, $mageHelper->__("Sorry, we couldn't find any order with this information."));
				return $facebook->respondSuccess();
			}

			//general commands
			if ($chatdata->checkCommand($text, $chatdata->_listCategoriesCmd))
			{
				$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather all categories for you."));
				$facebook->sendChatAction($chatId, "typing_on");

				$categoryHelper = Mage::helper('catalog/category');
				$categories = $categoryHelper->getStoreCategories(); // TODO test with a store without categories
				$i = 0;
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listCategoriesState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
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
							$cat_name = $_category->getName();
							if (!empty($cat_name))
							{
								$reply = array(
									'content_type' => 'text',
									'title' => $cat_name,
									'payload' => 'list_category_' . $_category->getId() // TODO
								);
								array_push($replies, $reply);
								$i++;
							}
						}
					}
					if (!empty($replies))
					{
						$message = $mageHelper->__("Select a category") . ". " . $chatdata->_cancelMessage;
						$facebook->sendQuickReply($chatId, $message, $replies);
					}
				}
				else if ($i == 0)
				{
					$facebook->sendMessage($chatId, $mageHelper->__("No categories available at the moment, please try again later."));
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				}
				else
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);

				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_checkoutCmd))
			{
				$sessionId = null;
				$quoteId = null;
				$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I prepare the checkout for you."));
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

						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_checkoutState))
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
						else
							$facebook->sendButtonTemplate($chatId, $message, $buttons);
					}
					else if (!$chatdata->clearCart()) // try to clear cart
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				}
				if ($emptyCart)
					$facebook->sendMessage($chatId, $mageHelper->__("Your cart is empty."));
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_clearCartCmd))
			{
				$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I clear your cart."));
				$facebook->sendChatAction($chatId, "typing_on");
				$errorFlag = false;
				if ($chatdata->clearCart())
				{
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_clearCartState))
						$errorFlag = true;
					else
						$facebook->sendMessage($chatId, $mageHelper->__("Cart cleared."));
				}
				else
					$errorFlag = true;
				if ($errorFlag)
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_searchCmd))
			{
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_searchState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("what do you want to search for?") . " " . $chatdata->_cancelMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_loginCmd))
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
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_loginState))
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else
					{
						$facebook->sendButtonTemplate(
							$chatId, $mageHelper->__("To login to your account, access the link below") . ". " .
							$mageHelper->__("If you want to logout from your account, just send") . ' "' . $chatdata->_logoutCmd['command'] . '"', $buttons
						);
					}
				}
				else
					$facebook->sendMessage($chatId, $mageHelper->__("You're already logged."));
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_logoutCmd)) // TODO
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$facebook->sendMessage($chatId, $mageHelper->__("Ok, logging out."));
					$errorFlag = false;
					try
					{
						$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState);
						$chatdata->updateChatdata('is_logged', "0");
						$chatdata->updateChatdata('customer_id', ""); // TODO null?
						$chatdata->clearCart();
					}
					catch (Exception $e)
					{
						$errorFlag = true;
					}

					if ($errorFlag)
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else
						$facebook->sendMessage($chatId, $mageHelper->__("Done."));
				}
				else
					$facebook->sendMessage($chatId, $mageHelper->__("You're not logged."));

				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_registerCmd)) // TODO
			{
				$registerUrl = strtok(Mage::getUrl('customer/account/create'), '?');
				if (!empty($registerUrl))
					$facebook->sendMessage($chatId, $mageHelper->__("Access %s to register a new account on our shop.", $registerUrl));
				else
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_listOrdersCmd) || $moreOrders)
			{
				if ($chatdata->getIsLogged() == "1")
				{
					if ($showMore == 0) // show only in the first time
						$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather your orders for listing."));
					else
						$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

					$facebook->sendChatAction($chatId, "typing_on");
					$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
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
									$facebook->sendMessage($chatId, $mageHelper->__("Done. You've only one order."));
								else
									$facebook->sendMessage($chatId, $mageHelper->__("Done. I've found %s orders.", $total));
							}

							foreach($ordersIDs as $orderID)
							{
								$buttons = array();
								$message = $chatdata->prepareFacebookOrderMessages($orderID);
								if ($message) // TODO
								{
									$button = array(
										'type' => 'postback',
										'title' => $mageHelper->__("Reorder"),
										'payload' => $chatdata->_reorderCmd['command'] . $orderID
									);
									array_push($buttons, $button);
									if ($i >= $showMore)
									{
										if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
										{
											// TODO add option to list more orders
											$button = array(
												'type' => 'postback',
												'title' => $mageHelper->__("Show more orders"),
												'payload' => $listMoreOrders . (string)($i + 1)
											);
											array_push($buttons, $button);
											if ($chatdata->getFacebookConvState() != $chatdata->_listOrdersState)
												if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listOrdersState))
													$facebook->sendMessage($chatId, $chatdata->_errorMessage);
											$flagBreak = true;
										}
										else if (($i + 1) == $total) // if it's the last one, back to _startState
										{
											//$facebook->sendMessage($chatId, $mageHelper->__("And that was the last one.")); // uneeded message for facebook
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
												$facebook->sendMessage($chatId, $chatdata->_errorMessage);
										}

										$facebook->sendButtonTemplate($chatId, $message, $buttons);
										if ($flagBreak)
											break;
									}
									$i++;
								}
							}
							if ($i == 0)
								$facebook->sendMessage($chatId, $chatdata->_errorMessage);
//							else if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listOrdersState))
//								$facebook->sendMessage($chat_id, $chatdata->_errorMessage);
						}
					}
					else
					{
						$facebook->sendMessage($chatId, $mageHelper->__("This account has no orders."));
						return $facebook->respondSuccess();
					}
				}
				else
					$facebook->sendMessage($chatId, $chatdata->_loginFirstMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->startsWith($text, $chatdata->_reorderCmd['command'])) // ignore alias // old checkCommandWithValue
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I add the products from this order to your cart."));
					$facebook->sendChatAction($chatId, "typing_on");
					$errorFlag = false;
					$cmdvalue = $chatdata->getCommandValue($text, $chatdata->_reorderCmd['command']);
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
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_reorderState))
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else // success!!
						$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("to checkout send") . ' "' . $chatdata->_checkoutCmd['command'] . '"');
				}
				else
					$facebook->sendMessage($chatId, $chatdata->_loginFirstMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_trackOrderCmd))
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
					if ($ordersIDs)
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_trackOrderState))
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
						else
							$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("send the order number."));
					}
					else
						$facebook->sendMessage($chatId, $mageHelper->__("Your account dosen't have any orders."));
				}
				else
					$facebook->sendMessage($chatId, $chatdata->_loginFirstMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_supportCmd))
			{
				$supportEnabled = $chatdata->getEnableSupport();
				$errorFlag = false;
				if ($supportEnabled == "1")
				{
					if ($chatdata->getTelegramConvState() != $chatdata->_supportState) // TODO
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_supportState))
							$errorFlag = true;
						else
							$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("what do you need support for?") . " " . $chatdata->_cancelMessage);
					}
					else
						$facebook->sendMessage($chatId, $mageHelper->__("You're already on support in other chat application, please close it before opening a new one."));
				}
				else
					$facebook->sendMessage($chatId, $mageHelper->__("I'm sorry, you can't ask for support now. Please try again later."));

				if ($errorFlag)
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				return $facebook->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_sendEmailCmd))
			{
				if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_sendEmailState))
					$facebook->sendMessage($chatId, $chatdata->_errorMessage);
				else
				{
					$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("write the email content."));
					$facebook->sendMessage($chatId, $mageHelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $chatdata->_cancelMessage);
				}
				return $facebook->respondSuccess();
			}
			else
			{
				$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState); // back to start state
				if ($enableFinalMessage2Support == "1")
				{
					$errorFlag = true;
					if ($supportGroupId == $chatdata->_tgBot)
						if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chatId, $originalText, $chatdata->_apiType, $username)) // send chat id, original text, "facebook" and username
						{
//								if ($chatdata->getTelegramConvState() != $chatdata->_supportState) // TODO
//									$chatdata->updateChatdata('facebook_conv_state', $chatdata->_supportState);
							$errorFlag = false;
						}

					if ($errorFlag)
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else
						$facebook->sendMessage($chatId,
							$mageHelper->__("Sorry, I didn't understand that.") . " " .
							$mageHelper->__("Please wait while our support check your message so you can talk to a real person.") . " " .
							$chatdata->_cancelMessage
						); // TODO
					return $facebook->respondSuccess();
				}
				else // process cases where the customer message wasn't understandable
				{
					if (isset($this->_witAi) && !($this->_isWitAi))
					{
						$witAiConfidence = Mage::getStoreConfig('chatbot_enable/witai_config/witai_confidence');
						if (!is_numeric($witAiConfidence) || (int)$witAiConfidence > 100)
							$witAiConfidence = $defaultConfidence; // default acceptable confidence percentage

						$witResponse = $this->_witAi->getWitAIResponse($text);

						$hasIntent = true;
						if (property_exists($witResponse->entities, "facebook_intent"))
							$intents = $witResponse->entities->facebook_intent;
						else if (property_exists($witResponse->entities, "intent"))
							$intents = $witResponse->entities->intent;
						else
							$hasIntent = false;

						if ($hasIntent)
						{
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
												if (isset($witResponse->entities->keyword))
												{
													foreach ($witResponse->entities->keyword as $keyword)
													{
														if (((float)$keyword->confidence * 100) < (float)$witAiConfidence)
															continue;
														if ($intent->value == $chatdata->_searchCmd['command'])
														{
															$chatdata->updateChatdata('facebook_conv_state', $chatdata->_searchState);
															//$facebook->_originalText = $listMoreSearch . $witResponse->entities->keyword . ",1";
															$facebook->_originalText = $keyword->value;
															$hasKeyword = true;
															break;
														}
														else if ($intent->value == $chatdata->_listCategoriesCmd['command'])
														{
															$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $keyword->value);
															if ($_category) // check if variable isn't false/empty
															{
																if ($_category->getId()) // check if is a valid category
																{
																	$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listCategoriesState);
																	//$facebook->_originalText = $listMoreCategories . $witResponse->entities->keyword . ",1";
																	$facebook->_originalText = $keyword->value;
																	$hasKeyword = true;
																}
															}
															break;
														}
														else if ($intent->value == $chatdata->_trackOrderCmd['command'])
														{
															if ($chatdata->getIsLogged() == "1")
															{
																$chatdata->updateChatdata('facebook_conv_state', $chatdata->_trackOrderState);
																$facebook->_originalText = $keyword->value;
																$hasKeyword = true;
															}
															else
															{
																$facebook->sendMessage(array('chat_id' => $chatId, 'text' => $chatdata->_loginFirstMessage));
																$break = true;
																return $facebook->respondSuccess();
															}
															break;
														}
														else if ($intent->value == $chatdata->_supportCmd['command'])
														{
															$chatdata->updateChatdata('facebook_conv_state', $chatdata->_supportState);
															$facebook->_originalText = $keyword->value;
															$hasKeyword = true;
															break;
														}
														else if ($intent->value == $chatdata->_sendEmailCmd['command'])
														{
															$chatdata->updateChatdata('facebook_conv_state', $chatdata->_sendEmailState);
															$facebook->_originalText = $keyword->value;
															$hasKeyword = true;
															break;
														}
													}
													if ($break)
														break;
												}
											}
										}
										if(!$hasKeyword)
										{
											$facebook->_originalText = $key; // replace text with command
											$facebook->sendMessage($chatId, $mageHelper->__($message));
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
						$facebook->sendMessage($chatId, $message); // TODO

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
			return null;
		}
	}

?>