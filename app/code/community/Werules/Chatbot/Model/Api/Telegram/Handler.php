<?php
	include("Telegram.php");
	//$api_path = Mage::getModuleDir('', 'Werules_Chatbot') . DS . "Model" . DS . "Api" . DS . "witAI" . DS;
	//include($api_path . "witAI.php");

	class Werules_Chatbot_Model_Api_Telegram_Handler extends Werules_Chatbot_Model_Chatdata
	{
		public function _construct()
		{
			//parent::_construct();
			//$this->_init('chatbot/api_telegram_handler'); // this is location of the resource file.
		}

		public function foreignMessageToSupport($chat_id, $text, $api_name, $customer_name)
		{
			$chatdata = Mage::getModel('chatbot/chatdata');
			if ($api_name == $chatdata->fb_bot && $chat_id)
			{
				$chatdata->load($chat_id, 'facebook_chat_id');
				if (is_null($chatdata->getFacebookChatId()))
				{ // should't happen
					$chatdata->updateChatdata("facebook_chat_id", $chat_id);
				}
			}

			$chatdata->api_type = $chatdata->tg_bot;
			$apiKey = $chatdata->getApikey($chatdata->api_type); // get telegram bot api
			if ($apiKey)
			{
				$telegram = new Telegram($apiKey);

				$magehelper = Mage::helper('core');
				$supportgroup = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_support_group');
				if (!empty($supportgroup))
				{
					try{
						if ($supportgroup[0] == "g") // remove the 'g' from groupd id, and add '-'
							$supportgroup = "-" . ltrim($supportgroup, "g");

						$message = $magehelper->__("Message via") . " " . $api_name . ":\n" . $magehelper->__("From") . ": " . $customer_name . "\n" . $text;
						$result = $telegram->sendMessage(array('chat_id' => $supportgroup, 'text' => $message));
						$mid = $result['result']['message_id'];
						if (!empty($mid))
						{
							$chatdata->updateChatdata("custom_one", $mid);
							$chatdata->updateChatdata("custom_two", $api_name);
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

		public function telegramHandler($apiKey)
		{
			// Instances the Telegram class
			$telegram = new Telegram($apiKey);

			// Take text and chat_id from the message
			$text = $telegram->Text();
			$chat_id = $telegram->ChatID();
			$message_id = $telegram->MessageID();

			// configs
			$enable_log = Mage::getStoreConfig('chatbot_enable/general_config/enable_post_log');

			if ($enable_log == "1") // log all posts
				Mage::log("Post Data:\n" . var_export($telegram->getData(), true) . "\n\n", null, 'chatbot_telegram.log');

			if (!is_null($text) && !is_null($chat_id))
			{
				// Instances the model class
				$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'telegram_chat_id');
				$chatdata->api_type = $chatdata->tg_bot;
				$conv_state = $chatdata->getTelegramConvState();

				if ($message_id == $chatdata->getTelegramMessageId()) // prevents to reply the same request twice
					return $telegram->respondSuccess();
				else if ($chatdata->getTelegramChatId())
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
						$reply_msg_id = $telegram->ReplyToMessageID();
						if (!empty($reply_msg_id)) // if the message is replying another message
						{
							$foreignchatdata = Mage::getModel('chatbot/chatdata')->load($reply_msg_id, 'custom_one');
							if (!empty($foreignchatdata->getCustomOne()))
							{
								$api_name = $foreignchatdata->getCustomTwo();
								if ($api_name == $foreignchatdata->fb_bot)
									Mage::getModel('chatbot/api_facebook_handler')->foreignMessageFromSupport($foreignchatdata->getFacebookChatId(), $text); // send chat id and the original text
							}
							else
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
						}
						return $telegram->respondSuccess();
					}
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("I don't work with groups."))); // TODO
					return $telegram->respondSuccess(); // ignore all group messages
				}

				if ($chatdata->getIsLogged() == "1") // check if customer is logged
				{
					if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId()) // if is a valid customer id
					{
						if ($chatdata->getEnableTelegram() != "1")
						{
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("To talk with me, please enable Telegram on your account chatbot settings.")));
							return $telegram->respondSuccess();
						}
					}
				}

				// init start command
				$chatdata->start_cmd['command'] = "/start";

				if (is_null($chatdata->getTelegramChatId()) && !$chatdata->checkCommandWithValue($text, $chatdata->start_cmd['command'])) // if user isn't registred, and not using the start command
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
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg)); // TODO
					}
					return $telegram->respondSuccess();
				}

				// init other commands (for now, no alias for telegram)
				$chatdata->listacateg_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(1)['command']);
				$chatdata->search_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(2)['command']);
				$chatdata->login_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(3)['command']);
				$chatdata->listorders_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(4)['command']);
				$chatdata->reorder_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(5)['command']);
				$chatdata->add2cart_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(6)['command']);
				$chatdata->checkout_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(7)['command']);
				$chatdata->clearcart_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(8)['command']);
				$chatdata->trackorder_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(9)['command']);
				$chatdata->support_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(10)['command']);
				$chatdata->sendemail_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(11)['command']);
				$chatdata->cancel_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(12)['command']);
				$chatdata->help_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(13)['command']);
				$chatdata->about_cmd['command'] = $chatdata->validateTelegramCmd("/" . $chatdata->getCommandString(14)['command']);

				if (!$chatdata->cancel_cmd['command']) $chatdata->cancel_cmd['command'] = "/cancel"; // it must always have a cancel command

				// init messages
				$chatdata->errormsg = $magehelper->__("Something went wrong, please try again.");
				$chatdata->cancelmsg = $magehelper->__("To cancel, send") . " " . $chatdata->cancel_cmd['command'];
				$chatdata->canceledmsg = $magehelper->__("Ok, canceled.");
				$chatdata->loginfirstmsg =  $magehelper->__("Please login first.");
				array_push($chatdata->positivemsg, $magehelper->__("Ok"), $magehelper->__("Okay"), $magehelper->__("Cool"), $magehelper->__("Awesome"));
				// $chatdata->positivemsg[array_rand($chatdata->positivemsg)]

				// TODO DEBUG COMMANDS
				//				$temp_var = $chatdata->start_cmd['command'] . " - " .
				//				$chatdata->listacateg_cmd['command'] . " - " .
				//				$chatdata->search_cmd['command'] . " - " .
				//				$chatdata->login_cmd['command'] . " - " .
				//				$chatdata->listorders_cmd['command'] . " - " .
				//				$chatdata->reorder_cmd['command'] . " - " .
				//				$chatdata->add2cart_cmd['command'] . " - " .
				//				$chatdata->checkout_cmd['command'] . " - " .
				//				$chatdata->clearcart_cmd['command'] . " - " .
				//				$chatdata->trackorder_cmd['command'] . " - " .
				//				$chatdata->support_cmd['command'] . " - " .
				//				$chatdata->sendemail_cmd['command'];
				//				$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $temp_var));
				//				$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $conv_state));

				// start command
				if ($chatdata->checkCommandWithValue($text, $chatdata->start_cmd['command'])) // ignore alias
				//if ($text == $chatdata->start_cmd['command'])
				{
					$startdata = explode(" ", $text);
					if (is_array($startdata) && count($startdata) > 1) // has hash parameter
					{
						$chat_hash =  $chatdata->load(trim($startdata[1]), 'hash_key');
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
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg)); // TODO
						}
					}
					return $telegram->respondSuccess();
				}

				// help command
				if ($chatdata->checkCommand($text, $chatdata->help_cmd))
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_help_msg'); // TODO
					if ($message) // TODO
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
					return $telegram->respondSuccess();
				}

				// about command
				if ($chatdata->checkCommand($text, $chatdata->about_cmd))
				{
					$message = Mage::getStoreConfig('chatbot_enable/telegram_config/telegram_about_msg'); // TODO
					$cmdlisting = Mage::getStoreConfig('chatbot_enable/telegram_config/enable_command_list');
					if ($cmdlisting == 1)
					{
						$message .= "\n\n" . $magehelper->__("Command list") . ":\n";
						if ($chatdata->listacateg_cmd['command']) $message .= $chatdata->listacateg_cmd['command'] . " - " . $magehelper->__("List store categories.") . "\n";
						if ($chatdata->search_cmd['command']) $message .= $chatdata->search_cmd['command'] . " - " . $magehelper->__("Search for products.") . "\n";
						if ($chatdata->login_cmd['command']) $message .= $chatdata->login_cmd['command'] . " - " . $magehelper->__("Login into your account.") . "\n";
						if ($chatdata->listorders_cmd['command']) $message .= $chatdata->listorders_cmd['command'] . " - " . $magehelper->__("List your personal orders.") . "\n";
						//$message .= $chatdata->reorder_cmd['command'] . " - " . $magehelper->__("Reorder a order.") . "\n";
						//$message .= $chatdata->add2cart_cmd['command'] . " - " . $magehelper->__("Add product to cart.") . "\n";
						if ($chatdata->checkout_cmd['command']) $message .= $chatdata->checkout_cmd['command'] . " - " . $magehelper->__("Checkout your order.") . "\n";
						if ($chatdata->clearcart_cmd['command']) $message .= $chatdata->clearcart_cmd['command'] . " - " . $magehelper->__("Clear your cart.") . "\n";
						if ($chatdata->trackorder_cmd['command']) $message .= $chatdata->trackorder_cmd['command'] . " - " . $magehelper->__("Track your order status.") . "\n";
						if ($chatdata->support_cmd['command']) $message .= $chatdata->support_cmd['command'] . " - " . $magehelper->__("Send message to support.") . "\n";
						if ($chatdata->sendemail_cmd['command']) $message .= $chatdata->sendemail_cmd['command'] . " - " . $magehelper->__("Send email.") . "\n";
						//$message .= $chatdata->cancel_cmd['command'] . " - " . $magehelper->__("Cancel.");
						if ($chatdata->help_cmd['command']) $message .= $chatdata->help_cmd['command'] . " - " . $magehelper->__("Get help.") . "\n";
						//$message .= $chatdata->about_cmd['command'] . " - " . $magehelper->__("About.");
					}

					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
					return $telegram->respondSuccess();
				}

				// cancel command
				if ($chatdata->checkCommand($text, $chatdata->cancel_cmd)) // TODO
				{
					if ($conv_state == $chatdata->list_cat_state)
					{
						$keyb = $telegram->buildKeyBoardHide(true); // hide keyboard built on listing categories
						$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $chatdata->canceledmsg);
					}
					else if ($conv_state == $chatdata->support_state)
					{
						$content = array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("exiting support mode."));
						//$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Done.")));
					}
					else if ($conv_state == $chatdata->search_state)
					{
						$content = array('chat_id' => $chat_id, 'text' => $chatdata->canceledmsg);
					}
					else if ($conv_state == $chatdata->send_email_state)
					{
						$content = array('chat_id' => $chat_id, 'text' => $chatdata->canceledmsg);
					}
					else
						$content = array('chat_id' => $chat_id, 'text' => $chatdata->errormsg);

					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else
						$telegram->sendMessage($content);
					return $telegram->respondSuccess();
				}

				// add2cart commands
				if ($chatdata->checkCommandWithValue($text, $chatdata->add2cart_cmd['command'])) // ignore alias
				{
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
					$cmdvalue = $chatdata->getCommandValue($text, $chatdata->add2cart_cmd['command']);
					if ($cmdvalue) // TODO
					{
						if ($chatdata->addProd2Cart($cmdvalue))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Added. To checkout send") . " " . $chatdata->checkout_cmd['command']));
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					}
					return $telegram->respondSuccess();
				}

				// states
				if ($conv_state == $chatdata->list_cat_state) // TODO show only in stock products
				{
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
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
								$message = $chatdata->prepareTelegramProdMessages($productID);
								if ($message) // TODO
								{
									$i++;
									$image = $chatdata->loadImageContent($productID);
									if ($image)
										$telegram->sendPhoto(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'photo' => $image, 'caption' => $message));
									else
										$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $message));
								}
								if ($i >= 15)
								{
									// TODO add option to list more products
									break;
								}
							}
							if ($i == 0)
								$noprodflag = true;
							if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->list_prod_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
						}
						else
							$noprodflag = true;

						if ($noprodflag)
							$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $magehelper->__("Sorry, no products found in this category.")));
					}
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $chatdata->errormsg));
						$chatdata->updateChatdata('telegram_conv_state', $chatdata->start_state);
					}
					return $telegram->respondSuccess();
				}
				else if ($conv_state == $chatdata->search_state) // TODO
				{
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
					$noprodflag = false;
					$productIDs = $chatdata->getProductIdsBySearch($text);
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else if ($productIDs)
					{
						$i = 0;
						foreach ($productIDs as $productID)
						{
							$message = $chatdata->prepareTelegramProdMessages($productID);
							if ($message) // TODO
							{
								$i++;
								$image = $chatdata->loadImageContent($productID);
								if ($image)
									$telegram->sendPhoto(array('chat_id' => $chat_id, 'photo' => $image, 'caption' => $message));
								else
									$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $message));
							}
							if ($i >= 15)
							{
								// TODO add option to list more products
								break;
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
				else if ($conv_state == $chatdata->support_state)
				{
					$telegram->forwardMessage(array('chat_id' => $supportgroup, 'from_chat_id' => $chat_id, 'message_id' => $telegram->MessageID()));
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("we have sent your message to support.")));
					return $telegram->respondSuccess();
				}
				else if ($conv_state == $chatdata->send_email_state)
				{
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Trying to send the email...")));
					if ($chatdata->sendEmail($text))
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Done.")));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Sorry, I wasn't able to send an email this time. Please try again later.")));
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					return $telegram->respondSuccess();
				}
				else if ($conv_state == $chatdata->track_order_state)
				{
					$errorflag = false;
					if ($chatdata->getIsLogged() == "1")
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
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
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->loginfirstmsg));
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->start_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else if ($errorflag)
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Sorry, we couldn't find any order with this information.")));
					return $telegram->respondSuccess();
				}

				// commands
				if ($chatdata->checkCommand($text, $chatdata->listacateg_cmd))
				{
					$helper = Mage::helper('catalog/category');
					$categories = $helper->getStoreCategories(); // TODO test with a store without categories
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->list_cat_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else if ($categories)
					{
						$option = array();
						foreach ($categories as $_category) // TODO fix buttons max size
						{
							array_push($option, $_category->getName());
						}

						$keyb = $telegram->buildKeyBoard(array($option));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $magehelper->__("Select a category")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $chatdata->cancelmsg));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->checkout_cmd)) // TODO
				{
					$sessionId = null;
					$quoteId = null;
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
					if ($chatdata->getIsLogged() == "1")
					{
						if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId())
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

							if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->checkout_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
							else
								$telegram->sendMessage(array('chat_id' => $chat_id, 'parse_mode' => 'Markdown', 'text' => $message));
						}
						else if (!$chatdata->clearCart()) // try to clear cart
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					}
					if ($emptycart)
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Your cart is empty.")));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->clearcart_cmd))
				{
					$errorflag = false;
					if ($chatdata->clearCart())
					{
						if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->clear_cart_state))
							$errorflag = true;
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Cart cleared.")));
					}
					else
						$errorflag = true;
					if ($errorflag)
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->search_cmd))
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->search_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("what do you want to search for?")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->cancelmsg));
					}
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->login_cmd)) // TODO
				{
					if ($chatdata->getIsLogged() != "1") // customer not logged
					{
						$hashlink = Mage::getUrl('chatbot/settings/index/') . "hash" . DS . $chatdata->getHashKey();
						if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->login_state))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("To login to your account, click this link") . ": " . $hashlink));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("You're already logged.")));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->listorders_cmd)) // TODO
				{
					if ($chatdata->getIsLogged() == "1")
					{
						//$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("let me fetch that for you.")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
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
								if ($i >= 15)
								{
									// TODO add option to list more orders
									break;
								}
							}
						}
						else
						{
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("This account has no orders.")));
							return $telegram->respondSuccess();
						}
						if ($i == 0)
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
						else if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->list_orders_state))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->loginfirstmsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommandWithValue($text, $chatdata->reorder_cmd['command'])) // ignore alias TODO
				{
					if ($chatdata->getIsLogged() == "1")
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Processing...")));
						$errorflag = false;
						$cmdvalue = $chatdata->getCommandValue($text, $chatdata->reorder_cmd['command']);
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
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
						else if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->reorder_state))
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
						else // success!!
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("to checkout send") . " " . $chatdata->checkout_cmd['command']));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->loginfirstmsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->trackorder_cmd)) // TODO
				{
					if ($chatdata->getIsLogged() == "1")
					{
						$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
						if ($ordersIDs)
						{
							if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->track_order_state))
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
							else
								$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("send the order number.")));
						}
						else
							$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Your account dosen't have any orders.")));
					}
					else
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->loginfirstmsg));
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->support_cmd)) // TODO
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->support_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("what do you need support for?")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->cancelmsg));
					}
					return $telegram->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->sendemail_cmd)) // TODO
				{
					if (!$chatdata->updateChatdata('telegram_conv_state', $chatdata->send_email_state))
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->errormsg));
					else
					{
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("write the email content.")));
						$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $chatdata->cancelmsg));
					}
					return $telegram->respondSuccess();
				}
				else
					$telegram->sendMessage(array('chat_id' => $chat_id, 'text' => $magehelper->__("Sorry, I didn't understand that."))); // TODO
			}

			return $telegram->respondSuccess();
		}
	}


?>