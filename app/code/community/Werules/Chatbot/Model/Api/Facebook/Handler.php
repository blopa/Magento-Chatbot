<?php
	include("Messenger.php");
	include("../witAI/witAI.php");

	class Werules_Chatbot_Model_Api_Facebook_Handler extends Werules_Chatbot_Model_Chatdata
	{
		public function _construct()
		{
			//parent::_construct();
			//$this->_init('chatbot/api_facebook_handler'); // this is location of the resource file.
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
			$text = $facebook->Text();
			$chat_id = $facebook->ChatID();
			$message_id = $facebook->MessageID();
			$is_echo = $facebook->getEcho();

			// configs
			$enable_predict = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_predict_commands');

			if (!empty($text) && !empty($chat_id) && $is_echo != "true")
			{
				$text = strtolower($text);
				// Instances the model class
				$chatdata = Mage::getModel('chatbot/chatdata')->load($chat_id, 'facebook_chat_id');
				$chatdata->api_type = $chatdata->fb_bot;
				$conv_state = $chatdata->getFacebookConvState();

				if ($message_id == $chatdata->getFacebookMessageId()) // prevents to reply the same request twice
					return $facebook->respondSuccess();
				else if ($chatdata->getFacebookChatId())
					$chatdata->updateChatdata('facebook_message_id', $message_id); // if this fails, it may send the same message twice

				// send feedback to user
				$facebook->sendChatAction($chat_id, "typing_on");

				// mage helper
				$magehelper = Mage::helper('core');

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
				$chatdata->listacateg_cmd = array_map('strtolower', $chatdata->getCommandString(1));
				$chatdata->search_cmd = array_map('strtolower', $chatdata->getCommandString(2));
				$chatdata->login_cmd = array_map('strtolower', $chatdata->getCommandString(3));
				$chatdata->listorders_cmd = array_map('strtolower', $chatdata->getCommandString(4));
				$chatdata->reorder_cmd = array_map('strtolower', $chatdata->getCommandString(5));
				$chatdata->add2cart_cmd = array_map('strtolower', $chatdata->getCommandString(6));
				$chatdata->checkout_cmd = array_map('strtolower', $chatdata->getCommandString(7));
				$chatdata->clearcart_cmd = array_map('strtolower', $chatdata->getCommandString(8));
				$chatdata->trackorder_cmd = array_map('strtolower', $chatdata->getCommandString(9));
				$chatdata->support_cmd = array_map('strtolower', $chatdata->getCommandString(10));
				$chatdata->sendemail_cmd = array_map('strtolower', $chatdata->getCommandString(11));
				$chatdata->cancel_cmd = array_map('strtolower', $chatdata->getCommandString(12));
				$chatdata->help_cmd = array_map('strtolower', $chatdata->getCommandString(13));
				$chatdata->about_cmd = array_map('strtolower', $chatdata->getCommandString(14));
				if (!$chatdata->cancel_cmd) $chatdata->cancel_cmd['command'] = "Cancel"; // it must always have a cancel command

				// init messages
				$chatdata->errormsg = $magehelper->__("Something went wrong, please try again.");
				$chatdata->cancelmsg = $magehelper->__("To cancel, send") . " " . $chatdata->cancel_cmd['command'];
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
				if ($text == $chatdata->cancel_cmd['command']) // && $chatdata->cancel_cmd['command'] TODO
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

				// help command
				if ($chatdata->help_cmd['command'] && $text == $chatdata->help_cmd['command'])
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_help_msg'); // TODO
					if ($message) // TODO
						$facebook->sendMessage($chat_id, $message);
					$facebook->sendChatAction($chat_id, "typing_off");
					return $facebook->respondSuccess();
				}

				// about command
				if ($chatdata->about_cmd['command'] && $text == $chatdata->about_cmd['command'])
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_about_msg'); // TODO
					$cmdlisting = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_command_list');
					if ($cmdlisting == 1)
					{
						$message .= "\n\n" . $magehelper->__("Command list") . ":\n";
						$replies = array(); // quick replies limit is 10 options
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
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);

					if ($_category) // this works, no need to get the id
					{
						$noprodflag = false;
						$productIDs = $_category->getProductCollection()->getAllIds();
						if ($productIDs)
						{
							$i = 0;
							$elements = array();
							foreach ($productIDs as $productID)
							{
								$i++;
								$product = Mage::getModel('catalog/product')->load($productID);
								$product_url = $product->getProductUrl();
								$product_image = $product->getImageUrl();
								if (empty($product_image))
									$product_image = Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . "/placeholder/" . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeho‌​lder");

								$button = array(
									array(
										'type' => 'web_url',
										'url' => $product_url, // TODO
										'title' => $magehelper->__("Add to cart")
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

								if ($i >= 9) // facebook api generic template limit
								{
									// TODO add option to list more products
									break;
								}
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
						$facebook->sendMessage($chat_id, $chatdata->errormsg);
					return $facebook->respondSuccess();
				}

				//general commands
				if ($chatdata->listacateg_cmd['command'] && $text == $chatdata->listacateg_cmd['command'])
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

				if (true)
				{
					$message = $text;
					$result = $facebook->sendMessage($chat_id, $message);
					return $facebook->respondSuccess();
				}
			}
			else
				return $facebook->respondSuccess();
		}
	}

?>