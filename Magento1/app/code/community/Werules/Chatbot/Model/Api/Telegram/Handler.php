<?php
	include("Telegram.php");
	//$api_path = Mage::getModuleDir('', 'Werules_Chatbot') . DS . "Model" . DS . "Api" . DS . "witAI" . DS;
	//include($api_path . "witAI.php");

	class TelegramBot extends Telegram
	{
		public $_text;
		public $_chatId;
		public $_messageId;

        public function postMessage($chatId, $message)
        {
            return $this->sendMessage(array('chat_id' => $chatId, 'text' => $message));
        }
	}

	class Werules_Chatbot_Model_Api_Telegram_Handler extends Werules_Chatbot_Model_Chatdata
	{
		protected $_telegram;
		protected $_witAi;

		public function _construct()
		{
			//parent::_construct();
			//$this->_init('chatbot/api_telegram_handler'); // this is location of the resource file.
			$apikey = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_api_key');
			$this->_telegram = new TelegramBot($apikey);
		}

		public function foreignMessageToSupport($chatId, $text, $apiName, $customerName)
		{
			$chatdata = Mage::getModel('chatbot/chatdata');
			if ($apiName == $chatdata->_fbBot && $chatId)
			{
				$chatdata->load($chatId, 'facebook_chat_id');
				if (is_null($chatdata->getFacebookChatId()))
				{ // should't happen
					$chatdata->updateChatdata("facebook_chat_id", $chatId);
				}
			}

			//$chatdata->_apiType = $chatdata->_tgBot;
			$telegram = $this->_telegram;
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

		public function telegramHandler()
		{
			// Instances the Telegram class
			$telegram = $this->_telegram;
			//$telegram = new TelegramBot("");

			if (!isset($telegram)) // if no apiKey available, break process
				return json_encode(array("status" => "error"));

			// Take text and chat_id from the message
			$telegram->_text = $telegram->Text();
			$telegram->_chatId = $telegram->ChatID();
			$telegram->_messageId = $telegram->MessageID();
			$inlineQuery = $telegram->Inline_Query();

			$enableLog = Mage::getStoreConfig('chatbot_enable/general_config/enable_post_log');
			if ($enableLog == "1") // log all posts
				Mage::log("Post Data:\n" . var_export($telegram->getData(), true) . "\n\n", null, 'chatbot_telegram.log');

			$enableInlineBot = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_inline_search');
			// handle inline search
			if (!empty($inlineQuery))
			{
				if ($enableInlineBot == "1")
				{
					$query = $inlineQuery['query'];
					$queryId = $inlineQuery['id'];
					$results = array();
					$chatdataInline = Mage::getModel('chatbot/chatdata');
					if (!empty($query))
					{
						$productIDs = $chatdataInline->getProductIdsBySearch($query);
						$mageHelperInline = Mage::helper('core');
						if (!empty($productIDs))
						{
							//$total = count($productIDs);
							$i = 0;
							foreach($productIDs as $productID)
							{
								$product = Mage::getModel('catalog/product')->load($productID);
								if ($product->getId())
								{
									if ($product->getStockItem()->getIsInStock() > 0)
									{
										if ($i >= 5)
											break;
										$productUrl = $product->getProductUrl();
										$productImage = $product->getImageUrl();
										$productName = $product->getName();
										$productDescription = $chatdataInline->excerpt($product->getShortDescription(), 60);
										$placeholder = Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
										if (empty($productImage))
											$productImage = $placeholder;

										$message = $productName . "\n" .
											$mageHelperInline->__("Price") . ": " . Mage::helper('core')->currency($product->getPrice(), true, false) . "\n" .
											$productDescription . "\n" .
											$productUrl
										;

										$result = array(
											'type' => 'article',
											'id' => $queryId . "/" . (string)$i,
											'title' => $productName,
											'description' => $productDescription,
											'thumb_url' => $productImage,
											'input_message_content' => array(
												'message_text' => $message,
												'parse_mode' => 'HTML'
											)
										);

										array_push($results, $result);
										$i++;
									}
								}
							}

							$telegram->answerInlineQuery(array('inline_query_id' => $queryId, 'results' => json_encode($results)));
						}
						else
						{
							$results = array(
								array(
									'type' => 'article',
									'id' => $queryId . "/0",
									'title' => $mageHelperInline->__("Sorry, no products found for this criteria."),
									'input_message_content' => array(
										'message_text' => $mageHelperInline->__("Sorry, no products found for this criteria.")
									)
								)
							);
							$telegram->answerInlineQuery(array('inline_query_id' => $queryId, 'results' => json_encode($results)));
						}
					}
				}
				$telegram->respondSuccess();
			}

			if (!is_null($telegram->_text) && !is_null($telegram->_chatId))
			{
				return $this->processText();
			}

			return $telegram->respondSuccess();
		}

		public function processText()
		{
			// configs
			$enabledBot = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_bot');
			$enableReplies = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_default_replies');
			$enableEmptyCategoriesListing = Mage::getStoreConfig('chatbot_enable/general_config/list_empty_categories');
			$enableFinalMessage2Support = Mage::getStoreConfig('chatbot_enable/general_config/enable_support_final_message');
			$supportGroupId = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_support_group');
			$showMore = 0;
			$cat_id = null;
			$moreOrders = false;
			$defaultConfidence = 75;
			$listingLimit = 5;
			$categoryLimit = 18;
			$listMoreCategories = "/lmc_";
			$listMoreSearch = "/lms_";
			$listMoreOrders = "/lmo_";

			// Take text and chat_id from the message
			$telegram = $this->_telegram;
			$text = $telegram->_text;
			$chatId = $telegram->_chatId;
			$messageId = $telegram->_messageId;

			// Instances Telegram user details
			$username = $telegram->Username();

			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata')->load($chatId, 'telegram_chat_id');
			$chatdata->_apiType = $chatdata->_tgBot;

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

			if ($messageId == $chatdata->getTelegramMessageId() && !($this->_isWitAi)) // prevents to reply the same request twice
				return $telegram->respondSuccess();
			else if ($chatdata->getTelegramChatId())
				$chatdata->updateChatdata('telegram_message_id', $messageId); // if this fails, it may send the same message twice

			// bot enabled/disabled
			if ($enabledBot != "1")
			{
				$disabledMessage = Mage::getStoreConfig('chatbot_enable/telegram_config/disabled_message');
				if (!empty($disabledMessage))
					$telegram->postMessage($chatId, $disabledMessage);
				return $telegram->respondSuccess();
			}

			// send feedback to user
			$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));

			// show more handler, may change the conversation state
			if ($chatdata->getTelegramConvState() == $chatdata->_listProductsState || $chatdata->getTelegramConvState() == $chatdata->_listOrdersState) // listing products
			{
				if ($chatdata->startsWith($text, $listMoreCategories)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('telegram_conv_state', $chatdata->_listCategoriesState))
					{
						$value = $this->getCommandValue($text, $listMoreCategories);
						$arr = explode("_", $value);
						$cat_id = (int)$arr[0]; // get category id
						$showMore = (int)$arr[1]; // get where listing stopped
					}
				}
				else if ($chatdata->startsWith($text, $listMoreSearch)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('telegram_conv_state', $chatdata->_searchState))
					{
						$value = $this->getCommandValue($text, $listMoreSearch);
						$arr = explode("_", $value);
						$showMore = (int)end($arr); // get where listing stopped
						$value = str_replace("_" . (string)$showMore, "", $value);
						$text = str_replace("_", " ", $value); // get search criteria
					}
				}
				else if ($chatdata->startsWith($text, $listMoreOrders)) // old checkCommandWithValue
				{
					if ($chatdata->updateChatdata('telegram_conv_state', $chatdata->_listOrdersState))
					{
						$value = $this->getCommandValue($text, $listMoreOrders);
						$showMore = (int)$value; // get where listing stopped
						$moreOrders = true;
					}
				}
