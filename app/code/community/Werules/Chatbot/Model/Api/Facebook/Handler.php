<?php
	include("Messenger.php");
	//$api_path = Mage::getModuleDir('', 'Werules_Chatbot') . DS . "Model" . DS . "Api" . DS . "witAI" . DS;
	//include($api_path . "witAI.php");

	class Werules_Chatbot_Model_Api_Facebook_Handler extends Werules_Chatbot_Model_Chatdata
	{
		public function _construct()
		{
			//parent::_construct();
			//$this->_init('chatbot/api_facebook_handler'); // this is location of the resource file.
		}

		public function foreignMessageFromSupport($chat_id, $text)
		{
			// Instances the model class
			$chatdata = Mage::getModel('chatbot/chatdata');
			$chatdata->load($chat_id, 'facebook_chat_id');
			$chatdata->api_type = $chatdata->fb_bot;

			if (is_null($chatdata->getFacebookChatId()))
			{ // should't happen
				return false;
			}

			// mage helper
			$magehelper = Mage::helper('core');

			$apiKey = $chatdata->getApikey($chatdata->api_type); // get facebook bot api
			if ($apiKey)
			{
				$facebook = new Messenger($apiKey);
				$message = $magehelper->__("Message from support") . ":\n" . $text;
				$facebook->sendMessage($chat_id, $message);
				return true;
			}

			return false;
		}

		public function facebookHandler($apiKey)
		{
			// Instances the Facebook class
			$facebook = new Messenger($apiKey);

			// Instances the witAI class
//			$witapi = Mage::getStoreConfig('chatbot_enable/witai_config/enable_witai');
//			$witai = new witAI($witapi);

			// hub challenge
			$hub_token = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
			$verify = $facebook->verifyWebhook($hub_token);
			if ($verify)
				return $verify;

			// Take text and chat_id from the message
			$text_orig = $facebook->Text();
			$chat_id = $facebook->ChatID();
			$message_id = $facebook->MessageID();
			$is_echo = $facebook->getEcho();

			// configs
			$enable_predict = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_predict_commands');
			$enable_log = Mage::getStoreConfig('chatbot_enable/general_config/enable_post_log');
			$show_more = 0;

			if ($enable_log == "1") // log all posts
				Mage::log("Post Data:\n" . var_export($facebook->RawData(), true) . "\n\n", null, 'chatbot_facebook.log');

			// checking for payload
			$is_payload = false;
			$payload = $facebook->getPayload();
			if ($payload && empty($text_orig))
			{
				$is_payload = true;
				$text_orig = $payload;
				$message_id = $facebook->getMessageTimestamp();
			}

			if (!empty($text_orig) && !empty($chat_id) && $is_echo != "true")
			{
				// Instances facebook user details
				$user_data = $facebook->UserData($chat_id);
				$username = null;
				if (!empty($user_data))
					$username = $user_data['first_name'];

				$text = strtolower($text_orig);

				// Instances the model class
				$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'facebook_chat_id');
				$chatdata->api_type = $chatdata->fb_bot;

				if ($message_id == $chatdata->getFacebookMessageId()) // prevents to reply the same request twice
					return $facebook->respondSuccess();
				else if ($chatdata->getFacebookChatId())
					$chatdata->updateChatdata('facebook_message_id', $message_id); // if this fails, it may send the same message twice

				// send feedback to user
				$facebook->sendChatAction($chat_id, "typing_on");

				// payload handler, may change the conversation state
				if ($chatdata->checkCommandWithValue($text, "show_more_list_cat_"))
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->list_cat_state))
					{
						$value = $this->getCommandValue($text, "show_more_list_cat_");
						$arr = explode(",", $value);
						$text = $arr[0];
						$show_more = (int)$arr[1];
					}
				}
				else if ($chatdata->checkCommandWithValue($text, "show_more_search_prod_"))
				{
					if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->search_state))
					{
						$value = $this->getCommandValue($text, "show_more_search_prod_");
						$arr = explode(",", $value);
						$text = $arr[0];
						$show_more = (int)$arr[1];
					}
				}

				// instances conversation state
				$conv_state = $chatdata->getFacebookConvState();

				// mage helper
				$magehelper = Mage::helper('core');

				$supportgroup = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_support_group');

				// if it's a group message
				if ($chat_id == $supportgroup)
				{
					//return $facebook->respondSuccess();
				}

				if ($chatdata->getIsLogged() == "1") // check if customer is logged
				{
					if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId()) // if is a valid customer id
					{
						if ($chatdata->getEnableFacebook() != "1")
						{
							$facebook->sendMessage($chat_id, $magehelper->__("To talk with me, please enable Facebook Messenger on your account chatbot settings."));
							$facebook->sendChatAction($chat_id, "typing_off");
							return $facebook->respondSuccess();
						}
					}
				}

				// user isnt registred HERE
				if (is_null($chatdata->getFacebookChatId())) // if user isn't registred
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_welcome_msg'); // TODO
					if ($message) // TODO
						$facebook->sendMessage($chat_id, $message);
					try
					{
						$hash = substr(md5(uniqid($chat_id, true)), 0, 150); // TODO
						$chatdata // using magento model to insert data into database the proper way
						->setFacebookChatId($chat_id)
							->setHashKey($hash) // TODO
							->save();
						//$chatdata->updateChatdata('facebook_chat_id', $chat_id);
						//$chatdata->updateChatdata('hash_key', $hash);
					}
					catch (Exception $e)
					{
						$facebook->sendMessage($chat_id, $chatdata->errormsg); // TODO
					}
					$facebook->sendChatAction($chat_id, "typing_off");
					return $facebook->respondSuccess();
				}

				// init commands
				//$chatdata->start_cmd['command'] = "Start";
				$chatdata->listacateg_cmd = $chatdata->getCommandString(1);
				$chatdata->search_cmd = $chatdata->getCommandString(2);
				$chatdata->login_cmd = $chatdata->getCommandString(3);
				$chatdata->listorders_cmd = $chatdata->getCommandString(4);
				$chatdata->reorder_cmd = $chatdata->getCommandString(5);
				$chatdata->add2cart_cmd = $chatdata->getCommandString(6);
				$chatdata->checkout_cmd = $chatdata->getCommandString(7);
				$chatdata->clearcart_cmd = $chatdata->getCommandString(8);
				$chatdata->trackorder_cmd = $chatdata->getCommandString(9);
				$chatdata->support_cmd = $chatdata->getCommandString(10);
				$chatdata->sendemail_cmd = $chatdata->getCommandString(11);
				$chatdata->cancel_cmd = $chatdata->getCommandString(12);
				$chatdata->help_cmd = $chatdata->getCommandString(13);
				$chatdata->about_cmd = $chatdata->getCommandString(14);
				if (!$chatdata->cancel_cmd['command']) $chatdata->cancel_cmd['command'] = "cancel"; // it must always have a cancel command

				// init messages
				$chatdata->errormsg = $magehelper->__("Something went wrong, please try again.");
				$chatdata->cancelmsg = $magehelper->__("To cancel, send") . ' "' . $chatdata->cancel_cmd['command'] . '"';
				$chatdata->canceledmsg = $magehelper->__("Ok, canceled.");
				$chatdata->loginfirstmsg =  $magehelper->__("Please login first.");
				array_push($chatdata->positivemsg, $magehelper->__("Ok"), $magehelper->__("Okay"), $magehelper->__("Cool"), $magehelper->__("Awesome"));
				// $chatdata->positivemsg[array_rand($chatdata->positivemsg)]

				if ($enable_predict == "1") // is enable
				{
					if ($conv_state == $chatdata->start_state)
					{
						$cmdarray = array(
							$chatdata->start_cmd['command'],
							$chatdata->listacateg_cmd['command'],
							$chatdata->search_cmd['command'],
							$chatdata->login_cmd['command'],
							$chatdata->listorders_cmd['command'],
							$chatdata->reorder_cmd['command'],
							$chatdata->add2cart_cmd['command'],
							$chatdata->checkout_cmd['command'],
							$chatdata->clearcart_cmd['command'],
							$chatdata->trackorder_cmd['command'],
							$chatdata->support_cmd['command'],
							$chatdata->sendemail_cmd['command'],
							$chatdata->cancel_cmd['command'],
							$chatdata->help_cmd['command'],
							$chatdata->about_cmd['command']
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
				if ($chatdata->checkCommand($text, $chatdata->cancel_cmd))
				{
					if ($conv_state == $chatdata->list_cat_state)
					{
						$message = $chatdata->canceledmsg;
					}
					else if ($conv_state == $chatdata->support_state)
					{
						$message = $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("exiting support mode.");
					}
					else if ($conv_state == $chatdata->search_state)
					{
						$message = $chatdata->canceledmsg;
					}
					else if ($conv_state == $chatdata->send_email_state)
					{
						$message = $chatdata->canceledmsg;
					}
					else
						$message = $chatdata->errormsg;

					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->start_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else
						$facebook->sendMessage($chat_id, $message);
					$facebook->sendChatAction($chat_id, "typing_off");
					return $facebook->respondSuccess();
				}

				// add2cart commands
				if ($chatdata->checkCommandWithValue($text, $chatdata->add2cart_cmd['command'])) // ignore alias
				{
					$cmdvalue = $chatdata->getCommandValue($text, $chatdata->add2cart_cmd['command']);
					if ($cmdvalue) // TODO
					{
						$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
						if ($chatdata->addProd2Cart($cmdvalue))
							$facebook->sendMessage($chat_id, $magehelper->__("Added. To checkout send") . ' "' . $chatdata->checkout_cmd['command'] . '"');
						else
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
					}
					return $facebook->respondSuccess();
				}

				// help command
				if ($chatdata->checkCommand($text, $chatdata->help_cmd))
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_help_msg'); // TODO
					if ($message) // TODO
						$facebook->sendMessage($chat_id, $message);
					$facebook->sendChatAction($chat_id, "typing_off");
					return $facebook->respondSuccess();
				}

				// about command
				if ($chatdata->checkCommand($text, $chatdata->about_cmd))
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_about_msg'); // TODO
					$cmdlisting = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_command_list');
					if ($cmdlisting == 1)
					{
						$message .= "\n\n" . $magehelper->__("Command list") . ":\n";
						$replies = array(); // quick replies limit is 10 options
						// just getting the command string, not checking the command
						if ($chatdata->listacateg_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->listacateg_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->listacateg_cmd['command'])));
							$message .= $chatdata->listacateg_cmd['command'] . " - " . $magehelper->__("List store categories.") . "\n";
						}
						if ($chatdata->search_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->search_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->search_cmd['command'])));
							$message .= $chatdata->search_cmd['command'] . " - " . $magehelper->__("Search for products.") . "\n";
						}
						if ($chatdata->login_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->login_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->login_cmd['command'])));
							$message .= $chatdata->login_cmd['command'] . " - " . $magehelper->__("Login into your account.") . "\n";
						}
						if ($chatdata->listorders_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->listorders_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->listorders_cmd['command'])));
							$message .= $chatdata->listorders_cmd['command'] . " - " . $magehelper->__("List your personal orders.") . "\n";
						}
						//$message .= $chatdata->reorder_cmd['command'] . " - " . $magehelper->__("Reorder a order.") . "\n";
						//$message .= $chatdata->add2cart_cmd['command'] . " - " . $magehelper->__("Add product to cart.") . "\n";
						if ($chatdata->checkout_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->checkout_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->checkout_cmd['command'])));
							$message .= $chatdata->checkout_cmd['command'] . " - " . $magehelper->__("Checkout your order.") . "\n";
						}
						if ($chatdata->clearcart_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->clearcart_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->clearcart_cmd['command'])));
							$message .= $chatdata->clearcart_cmd['command'] . " - " . $magehelper->__("Clear your cart.") . "\n";
						}
						if ($chatdata->trackorder_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->trackorder_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->trackorder_cmd['command'])));
							$message .= $chatdata->trackorder_cmd['command'] . " - " . $magehelper->__("Track your order status.") . "\n";
						}
						if ($chatdata->support_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->support_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->support_cmd['command'])));
							$message .= $chatdata->support_cmd['command'] . " - " . $magehelper->__("Send message to support.") . "\n";
						}
						if ($chatdata->sendemail_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->sendemail_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->sendemail_cmd['command'])));
							$message .= $chatdata->sendemail_cmd['command'] . " - " . $magehelper->__("Send email.") . "\n";
						}
						//$message .= $chatdata->cancel_cmd['command'] . " - " . $magehelper->__("Cancel.");
						if ($chatdata->help_cmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->help_cmd['command'], 'payload' => str_replace(' ', '_', $chatdata->help_cmd['command'])));
							$message .= $chatdata->help_cmd['command'] . " - " . $magehelper->__("Get help.") . "\n";
						}
						//$message .= $chatdata->about_cmd['command'] . " - " . $magehelper->__("About.");

						$facebook->sendQuickReply($chat_id, $message, $replies);
					}
					else
						$facebook->sendMessage($chat_id, $message);

					$facebook->sendChatAction($chat_id, "typing_off");
					return $facebook->respondSuccess();
				}

				// states
				if ($conv_state == $chatdata->list_cat_state) // TODO show only in stock products
				{
					$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
					if ($_category) // this works, no need to get the id
					{
						$noprodflag = false;
						$productIDs = $_category->getProductCollection()->getAllIds();
						if ($productIDs)
						{
							$i = 0;
							$elements = array();
							$placeholder =  Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
							foreach ($productIDs as $productID)
							{
								if ($i >= $show_more)
								{
									$product = Mage::getModel('catalog/product')->load($productID);
									$product_url = $product->getProductUrl();
									$product_image = $product->getImageUrl();
									if (empty($product_image))
										$product_image = $placeholder;

									$button = array(
										array(
											'type' => 'postback',
											'title' => $magehelper->__("Add to cart"),
											'payload' => $chatdata->add2cart_cmd['command'] . $productID
										),
										array(
											'type' => 'web_url',
											'url' => $product_url,
											'title' => $magehelper->__("Visit product's page")
										)
									);
									$element = array(
										'title' => $product->getName(),
										'item_url' => $product_url,
										'image_url' => $product_image,
										'subtitle' => $chatdata->excerpt($product->getShortDescription(), 60),
										'buttons' => $button
									);
									array_push($elements, $element);

									if ($i >= $show_more + 2) // facebook api generic template limit
									{
										// TODO add option to list more products
										$button = array(
											array(
												'type' => 'postback',
												'title' => $magehelper->__("Show more"),
												'payload' => 'show_more_list_cat_' . $text . "," . (string)$i
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
										break;
									}
								}
								$i++;
							}
							if ($i == 0)
								$noprodflag = true;
							if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->list_prod_state))
								$facebook->sendMessage($chat_id, $chatdata->errormsg);
						}
						else
							$noprodflag = true;

						if ($noprodflag)
							$facebook->sendMessage($chat_id, $magehelper->__("Sorry, no products found in this category."));
						else
							$facebook->sendGenericTemplate($chat_id, $elements);
					}
					else
					{
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
						$chatdata->updateChatdata('facebook_conv_state', $chatdata->start_state);
					}
					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->search_state)
				{
					$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
					$noprodflag = false;
					$productIDs = $chatdata->getProductIdsBySearch($text);
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->start_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else if ($productIDs)
					{
						$i = 0;
						$elements = array();
						$placeholder =  Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
						foreach ($productIDs as $productID)
						{
							$message = $chatdata->prepareFacebookProdMessages($productID);
							//Mage::helper('core')->__("Add to cart") . ": " . $this->add2cart_cmd['command'] . $product->getId();
							if ($message) // TODO
							{
								if ($i >= $show_more)
								{
									$product = Mage::getModel('catalog/product')->load($productID);
									$product_url = $product->getProductUrl();
									$product_image = $product->getImageUrl();
									if (empty($product_image))
										$product_image = $placeholder;

									$button = array(
										array(
											'type' => 'postback',
											'title' => $magehelper->__("Add to cart"),
											'payload' => $chatdata->add2cart_cmd['command'] . $productID
										),
										array(
											'type' => 'web_url',
											'url' => $product_url,
											'title' => $magehelper->__("Visit product's page")
										)
									);
									$element = array(
										'title' => $product->getName(),
										'item_url' => $product_url,
										'image_url' => $product_image,
										'subtitle' => $chatdata->excerpt($product->getShortDescription(), 60),
										'buttons' => $button
									);
									array_push($elements, $element);

									if ($i >= $show_more + 2) // facebook api generic template limit
									{
										// TODO add option to list more products
										$button = array(
											array(
												'type' => 'postback',
												'title' => $magehelper->__("Show more"),
												'payload' => 'show_more_search_prod_' . $text . "," . (string)$i
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
										break;
									}
								}
								$i++;
							}
						}
						if ($i == 0)
							$noprodflag = true;
					}
					else
						$noprodflag = true;

					if ($noprodflag)
						$facebook->sendMessage($chat_id, $magehelper->__("Sorry, no products found for this criteria."));
					else
						$facebook->sendGenericTemplate($chat_id, $elements);

					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->support_state)
				{
					$errorflag = true;
					if ($supportgroup == $chatdata->tg_bot)
						if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chat_id, $text_orig, $chatdata->api_type, $username)) // send chat id, original text and "facebook"
							$errorflag = false;

					if ($errorflag)
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else
						$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("we have sent your message to support."));
					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->send_email_state)
				{
					$facebook->sendMessage($chat_id, $magehelper->__("Trying to send the email..."));
					if ($chatdata->sendEmail($text))
					{
						$facebook->sendMessage($chat_id, $magehelper->__("Done."));
					}
					else
						$facebook->sendMessage($chat_id, $magehelper->__("Sorry, I wasn't able to send an email this time. Please try again later."));
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->start_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->track_order_state)
				{
					$errorflag = false;
					if ($chatdata->getIsLogged() == "1")
					{
						$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
						$order = Mage::getModel('sales/order')->loadByIncrementId($text);
						if ($order->getId())
						{
							if ($order->getCustomerId() == $chatdata->getCustomerId()) // not a problem if customer dosen't exist
							{
								$facebook->sendMessage($chat_id, $magehelper->__("Your order status is") . " " . $order->getStatus());
							}
							else
								$errorflag = true;
						}
						else
							$errorflag = true;
					}
					else
						$facebook->sendMessage($chat_id, $chatdata->loginfirstmsg);
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->start_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else if ($errorflag)
						$facebook->sendMessage($chat_id, $magehelper->__("Sorry, we couldn't find any order with this information."));
					return $facebook->respondSuccess();
				}

				//general commands
				if ($chatdata->checkCommand($text, $chatdata->listacateg_cmd))
				{
					$helper = Mage::helper('catalog/category');
					$categories = $helper->getStoreCategories(); // TODO test with a store without categories
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->list_cat_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else if ($categories)
					{
						$replies = array();
						foreach ($categories as $_category) // TODO fix buttons max size
						{
							//array_push($option, $_category->getName());
							$cat_name = $_category->getName();
							if (!empty($cat_name))
							{
								$reply = array(
									'content_type' => 'text',
									'title' => $cat_name,
									'payload' => 'CAT_PAYLOAD' // TODO
								);
								array_push($replies, $reply);
							}
						}
						if (!empty($replies))
						{
							$message = $magehelper->__("Select a category") . ". " . $chatdata->cancelmsg;
							$facebook->sendQuickReply($chat_id, $message, $replies);
						}
					}
					else
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->checkout_cmd))
				{
					$sessionId = null;
					$quoteId = null;
					$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
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
							$buttons = array(
								array(
									'type' => 'web_url',
									'url' => $cartUrl,
									'title' => $magehelper->__("Checkout")
								)
							);
							$emptycart = false;
							$message = $magehelper->__("Products on cart") . ":\n";
							foreach ($cart->getQuote()->getItemsCollection() as $item) // TODO
							{
								$message .= $item->getQty() . "x " . $item->getProduct()->getName() . "\n" .
									$magehelper->__("Price") . ": " . Mage::helper('core')->currency($item->getProduct()->getPrice(), true, false) . "\n\n";
							}
							$message .= $magehelper->__("Total") . ": " . Mage::helper('core')->currency($ordersubtotal, true, false);

							if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->checkout_state))
								$facebook->sendMessage($chat_id, $chatdata->errormsg);
							else
								$facebook->sendButtonTemplate($chat_id, $message, $buttons);
						}
						else if (!$chatdata->clearCart()) // try to clear cart
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
					}
					if ($emptycart)
						$facebook->sendMessage($chat_id, $magehelper->__("Your cart is empty."));
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->clearcart_cmd))
				{
					$errorflag = false;
					if ($chatdata->clearCart())
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->clear_cart_state))
							$errorflag = true;
						else
							$facebook->sendMessage($chat_id, $magehelper->__("Cart cleared."));
					}
					else
						$errorflag = true;
					if ($errorflag)
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->search_cmd))
				{
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->search_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else
					{
						$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("what do you want to search for?"));
						$facebook->sendMessage($chat_id, $chatdata->cancelmsg);
					}
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->login_cmd))
				{
					if ($chatdata->getIsLogged() != "1") // customer not logged
					{
						$hashlink = Mage::getUrl('chatbot/settings/index/') . "hash" . DS . $chatdata->getHashKey();
						$buttons = array(
							array(
								'type' => 'web_url',
								'url' => $hashlink,
								'title' => $magehelper->__("Login")
							)
						);
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->login_state))
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
						else
							$facebook->sendButtonTemplate($chat_id, $magehelper->__("To login to your account, access the link below"), $buttons);
					}
					else
						$facebook->sendMessage($chat_id, $magehelper->__("You're already logged."));
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->listorders_cmd))
				{
					if ($chatdata->getIsLogged() == "1")
					{
						//$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("let me fetch that for you."));
						$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
						$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
						$i = 0;
						if ($ordersIDs)
						{
							foreach($ordersIDs as $orderID)
							{
								$message = $chatdata->prepareFacebookOrderMessages($orderID);
								if ($message) // TODO
								{
									$buttons = array(
										array(
											'type' => 'postback',
											'title' => $magehelper->__("Reorder"),
											'payload' => $chatdata->reorder_cmd['command'] . $orderID
										)
									);
									$i++;
									$facebook->sendButtonTemplate($chat_id, $message, $buttons);
								}
								if ($i >= 9)
								{
									// TODO add option to list more orders
									break;
								}
							}
						}
						else
						{
							$facebook->sendMessage($chat_id, $magehelper->__("This account has no orders."));
							return $facebook->respondSuccess();
						}
						if ($i == 0)
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
						else if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->list_orders_state))
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
					}
					else
						$facebook->sendMessage($chat_id, $chatdata->loginfirstmsg);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommandWithValue($text, $chatdata->reorder_cmd['command'])) // ignore alias
				{
					$facebook->sendMessage($chat_id, "Passei aqui");
					if ($chatdata->getIsLogged() == "1")
					{
						$facebook->sendMessage($chat_id, $magehelper->__("Processing..."));
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
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
						else if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->reorder_state))
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
						else // success!!
							$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("to checkout send") . ' "' . $chatdata->checkout_cmd['command'] . '"');
					}
					else
						$facebook->sendMessage($chat_id, $chatdata->loginfirstmsg);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->trackorder_cmd))
				{
					if ($chatdata->getIsLogged() == "1")
					{
						$ordersIDs = $chatdata->getOrdersIdsFromCustomer();
						if ($ordersIDs)
						{
							if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->track_order_state))
								$facebook->sendMessage($chat_id, $chatdata->errormsg);
							else
								$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("send the order number."));
						}
						else
							$facebook->sendMessage($chat_id, $magehelper->__("Your account dosen't have any orders."));
					}
					else
						$facebook->sendMessage($chat_id, $chatdata->loginfirstmsg);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->support_cmd))
				{
					if ($chatdata->getTelegramConvState() != $chatdata->support_state)
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->support_state))
							$facebook->sendMessage($chat_id, $chatdata->errormsg);
						else
						{
							$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("what do you need support for?"));
							$facebook->sendMessage($chat_id, $chatdata->cancelmsg);
						}
					}
					else
						$facebook->sendMessage($chat_id, $magehelper->__("You're already on support in other chat application, please close it before opening a new one."));
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->sendemail_cmd))
				{
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->send_email_state))
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					else
					{
						$facebook->sendMessage($chat_id, $chatdata->positivemsg[array_rand($chatdata->positivemsg)] . ", " . $magehelper->__("write the email content."));
						$facebook->sendMessage($chat_id, $magehelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $chatdata->cancelmsg);
					}
					return $facebook->respondSuccess();
				}
				else
					$facebook->sendMessage($chat_id, $magehelper->__("Sorry, I didn't understand that.")); // TODO
			}

			return $facebook->respondSuccess();
		}
	}

?>