//					else
//						$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState);
			}

			// instances conversation state
			$conversationState = $chatdata->getTelegramConvState();

			// mage helper
			$mageHelper = Mage::helper('core');

			if ($supportGroupId[0] == "g") // remove the 'g' from groupd id, and add '-'
				$supportGroupId = "-" . ltrim($supportGroupId, "g");

			// handle admin stuff
			//$isAdmin = $chatdata->getIsAdmin();
			// if it's a group message
			if ($telegram->messageFromGroup())
			{
				if ($chatId == $supportGroupId) // if the group sending the message is the support group
				{
					$replyMessageId = $telegram->ReplyToMessageID();
					if (!empty($replyMessageId)) // if the message is replying another message
					{
						$foreignChatdata = Mage::getModel('chatbot/chatdata')->load($replyMessageId, 'last_support_message_id');
						$replyFromUserId = $telegram->ReplyToMessageFromUserID();

						$isForeign = !empty($foreignChatdata->getLastSupportMessageId()); // check if current reply message id is saved on databse
						$isLocal = !is_null($replyFromUserId);
						if ($isLocal != $isForeign) // XOR
						{
							$admEndSupport = "/" . $chatdata->_admEndSupportCmd;
							$admBlockSupport = "/" . $chatdata->_admBlockSupportCmd;
							$admEnableSupport = "/" . $chatdata->_admEnableSupportCmd;

							if ($isLocal)
								$customerChatdata = Mage::getModel('chatbot/chatdata')->load($replyFromUserId, 'telegram_chat_id');
							else //if ($isForeign)
								$customerChatdata = $foreignChatdata;

							$handler = Mage::getModel('chatbot/api_facebook_handler'); // instances new Facebook model
							if ($text == $admEndSupport) // finish customer support
							{
								// TODO IMPORTANT remember to switch off all other supports
								if ($isLocal)
									$telegram->postMessage($replyFromUserId, $mageHelper->__("Support ended.")); // TODO
								else// if ($isForeign) // TODO make this generic
									$handler->foreignMessageFromSupport($foreignChatdata->getFacebookChatId(), $mageHelper->__("Support ended."));

								$customerChatdata->updateChatdata('telegram_conv_state', $chatdata->_startState);
								$telegram->postMessage($chatId, $mageHelper->__("Done. The customer is no longer on support."));
							}
							else if ($text == $admBlockSupport) // block user from using support
							{
								$customerChatdata->updateChatdata('enable_support', "0"); // disable support
								$telegram->postMessage($chatId, $mageHelper->__("Done. The customer is no longer able to enter support.")); // TODO
							}
							else if ($text == $admEnableSupport) // unblock user from using support
							{
								$customerChatdata->updateChatdata('enable_support', "1"); // enable support
								$telegram->postMessage($chatId, $mageHelper->__("Done. The customer is now able to enter support.")); // TODO
							}
							else // if no command, then it's replying the user
							{
								$switchedToSupport = false;
								if ($customerChatdata->getTelegramConvState() != $chatdata->_supportState) // if user isn't on support, switch to support
								{
									$customerChatdata->updateChatdata('telegram_conv_state', $chatdata->_supportState);
									$switchedToSupport = true;
								}

								if ($isLocal)
								{
									if ($switchedToSupport)
										$telegram->postMessage($replyFromUserId, $mageHelper->__("You're now on support mode."));
									$telegram->postMessage($replyFromUserId, $mageHelper->__("Message from support") . ":\n" . $text); // send message to customer TODO
								}
								else //if ($isForeign)
								{
									if ($switchedToSupport)
										$handler->foreignMessageFromSupport($foreignChatdata->getFacebookChatId(), $mageHelper->__("You're now on support mode."));
									$handler->foreignMessageFromSupport($foreignChatdata->getFacebookChatId(), $text);
								}

								$telegram->postMessage($chatId, $mageHelper->__("Message sent.")); // send message to admin group TODO
							}
						}
						else
						{
							$telegram->postMessage($chatId, $mageHelper->__("Something went wrong, please try again."));
							return $telegram->respondSuccess();
						}
					}
					else // proccess other admin commands (that aren't replying messages)
					{
						$admSend2All = "/" . $chatdata->_admSendMessage2AllCmd;

						if ($chatdata->startsWith($text, $admSend2All)) // old checkCommandWithValue
						{
							$message = trim($chatdata->getCommandValue($text, $admSend2All));
							if (!empty($message))
							{
								$chatbotcollection = Mage::getModel('chatbot/chatdata')->getCollection();
								$i = 0;
								foreach($chatbotcollection as $chatbot)
								{
									$tgChatId = $chatbot->getTelegramChatId();
									$enabled = // if backend promotional messages are disabled or if the customer wants to receive promotional messages
										(Mage::getStoreConfig('chatbot_enable/general_config/disable_promotional_messages') != "1") ||
										($chatbot->getEnablePromotionalMessages() == "1");
									if (!empty($tgChatId) && ($enabled))
									{
										$i++;
										$telegram->postMessage($tgChatId, $message); // $magehelper->__("Message from support") . ":\n" .
									}
								}
								if ($i > 0)
								{
									if ($i == 1)
										$telegram->postMessage($chatId, $mageHelper->__("One message sent."));
									else
										$telegram->postMessage($chatId, $mageHelper->__("%s messages sent.", $i));
								}
								else
									$telegram->postMessage($chatId, $mageHelper->__("No customer available to receive this message."));
							}
							else
								$telegram->postMessage($chatId, $mageHelper->__("Please use") . ' "' . $admSend2All . " " . $mageHelper->__("your message here.") . '"');
						}
					}
					return $telegram->respondSuccess();
				}
				$telegram->postMessage($chatId, $mageHelper->__("I don't work with groups.")); // TODO
				return $telegram->respondSuccess(); // ignore all group messages
			}

			// ALL CUSTOMER HANDLERS GOES AFTER HERE

			if ($chatdata->getIsLogged() == "1") // check if customer is logged
			{
				if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId()) // if is a valid customer id
				{
					if ($chatdata->getEnableTelegram() != "1")
					{
						$telegram->postMessage($chatId, $mageHelper->__("To talk with me, please enable Telegram on your account chatbot settings."));
						return $telegram->respondSuccess();
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
				$defaultReplies = Mage::getStoreConfig('chatbot_enable/telegram_config/default_replies');
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
										break;
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
										$text = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString($cmdId)['command']); // 'transform' original text into a known command
									if (!empty($message))
										$telegram->postMessage($chatId, $message);
								}
								else //if ($reply['reply_mode'] == "0")
								{
									if (!empty($message))
									{
										$telegram->postMessage($chatId, $message);
										if ($reply["stop_processing"] == "1")
											return $telegram->respondSuccess();
									}
								}
								break;
							}
						}
					}
				}
			}

			// init start command
			$chatdata->_startCmd['command'] = "/start";

			if (is_null($chatdata->getTelegramChatId()) && !$chatdata->startsWith($text, $chatdata->_startCmd['command'])) // if user isn't registred, and not using the start command // old checkCommandWithValue
			{
				$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg'); // TODO
				if ($message) // TODO
					$telegram->postMessage($chatId, $message);
				try
				{
					$hash = substr(md5(uniqid($chatId, true)), 0, 150); // TODO
					$chatdata // using magento model to insert data into database the proper way
					->setTelegramChatId($chatId)
						->setHashKey($hash) // TODO
						->save();
				}
				catch (Exception $e)
				{
					$telegram->postMessage($chatId, $chatdata->_errorMessage); // TODO
				}
				return $telegram->respondSuccess();
			}

			// init other commands (for now, no alias for telegram)
			$chatdata->_listCategoriesCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(1)['command']);
			$chatdata->_searchCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(2)['command']);
			$chatdata->_loginCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(3)['command']);
			$chatdata->_listOrdersCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(4)['command']);
			$chatdata->_reorderCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(5)['command']);
			$chatdata->_add2CartCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(6)['command']);
			$chatdata->_checkoutCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(7)['command']);
			$chatdata->_clearCartCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(8)['command']);
			$chatdata->_trackOrderCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(9)['command']);
			$chatdata->_supportCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(10)['command']);
			$chatdata->_sendEmailCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(11)['command']);
			$chatdata->_cancelCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(12)['command']);
			$chatdata->_helpCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(13)['command']);
			$chatdata->_aboutCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(14)['command']);
			$chatdata->_logoutCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(15)['command']);
			$chatdata->_registerCmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(16)['command']);
			if (!$chatdata->_cancelCmd['command']) $chatdata->_cancelCmd['command'] = "/cancel"; // it must always have a cancel command

			// init messages
			$chatdata->_errorMessage = $mageHelper->__("Something went wrong, please try again.");
			$chatdata->_cancelMessage = $mageHelper->__("To cancel, send") . " " . $chatdata->_cancelCmd['command'];
			$chatdata->_canceledMessage = $mageHelper->__("Ok, canceled.");
			$chatdata->_loginFirstMessage = $mageHelper->__("Please login first.");
			array_push($chatdata->_positiveMessages, $mageHelper->__("Ok"), $mageHelper->__("Okay"), $mageHelper->__("Cool"), $mageHelper->__("Awesome"));
			// $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)]

			// TODO DEBUG COMMANDS
			//				$temp_var = $chatdata->_startCmd['command'] . " - " .
			//				$chatdata->_listCategoriesCmd['command'] . " - " .
			//				$chatdata->_searchCmd['command'] . " - " .
			//				$chatdata->_loginCmd['command'] . " - " .
			//				$chatdata->_listOrdersCmd['command'] . " - " .
			//				$chatdata->_reorderCmd['command'] . " - " .
			//				$chatdata->_add2CartCmd['command'] . " - " .
			//				$chatdata->_checkoutCmd['command'] . " - " .
			//				$chatdata->_clearCartCmd['command'] . " - " .
			//				$chatdata->_trackOrderCmd['command'] . " - " .
			//				$chatdata->_supportCmd['command'] . " - " .
			//				$chatdata->_sendEmailCmd['command'];
			//				$telegram->postMessage($chatId, $temp_var);
			//				$telegram->postMessage($chatId, $conversationState);

			// start command
			if ($chatdata->startsWith($text, $chatdata->_startCmd['command'])) // ignore alias // old checkCommandWithValue
				//if ($text == $chatdata->_startCmd['command'])
			{
				$startdata = explode(" ", $text);
				if (is_array($startdata) && count($startdata) > 1) // has hash parameter
				{
					$chat_hash = $chatdata->load(trim($startdata[1]), 'hash_key');
					if ($chat_hash->getHashKey())
					{
						try
						{
							$chat_hash->addData(array("telegram_chat_id" => $chatId));
							$chat_hash->save();
						}catch (Exception $e){}
						$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg'); // TODO
						if ($message) // TODO
							$telegram->postMessage($chatId, $message);
					}
				}
				else if ($chatdata->getTelegramChatId()) // TODO
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_about_msg'); // TODO
					$telegram->postMessage($chatId, $message);

					//						$data = array(
					//							//'customer_id' => $customerId,
					//							'telegram_chat_id' => $chatId
					//						); // data to be insert on database
					//						$model = Mage::getModel('chatbot/chatdata')->load($chatdata->getId())->addData($data); // insert data on database
					//						$model->setId($chatdata->getId())->save(); // save (duh)
				}
				else // if customer id isnt on our database, means that we need to insert his data
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_welcome_msg'); // TODO
					if ($message) // TODO
						$telegram->postMessage($chatId, $message);
					try
					{
						$hash = substr(md5(uniqid($chatId, true)), 0, 150); // TODO
						Mage::getModel('chatbot/chatdata') // using magento model to insert data into database the proper way
						->setTelegramChatId($chatId)
							->setHashKey($hash) // TODO
							->setCreatedAt(date('Y-m-d H:i:s'))
							->save();
					}
					catch (Exception $e)
					{
						$telegram->postMessage($chatId, $chatdata->_errorMessage); // TODO
					}
				}
				return $telegram->respondSuccess();
			}

			// help command
			if ($chatdata->checkCommand($text, $chatdata->_helpCmd))
			{
				$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_help_msg'); // TODO
				if (!empty($message)) // TODO
				{
					$cmdListing = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_help_command_list');
					if ($cmdListing == "1")
						$message .= $chatdata->listTelegramCommandsMessage();

					$telegram->postMessage($chatId, $message);
				}

				return $telegram->respondSuccess();
			}

			// about command
			if ($chatdata->checkCommand($text, $chatdata->_aboutCmd))
			{
				$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_about_msg'); // TODO
				if (!empty($message))
					$telegram->postMessage($chatId, $message);

				return $telegram->respondSuccess();
			}

			// cancel command
			if ($chatdata->checkCommand($text, $chatdata->_cancelCmd)) // TODO
			{
				if ($conversationState == $chatdata->_listCategoriesState)
				{
					$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories
					$content = array('chat_id' => $chatId, 'reply_markup' => $keyb, 'text' => $chatdata->_canceledMessage);
				}
				else if ($conversationState == $chatdata->_supportState)
				{
					$content = array('chat_id' => $chatId, 'text' => $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("exiting support mode."));
					//$telegram->postMessage($chatId, $magehelper->__("Done."));
				}
				else if ($conversationState == $chatdata->_searchState)
				{
					$content = array('chat_id' => $chatId, 'text' => $chatdata->_canceledMessage);
				}
				else if ($conversationState == $chatdata->_sendEmailState)
				{
					$content = array('chat_id' => $chatId, 'text' => $chatdata->_canceledMessage);
				}
				else if ($conversationState == $chatdata->_listProductsState)
				{
					$content = array('chat_id' => $chatId, 'text' => $chatdata->_canceledMessage);
				}
				else if ($conversationState == $chatdata->_listOrdersState)
				{
					$content = array('chat_id' => $chatId, 'text' => $chatdata->_canceledMessage);
				}
				else
					$content = array('chat_id' => $chatId, 'text' => $chatdata->_errorMessage);

				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				else
					$telegram->sendMessage($content);
				return $telegram->respondSuccess();
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
							$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("adding %s to your cart.", $productName));
							$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
							if ($chatdata->addProd2Cart($cmdvalue))
								$telegram->postMessage($chatId, $mageHelper->__("Added. To checkout send") . " " . $chatdata->_checkoutCmd['command']);
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
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				else if ($notInStock)
					$telegram->postMessage($chatId, $mageHelper->__("This product is not in stock."));

				return $telegram->respondSuccess();
			}

			// states
			if ($conversationState == $chatdata->_listCategoriesState) // TODO show only in stock products
			{
				if ($cat_id)
					$_category = Mage::getModel('catalog/category')->load($cat_id);
				else
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);

				$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories
				if ($showMore == 0) // show only in the first time
					$telegram->sendMessage(array('chat_id' => $chatId, 'reply_markup' => $keyb, 'text' => $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather all products from %s for you.", $_category->getName())));
				else
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

				$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
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

						if ($productIDs)
						{
							$i = 0;
							$total = count($productIDs);

							if ($showMore < $total)
							{
								if ($showMore == 0)
								{
									if ($total == 1)
										$telegram->postMessage($chatId, $mageHelper->__("Done. This category has only one product."));
									else
										$telegram->postMessage($chatId, $mageHelper->__("Done. This category has %s products.", $total));
								}

								foreach ($productIDs as $productID)
								{
									$message = $chatdata->prepareTelegramProdMessages($productID);
									if ($message) // TODO
									{
										if ($i >= $showMore)
										{
											$image = $chatdata->loadImageContent($productID);
											if ($image)
												$telegram->sendPhoto(array('chat_id' => $chatId, 'photo' => $image, 'caption' => $message));
											else
												$telegram->postMessage($chatId, $message);

											if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
											{
												// TODO add option to list more products
												$telegram->postMessage($chatId, $mageHelper->__("To show more, send") . " " . $listMoreCategories . $_category->getId() . "_" . (string)($i + 1));
												if ($chatdata->getTelegramConvState() != $chatdata->_listProductsState)
													if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_listProductsState))
														$telegram->postMessage($chatId, $chatdata->_errorMessage);
												break;
											}
											else if (($i + 1) == $total) // if it's the last one, back to _startState
											{
												$telegram->postMessage($chatId, $mageHelper->__("And that was the last one."));
												if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
													$telegram->postMessage($chatId, $chatdata->_errorMessage);
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
							$telegram->sendMessage(array('chat_id' => $chatId, 'reply_markup' => $keyb, 'text' => $mageHelper->__("Sorry, no products found in this category.")));
					}
					else
						$errorFlag = true;
				}
				else
					$errorFlag = true;

				if ($errorFlag)
				{
					$telegram->sendMessage(array('chat_id' => $chatId, 'reply_markup' => $keyb, 'text' => $chatdata->_errorMessage));
					$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState);
				}
				return $telegram->respondSuccess();
			}
			else if ($conversationState == $chatdata->_searchState) // TODO
			{
				if ($showMore == 0) // show only in the first time
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I search for '%s' for you.", $text));
				else
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

				$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
				$errorFlag = false;
				$noProductFlag = false;
				$productIDs = $chatdata->getProductIdsBySearch($text);
				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
				{
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
					return $telegram->respondSuccess();
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
								$telegram->postMessage($chatId, $mageHelper->__("Done. I've found only one product for your criteria."));
							else
								$telegram->postMessage($chatId, $mageHelper->__("Done. I've found %s products for your criteria.", $total));
						}

						foreach ($productIDs as $productID)
						{
							$message = $chatdata->prepareTelegramProdMessages($productID);
							if ($message) // TODO
							{
								if ($i >= $showMore)
								{
									$image = $chatdata->loadImageContent($productID);
									if ($image)
										$telegram->sendPhoto(array('chat_id' => $chatId, 'photo' => $image, 'caption' => $message));
									else
										$telegram->postMessage($chatId, $message);

									if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
									{
										// TODO add option to list more products
										$telegram->postMessage($chatId, $mageHelper->__("To show more, send") . " " . $listMoreSearch . str_replace(" ", "_", $text) . "_" . (string)($i + 1));
										if ($chatdata->getTelegramConvState() != $chatdata->_listProductsState)
											if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_listProductsState))
												$telegram->postMessage($chatId, $chatdata->_errorMessage);
										break;
									}
									else if (($i + 1) == $total) // if it's the last one, back to _startState
									{
										$telegram->postMessage($chatId, $mageHelper->__("And that was the last one."));
										if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
											$telegram->postMessage($chatId, $chatdata->_errorMessage);
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
					$telegram->postMessage($chatId, $mageHelper->__("Sorry, no products found for this criteria."));

				if ($errorFlag)
				{
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
					$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState);
				}

				return $telegram->respondSuccess();
			}
			else if ($conversationState == $chatdata->_supportState)
			{
				if (!empty($supportGroupId))
				{
					$telegram->forwardMessage(array('chat_id' => $supportGroupId, 'from_chat_id' => $chatId, 'message_id' => $telegram->MessageID())); // Reply to this message to reply to the customer
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("we have sent your message to support."));
				}
				else
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				return $telegram->respondSuccess();
			}
			else if ($conversationState == $chatdata->_sendEmailState)
			{
				$telegram->postMessage($chatId, $mageHelper->__("Trying to send the email..."));
				if ($chatdata->sendEmail($text, $username))
				{
					$telegram->postMessage($chatId, $mageHelper->__("Done."));
				}
				else
					$telegram->postMessage($chatId, $mageHelper->__("Sorry, I wasn't able to send an email this time. Please try again later."));
				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				return $telegram->respondSuccess();
			}
			else if ($conversationState == $chatdata->_trackOrderState)
			{
				$errorFlag = false;
				if ($chatdata->getIsLogged() == "1")
				{
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I check the status for order %s.", $text));
					$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
					$order = Mage::getModel('sales/order')->loadByIncrementId($text);
					if ($order->getId())
					{
						if ($order->getCustomerId() == $chatdata->getCustomerId()) // not a problem if customer dosen't exist
						{
							$telegram->postMessage($chatId, $mageHelper->__("Your order status is") . " " . $order->getStatus());
						}
						else
							$errorFlag = true;
					}
					else
						$errorFlag = true;
				}
				else
					$telegram->postMessage($chatId, $chatdata->_loginFirstMessage);
				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				else if ($errorFlag)
					$telegram->postMessage($chatId, $mageHelper->__("Sorry, we couldn't find any order with this information."));
				return $telegram->respondSuccess();
			}

			// general commands
			if ($chatdata->checkCommand($text, $chatdata->_listCategoriesCmd))
			{
				$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather all categories for you."));
				$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));

				$categoryHelper = Mage::helper('catalog/category');
				$categories = $categoryHelper->getStoreCategories(); // TODO test with a store without categories
				$i = 0;
				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_listCategoriesState))
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				else if ($categories)
				{
					$option = array();
					$arr = array();
					$charCount = 0;
					foreach ($categories as $category) // TODO fix buttons max size
					{
						if ($enableEmptyCategoriesListing != "1") // unallow empty categories listing
						{
							$category = Mage::getModel('catalog/category')->load($category->getId()); // reload category because EAV Entity
							$productIDs = $category->getProductCollection()
								->addAttributeToSelect('*')
								->addAttributeToFilter('visibility', 4)
								->addAttributeToFilter('type_id', 'simple')
								->getAllIds();
						}
						else
							$productIDs = true;
						if (!empty($productIDs)) // category with no products
						{
							//$option = array( array("A", "B"), array("C", "D") );
							$catName = $category->getName();
							$charCount = $charCount + strlen($catName);
							array_push($arr, $catName); // push category name into array arr
							if ($charCount > $categoryLimit) // size limit for Telegram buttons
							{
								array_push($option, $arr); // when hits the limit, add array to options
								$arr = array(); // clear array
								$charCount = 0;
							}

							$i++;
						}
					}

					if (!empty($arr)) // if the loop ended, and there's still categories on arr
						array_push($option, $arr);

					$keyb = $telegram->buildKeyBoard($option);
					$telegram->sendMessage(array('chat_id' => $chatId, 'reply_markup' => $keyb, 'resize_keyboard' => true, 'text' => $mageHelper->__("Select a category") . ". " . $chatdata->_cancelMessage));
				}
				else if ($i == 0)
				{
					$telegram->postMessage($chatId, $mageHelper->__("No categories available at the moment, please try again later."));
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
				}
				else
					$telegram->postMessage($chatId, $chatdata->_errorMessage);

				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_checkoutCmd)) // TODO
			{
				$sessionId = null;
				$quoteId = null;
				$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I prepare the checkout for you."));
				$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
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
						$emptyCart = false;
						$message = $mageHelper->__("Products on cart") . ":\n";
						foreach ($cart->getQuote()->getItemsCollection() as $item) // TODO
						{
							$message .= $item->getQty() . "x " . $item->getProduct()->getName() . "\n" .
								$mageHelper->__("Price") . ": " . Mage::helper('core')->currency($item->getProduct()->getPrice(), true, false) . "\n\n";
						}
						$message .= $mageHelper->__("Total") . ": " .
							Mage::helper('core')->currency($ordersubtotal, true, false) . "\n\n" .
							"[" . $mageHelper->__("Checkout Here") . "](" . $cartUrl . ")";

						if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_checkoutState))
							$telegram->postMessage($chatId, $chatdata->_errorMessage);
						else
							$telegram->sendMessage(array('chat_id' => $chatId, 'parse_mode' => 'Markdown', 'text' => $message));
					}
					else if (!$chatdata->clearCart()) // try to clear cart
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
				}
				if ($emptyCart)
					$telegram->postMessage($chatId, $mageHelper->__("Your cart is empty."));
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_clearCartCmd))
			{
				$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I clear your cart."));
				$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
				$errorFlag = false;
				if ($chatdata->clearCart())
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_clearCartState))
						$errorFlag = true;
					else
						$telegram->postMessage($chatId, $mageHelper->__("Cart cleared."));
				}
				else
					$errorFlag = true;
				if ($errorFlag)
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_searchCmd))
			{
				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_searchState))
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				else
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("what do you want to search for?") . " " . $chatdata->_cancelMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_loginCmd)) // TODO
			{
				if ($chatdata->getIsLogged() != "1") // customer not logged
				{
					$hashUrl = Mage::getUrl('chatbot/settings/index/'); // get base module URL
					$hashUrl = strtok($hashUrl, '?') . "hash" . DS . $chatdata->getHashKey(); // remove magento parameters
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_loginState))
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
					else
					{
						$telegram->postMessage($chatId,
								$mageHelper->__("To login to your account, click this link") . ": " .
								$hashUrl . " . " .
								$mageHelper->__("If you want to logout from your account, just send") . " " .
								$chatdata->_logoutCmd['command']
						);
					}
				}
				else
					$telegram->postMessage($chatId, $mageHelper->__("You're already logged."));
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_logoutCmd)) // TODO
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$telegram->postMessage($chatId, $mageHelper->__("Ok, logging out."));
					$errorFlag = false;
					try
					{
						$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState);
						$chatdata->updateChatdata('is_logged', "0");
						$chatdata->updateChatdata('customer_id', ""); // TODO null?
						$chatdata->clearCart();
					}
					catch (Exception $e)
					{
						$errorFlag = true;
					}

					if ($errorFlag)
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
					else
						$telegram->postMessage($chatId, $mageHelper->__("Done."));
				}
				else
					$telegram->postMessage($chatId, $mageHelper->__("You're not logged."));

				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_registerCmd)) // TODO
			{
				$registerUrl = strtok(Mage::getUrl('customer/account/create'), '?');
				if (!empty($registerUrl))
					$telegram->postMessage($chatId, $mageHelper->__("Access %s to register a new account on our shop.", $registerUrl));
				else
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_listOrdersCmd) || $moreOrders) // TODO
			{
				if ($chatdata->getIsLogged() == "1")
				{
					if ($showMore == 0) // show only in the first time
						$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I gather your orders for listing."));
					else
						$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("listing more."));

					$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
					$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
					if ($ordersIDs)
					{
						$i = 0;
						$total = count($ordersIDs);
						if ($showMore < $total)
						{
							if ($showMore == 0)
							{
								if ($total == 1)
									$telegram->postMessage($chatId, $mageHelper->__("Done. You've only one order."));
								else
									$telegram->postMessage($chatId, $mageHelper->__("Done. I've found %s orders.", $total));
							}

							foreach($ordersIDs as $orderID)
							{
								$message = $chatdata->prepareTelegramOrderMessages($orderID);
								if ($message) // TODO
								{
									if ($i >= $showMore)
									{
										$telegram->postMessage($chatId, $message);
										if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
										{
											// TODO add option to list more orders
											$telegram->postMessage($chatId, $mageHelper->__("To show more, send") . " " . $listMoreOrders . (string)($i + 1));
											if ($chatdata->getTelegramConvState() != $chatdata->_listOrdersState)
												if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_listOrdersState))
													$telegram->postMessage($chatId, $chatdata->_errorMessage);
											break;
										}
										else if (($i + 1) == $total) // if it's the last one, back to _startState
										{
											$telegram->postMessage($chatId, $mageHelper->__("And that was the last one."));
											if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState))
												$telegram->postMessage($chatId, $chatdata->_errorMessage);
										}
									}
									$i++;
								}
							}
							if ($i == 0)
								$telegram->postMessage($chatId, $chatdata->_errorMessage);
//							else if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_listOrdersState))
//								$telegram->postMessage($chatId, $chatdata->_errorMessage);
						}
					}
					else
					{
						$telegram->postMessage($chatId, $mageHelper->__("This account has no orders."));
						return $telegram->respondSuccess();
					}
				}
				else
					$telegram->postMessage($chatId, $chatdata->_loginFirstMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->startsWith($text, $chatdata->_reorderCmd['command'])) // ignore alias TODO // old checkCommandWithValue
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("please wait while I add the products from this order to your cart."));
					$telegram->sendChatAction(array('chat_id' => $chatId, 'action' => 'typing'));
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
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
					else if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_reorderState))
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
					else // success!!
						$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("to checkout send") . " " . $chatdata->_checkoutCmd['command']);
				}
				else
					$telegram->postMessage($chatId, $chatdata->_loginFirstMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_trackOrderCmd)) // TODO
			{
				if ($chatdata->getIsLogged() == "1")
				{
					$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
					if ($ordersIDs)
					{
						if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_trackOrderState))
							$telegram->postMessage($chatId, $chatdata->_errorMessage);
						else
							$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("send the order number."));
					}
					else
						$telegram->postMessage($chatId, $mageHelper->__("Your account dosen't have any orders."));
				}
				else
					$telegram->postMessage($chatId, $chatdata->_loginFirstMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_supportCmd)) // TODO
			{
				$supportEnabled = $chatdata->getEnableSupport();
				$errorFlag = false;
				if ($supportEnabled == "1")
				{
					if ($chatdata->getFacebookConvState() != $chatdata->_supportState)
					{
						if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_supportState))
							$errorFlag = true;
						else
							$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("what do you need support for?") . " " . $chatdata->_cancelMessage);
					}
					else
						$telegram->postMessage($chatId, $mageHelper->__("You're already on support in other chat application, please close it before opening a new one."));
				}
				else
					$telegram->postMessage($chatId, $mageHelper->__("I'm sorry, you can't ask for support now. Please try again later."));

				if ($errorFlag)
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				return $telegram->respondSuccess();
			}
			else if ($chatdata->checkCommand($text, $chatdata->_sendEmailCmd)) // TODO
			{
				if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->_sendEmailState))
					$telegram->postMessage($chatId, $chatdata->_errorMessage);
				else
				{
					$telegram->postMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $mageHelper->__("write the email content."));
					$telegram->postMessage($chatId, $mageHelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $chatdata->_cancelMessage);
				}
				return $telegram->respondSuccess();
			}
			else
			{
				$chatdata->updateChatdata('telegram_conv_state', $chatdata->_startState); // back to start state
				if ($enableFinalMessage2Support == "1")
				{
					if (!empty($supportGroupId))
					{
//							if ($chatdata->getFacebookConvState() != $chatdata->_supportState) // TODO
//								$chatdata->updateChatdata('telegram_conv_state', $chatdata->_supportState);
						$telegram->forwardMessage(array('chat_id' => $supportGroupId, 'from_chat_id' => $chatId, 'message_id' => $telegram->MessageID()));
						$telegram->postMessage($chatId,
							$mageHelper->__("Sorry, I didn't understand that.") . " " .
							$mageHelper->__("Please wait while our support check your message so you can talk to a real person.")// . " " .
							//$chatdata->_cancelMessage
						); // TODO
					}
					else
						$telegram->postMessage($chatId, $chatdata->_errorMessage);
					return $telegram->respondSuccess();
				}
				else // process cases where the customer message wasn't understandable
				{
					//$witAiMatch = false;
					if (isset($this->_witAi) && !($this->_isWitAi))
					{
						$witAiConfidence = Mage::getStoreConfig('chatbot_enable/witai_config/witai_confidence');
						if (!is_numeric($witAiConfidence) || (int)$witAiConfidence > 100)
							$witAiConfidence = $defaultConfidence; // default acceptable confidence percentage

						$witResponse = $this->_witAi->getWitAIResponse($text);

						$hasIntent = true;
						if (property_exists($witResponse->entities, "telegram_intent"))
							$intents = $witResponse->entities->telegram_intent;
						else if (property_exists($witResponse->entities, "intent"))
							$intents = $witResponse->entities->intent;
						else
							$hasIntent = false;

						if ($hasIntent)
						{
							$enableConfirmMessage = Mage::getStoreConfig('chatbot_enable/witai_config/confirmation_message');
							$messages = array(
								"Okay, so you want me to list the categories for you.",			//_listCategoriesCmd
								"Okay, so you want to search for a product.",					//_searchCmd
								"Okay, so you want to login.",									//_loginCmd
								"Okay, so you want to list orders.",							//_listOrdersCmd
								"Okay, so you want to reorder.",								//_reorderCmd
								"Okay, so you want to add a product to the cart.",				//_add2CartCmd
								"Okay, so you want to checkout.",								//_checkoutCmd
								"Okay, so you want to clear your cart.",						//_clearCartCmd
								"Okay, so you want to track your order.",						//_trackOrderCmd
								"Okay, so you want support.",									//_supportCmd
								"Okay, so you want to send us an email.",						//_sendEmailCmd
								"Okay, so you want to cancel.",									//_cancelCmd
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
													if ("/" . $intent->value == $chatdata->_searchCmd['command'])
													{
														$chatdata->updateChatdata('telegram_conv_state', $chatdata->_searchState);
														//$telegram->_text = $listMoreSearch . str_replace(" ", "_", $keyword->value) . "_1";
														$telegram->_text = $keyword->value;
														$hasKeyword = true;
														break;
													}
													else if ("/" . $intent->value == $chatdata->_listCategoriesCmd['command'])
													{
														$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $keyword->value);
														if ($_category) // check if variable isn't false/empty
														{
															if ($_category->getId()) // check if is a valid category
															{
																$chatdata->updateChatdata('telegram_conv_state', $chatdata->_listCategoriesState);
																//$telegram->_text = $listMoreCategories . $_category->getId() . "_1";
																$telegram->_text = $keyword->value;
																$hasKeyword = true;
															}
														}
														break;
													}
													else if ("/" . $intent->value == $chatdata->_trackOrderCmd['command'])
													{
														if ($chatdata->getIsLogged() == "1")
														{
															$chatdata->updateChatdata('telegram_conv_state', $chatdata->_trackOrderState);
															$telegram->_text = $keyword->value;
															$hasKeyword = true;
														}
														else
														{
															$telegram->postMessage($chatId, $chatdata->_loginFirstMessage);
															$break = true;
															return $telegram->respondSuccess();
														}
														break;
													}
													else if ("/" . $intent->value == $chatdata->_supportCmd['command'])
													{
														$chatdata->updateChatdata('telegram_conv_state', $chatdata->_supportState);
														$telegram->_text = $keyword->value;
														$hasKeyword = true;
														break;
													}
													else if ("/" . $intent->value == $chatdata->_sendEmailCmd['command'])
													{
														$chatdata->updateChatdata('telegram_conv_state', $chatdata->_sendEmailState);
														$telegram->_text = $keyword->value;
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
											$telegram->_text = "/" . $key; // replace text with command
											if ($enableConfirmMessage == "1")
												$telegram->postMessage($chatId, $mageHelper->__($message)); // TODO
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

						$fallbackLimit = Mage::getStoreConfig('chatbot_enable/telegram_config/fallback_message_quantity');
						if (!empty($fallbackLimit))
						{
							$fallbackQty = (int)$chatdata->getTelegramFallbackQty();
							$fallbackQty++;
							if (!is_numeric($fallbackLimit))
								$fallbackLimit = 3;
							if ($fallbackQty >= (int)$fallbackLimit)
							{
								$fallbackMessage = Mage::getStoreConfig('chatbot_enable/telegram_config/fallback_message');
								if (!empty($fallbackMessage))
								{
									$fallbackQty = 0;
									$message = $fallbackMessage;
								}
							}
						}

						$chatdata->updateChatdata("telegram_fallback_qty", (string)$fallbackQty);
						$telegram->postMessage($chatId, $message); // TODO

						$cmdListingOnError = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_error_command_list');
						if ($cmdListingOnError == "1")
						{
							$message = $mageHelper->__("Please try one of the following commands.");
							$message .= $chatdata->listTelegramCommandsMessage();
							$telegram->postMessage($chatId, $message); // TODO
						}
					}
				}
			}
			return null;
		}
	}
?>