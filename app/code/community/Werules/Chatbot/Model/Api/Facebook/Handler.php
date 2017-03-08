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
			$chatdata->_apiType = $chatdata->_fbBot;

			if (is_null($chatdata->getFacebookChatId()))
			{ // should't happen
				return false;
			}

			// mage helper
			$magehelper = Mage::helper('core');

			$apiKey = $chatdata->getApikey($chatdata->_apiType); // get facebook bot api
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
			$hubToken = Mage::getStoreConfig('chatbot_enable/general_config/your_custom_key');
			$verify = $facebook->verifyWebhook($hubToken);
			if ($verify)
				return $verify;

			// Take text and chat_id from the message
			$originalText = $facebook->Text();
			$chatId = $facebook->ChatID();
			$messageId = $facebook->MessageID();
			$isEcho = $facebook->getEcho();

			// configs
			//$enable_witai = Mage::getStoreConfig('chatbot_enable/witai_config/enable_witai');
			$enablePredict = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_predict_commands');
			$enableLog = Mage::getStoreConfig('chatbot_enable/general_config/enable_post_log');
			$enableEmptyCategoriesListing = Mage::getStoreConfig('chatbot_enable/general_config/list_empty_categories');
			$enableFinalMessage2Support = Mage::getStoreConfig('chatbot_enable/general_config/enable_support_final_message');
			$supportGroupdId = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_support_group');
			$showMore = 0;
			$moreOrders = false;
			$listingLimit = 5;
			$listMoreCategories = "show_more_list_cat_";
			$listMoreSearch = "show_more_search_prod_";
			$listMoreOrders = "show_more_order_";

			if ($enableLog == "1") // log all posts
				Mage::log("Post Data:\n" . var_export($facebook->RawData(), true) . "\n\n", null, 'chatbot_facebook.log');

			// checking for payload
			$isPayload = false;
			$payloadContent = $facebook->getPayload();
			if ($payloadContent && empty($originalText))
			{
				$isPayload = true;
				$originalText = $payloadContent;
				$messageId = $facebook->getMessageTimestamp();
			}

			if (!empty($originalText) && !empty($chatId) && $isEcho != "true")
			{
				// Instances facebook user details
				$user_data = $facebook->UserData($chatId);
				$username = null;
				if (!empty($user_data))
					$username = $user_data['first_name'];

				$text = strtolower($originalText);

				// Instances the model class
				$chatdata = Mage::getModel('chatbot/chatdata')->load($chatId, 'facebook_chat_id');
				$chatdata->_apiType = $chatdata->_fbBot;

				if ($messageId == $chatdata->getFacebookMessageId()) // prevents to reply the same request twice
					return $facebook->respondSuccess();
				else if ($chatdata->getFacebookChatId())
					$chatdata->updateChatdata('facebook_message_id', $messageId); // if this fails, it may send the same message twice

				// send feedback to user
				$facebook->sendChatAction($chatId, "typing_on");

				// payload handler, may change the conversation state
				if ($chatdata->getFacebookConvState() == $chatdata->_listProductsState || $chatdata->getFacebookConvState() == $chatdata->_listOrdersState) // listing products
				{
					if ($chatdata->checkCommandWithValue($text, $listMoreCategories))
					{
						if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->_listCategoriesState))
						{
							$value = $this->getCommandValue($text, $listMoreCategories);
							$arr = explode(",", $value);
							$text = $arr[0];
							$showMore = (int)$arr[1];
						}
					}
					else if ($chatdata->checkCommandWithValue($text, $listMoreSearch))
					{
						if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->_searchState))
						{
							$value = $this->getCommandValue($text, $listMoreSearch);
							$arr = explode(",", $value);
							$text = $arr[0];
							$showMore = (int)$arr[1];
						}
					}
					else if ($chatdata->checkCommandWithValue($text, $listMoreOrders))
					{
						if ($chatdata->updateChatdata('facebook_conv_state', $chatdata->_listOrdersState))
						{
							$value = $this->getCommandValue($text, $listMoreOrders);
							$showMore = (int)$value; // get where listing stopped
							$moreOrders = true;
						}
					}
					else
						$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState);
				}

				// instances conversation state
				$conv_state = $chatdata->getFacebookConvState();

				// mage helper
				$magehelper = Mage::helper('core');

				// if it's a group message
				if ($chatId == $supportGroupdId)
				{
					//return $facebook->respondSuccess();
				}

				if ($chatdata->getIsLogged() == "1") // check if customer is logged
				{
					if (Mage::getModel('customer/customer')->load((int)$chatdata->getCustomerId())->getId()) // if is a valid customer id
					{
						if ($chatdata->getEnableFacebook() != "1")
						{
							$facebook->sendMessage($chatId, $magehelper->__("To talk with me, please enable Facebook Messenger on your account chatbot settings."));
							$facebook->sendChatAction($chatId, "typing_off");
							return $facebook->respondSuccess();
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
							->save();
						//$chatdata->updateChatdata('facebook_chat_id', $chat_id);
						//$chatdata->updateChatdata('hash_key', $hash);
					}
					catch (Exception $e)
					{
						$facebook->sendMessage($chatId, $chatdata->_errorMessage); // TODO
					}
					$facebook->sendChatAction($chatId, "typing_off");
					return $facebook->respondSuccess();
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
				if (!$chatdata->_cancelCmd['command']) $chatdata->_cancelCmd['command'] = "cancel"; // it must always have a cancel command

				// init messages
				$chatdata->_errorMessage = $magehelper->__("Something went wrong, please try again.");
				$chatdata->_cancelMessage = $magehelper->__("To cancel, send") . ' "' . $chatdata->_cancelCmd['command'] . '"';
				$chatdata->_canceledMessage = $magehelper->__("Ok, canceled.");
				$chatdata->_loginFirstMessage = $magehelper->__("Please login first.");
				array_push($chatdata->_positiveMessages, $magehelper->__("Ok"), $magehelper->__("Okay"), $magehelper->__("Cool"), $magehelper->__("Awesome"));
				// $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)]

				if ($enablePredict == "1" && !$isPayload) // prediction is enabled and itsn't payload
				{
					if ($conv_state == $chatdata->_startState)
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
							$chatdata->_aboutCmd['command']
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
					if ($conv_state == $chatdata->_listCategoriesState)
					{
						$message = $chatdata->_canceledMessage;
					}
					else if ($conv_state == $chatdata->_supportState)
					{
						$message = $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("exiting support mode.");
					}
					else if ($conv_state == $chatdata->_searchState)
					{
						$message = $chatdata->_canceledMessage;
					}
					else if ($conv_state == $chatdata->_sendEmailState)
					{
						$message = $chatdata->_canceledMessage;
					}
					else if ($conv_state == $chatdata->_listProductsState)
					{
						$message = $chatdata->_canceledMessage;
					}
					else if ($conv_state == $chatdata->_listOrdersState)
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
				if ($chatdata->checkCommandWithValue($text, $chatdata->_add2CartCmd['command'])) // ignore alias
				{
					$cmdvalue = $chatdata->getCommandValue($text, $chatdata->_add2CartCmd['command']);
					if ($cmdvalue) // TODO
					{
						$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
						if ($chatdata->addProd2Cart($cmdvalue))
							$facebook->sendMessage($chatId, $magehelper->__("Added. To checkout send") . ' "' . $chatdata->_checkoutCmd['command'] . '"');
						else
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					}
					return $facebook->respondSuccess();
				}

				// help command
				if ($chatdata->checkCommand($text, $chatdata->_helpCmd))
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_help_msg'); // TODO
					if ($message) // TODO
						$facebook->sendMessage($chatId, $message);
					$facebook->sendChatAction($chatId, "typing_off");
					return $facebook->respondSuccess();
				}

				// about command
				if ($chatdata->checkCommand($text, $chatdata->_aboutCmd))
				{
					$message = Mage::getStoreConfig('chatbot_enable/facebook_config/facebook_about_msg'); // TODO
					$cmdlisting = Mage::getStoreConfig('chatbot_enable/facebook_config/enable_command_list');
					if ($cmdlisting == 1)
					{
						$message .= "\n\n" . $magehelper->__("Command list") . ":\n";
						$replies = array(); // quick replies limit is 10 options
						// just getting the command string, not checking the command
						if ($chatdata->_listCategoriesCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_listCategoriesCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_listCategoriesCmd['command'])));
							$message .= $chatdata->_listCategoriesCmd['command'] . " - " . $magehelper->__("List store categories.") . "\n";
						}
						if ($chatdata->_searchCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_searchCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_searchCmd['command'])));
							$message .= $chatdata->_searchCmd['command'] . " - " . $magehelper->__("Search for products.") . "\n";
						}
						if ($chatdata->_loginCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_loginCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_loginCmd['command'])));
							$message .= $chatdata->_loginCmd['command'] . " - " . $magehelper->__("Login into your account.") . "\n";
						}
						if ($chatdata->_listOrdersCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_listOrdersCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_listOrdersCmd['command'])));
							$message .= $chatdata->_listOrdersCmd['command'] . " - " . $magehelper->__("List your personal orders.") . "\n";
						}
						//$message .= $chatdata->_reorderCmd['command'] . " - " . $magehelper->__("Reorder a order.") . "\n";
						//$message .= $chatdata->_add2CartCmd['command'] . " - " . $magehelper->__("Add product to cart.") . "\n";
						if ($chatdata->_checkoutCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_checkoutCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_checkoutCmd['command'])));
							$message .= $chatdata->_checkoutCmd['command'] . " - " . $magehelper->__("Checkout your order.") . "\n";
						}
						if ($chatdata->_clearCartCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_clearCartCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_clearCartCmd['command'])));
							$message .= $chatdata->_clearCartCmd['command'] . " - " . $magehelper->__("Clear your cart.") . "\n";
						}
						if ($chatdata->_trackOrderCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_trackOrderCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_trackOrderCmd['command'])));
							$message .= $chatdata->_trackOrderCmd['command'] . " - " . $magehelper->__("Track your order status.") . "\n";
						}
						if ($chatdata->_supportCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_supportCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_supportCmd['command'])));
							$message .= $chatdata->_supportCmd['command'] . " - " . $magehelper->__("Send message to support.") . "\n";
						}
						if ($chatdata->_sendEmailCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_sendEmailCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_sendEmailCmd['command'])));
							$message .= $chatdata->_sendEmailCmd['command'] . " - " . $magehelper->__("Send email.") . "\n";
						}
						//$message .= $chatdata->_cancelCmd['command'] . " - " . $magehelper->__("Cancel.");
						if ($chatdata->_helpCmd['command'])
						{
							array_push($replies, array('content_type' => 'text', 'title' => $chatdata->_helpCmd['command'], 'payload' => str_replace(' ', '_', $chatdata->_helpCmd['command'])));
							$message .= $chatdata->_helpCmd['command'] . " - " . $magehelper->__("Get help.") . "\n";
						}
						//$message .= $chatdata->_aboutCmd['command'] . " - " . $magehelper->__("About.");

						$facebook->sendQuickReply($chatId, $message, $replies);
					}
					else
						$facebook->sendMessage($chatId, $message);

					$facebook->sendChatAction($chatId, "typing_off");
					return $facebook->respondSuccess();
				}

				// states
				if ($conv_state == $chatdata->_listCategoriesState) // TODO show only in stock products
				{
					$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
					$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $text);
					$errorFlag = false;
					if ($_category) // check if variable isn't false/empty
					{
						if ($_category->getId()) // check if is a valid category
						{
							$noProductFlag = false;
							$productIDs = $_category->getProductCollection()
								->addAttributeToSelect('*')
								->addAttributeToFilter('visibility', 4)
								->addAttributeToFilter('type_id', 'simple')
								->getAllIds();

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
											$facebook->sendMessage($chatId, $magehelper->__("Done. This category has only one product.", $total));
										else
											$facebook->sendMessage($chatId, $magehelper->__("Done. This category has %s products.", $total));
									}

									$placeholder = Mage::getSingleton("catalog/product_media_config")->getBaseMediaUrl() . DS . "placeholder" . DS . Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
									foreach ($productIDs as $productID)
									{
										if ($i >= $showMore)
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
													'payload' => $chatdata->_add2CartCmd['command'] . $productID
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

											if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
											{
												// TODO add option to list more products
												$button = array(
													array(
														'type' => 'postback',
														'title' => $magehelper->__("Show more"),
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
												if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
													$facebook->sendMessage($chatId, $chatdata->_errorMessage);
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
								$facebook->sendMessage($chatId, $magehelper->__("Sorry, no products found in this category."));
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
				else if ($conv_state == $chatdata->_searchState)
				{
					$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
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
									$facebook->sendMessage($chatId, $magehelper->__("Done. I've found only one product for your criteria.", $total));
								else
									$facebook->sendMessage($chatId, $magehelper->__("Done. I've found %s products for your criteria.", $total));
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
										$product_url = $product->getProductUrl();
										$product_image = $product->getImageUrl();
										if (empty($product_image))
											$product_image = $placeholder;

										$button = array(
											array(
												'type' => 'postback',
												'title' => $magehelper->__("Add to cart"),
												'payload' => $chatdata->_add2CartCmd['command'] . $productID
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

										if (($i + 1) != $total && $i >= ($showMore + $listingLimit)) // if isn't the 'last but one' and $i is bigger than listing limit + what was shown last time ($show_more)
										{
											// TODO add option to list more products
											$button = array(
												array(
													'type' => 'postback',
													'title' => $magehelper->__("Show more"),
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
											if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
												$facebook->sendMessage($chatId, $chatdata->_errorMessage);
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
						$facebook->sendMessage($chatId, $magehelper->__("Sorry, no products found for this criteria."));
					else if ($errorFlag)
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listProductsState))
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
						else
							$facebook->sendGenericTemplate($chatId, $elements);

					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->_supportState)
				{
					$errorFlag = true;
					if ($supportGroupdId == $chatdata->_tgBot)
						if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chatId, $originalText, $chatdata->_apiType, $username)) // send chat id, original text and "facebook"
							$errorFlag = false;

					if ($errorFlag)
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else
						$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("we have sent your message to support."));
					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->_sendEmailState)
				{
					$facebook->sendMessage($chatId, $magehelper->__("Trying to send the email..."));
					if ($chatdata->sendEmail($text))
					{
						$facebook->sendMessage($chatId, $magehelper->__("Done."));
					}
					else
						$facebook->sendMessage($chatId, $magehelper->__("Sorry, I wasn't able to send an email this time. Please try again later."));
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					return $facebook->respondSuccess();
				}
				else if ($conv_state == $chatdata->_trackOrderState)
				{
					$errorFlag = false;
					if ($chatdata->getIsLogged() == "1")
					{
						$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
						$order = Mage::getModel('sales/order')->loadByIncrementId($text);
						if ($order->getId())
						{
							if ($order->getCustomerId() == $chatdata->getCustomerId()) // not a problem if customer dosen't exist
							{
								$facebook->sendMessage($chatId, $magehelper->__("Your order status is") . " " . $order->getStatus());
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
						$facebook->sendMessage($chatId, $magehelper->__("Sorry, we couldn't find any order with this information."));
					return $facebook->respondSuccess();
				}

				//general commands
				if ($chatdata->checkCommand($text, $chatdata->_listCategoriesCmd))
				{
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
							$message = $magehelper->__("Select a category") . ". " . $chatdata->_cancelMessage;
							$facebook->sendQuickReply($chatId, $message, $replies);
						}
					}
					else if ($i == 0)
					{
						$facebook->sendMessage($chatId, $magehelper->__("No categories available at the moment, please try again later."));
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
					$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
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
									'title' => $magehelper->__("Checkout")
								)
							);
							$emptyCart = false;
							$message = $magehelper->__("Products on cart") . ":\n";
							foreach ($cart->getQuote()->getItemsCollection() as $item) // TODO
							{
								$message .= $item->getQty() . "x " . $item->getProduct()->getName() . "\n" .
									$magehelper->__("Price") . ": " . Mage::helper('core')->currency($item->getProduct()->getPrice(), true, false) . "\n\n";
							}
							$message .= $magehelper->__("Total") . ": " . Mage::helper('core')->currency($ordersubtotal, true, false);

							if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_checkoutState))
								$facebook->sendMessage($chatId, $chatdata->_errorMessage);
							else
								$facebook->sendButtonTemplate($chatId, $message, $buttons);
						}
						else if (!$chatdata->clearCart()) // try to clear cart
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					}
					if ($emptyCart)
						$facebook->sendMessage($chatId, $magehelper->__("Your cart is empty."));
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->_clearCartCmd))
				{
					$errorFlag = false;
					if ($chatdata->clearCart())
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_clearCartState))
							$errorFlag = true;
						else
							$facebook->sendMessage($chatId, $magehelper->__("Cart cleared."));
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
						$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("what do you want to search for?") . ". " . $chatdata->_cancelMessage);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->_loginCmd))
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
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_loginState))
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
						else
							$facebook->sendButtonTemplate($chatId, $magehelper->__("To login to your account, access the link below"), $buttons);
					}
					else
						$facebook->sendMessage($chatId, $magehelper->__("You're already logged."));
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->_listOrdersCmd) || $moreOrders)
				{
					if ($chatdata->getIsLogged() == "1")
					{
						//$facebook->sendMessage($chat_id, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("let me fetch that for you."));
						$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
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
										$facebook->sendMessage($chatId, $magehelper->__("Done. You've only one order.", $total));
									else
										$facebook->sendMessage($chatId, $magehelper->__("Done. I've found %s orders.", $total));
								}

								foreach($ordersIDs as $orderID)
								{
									$buttons = array();
									$message = $chatdata->prepareFacebookOrderMessages($orderID);
									if ($message) // TODO
									{
										$button = array(
											'type' => 'postback',
											'title' => $magehelper->__("Reorder"),
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
													'title' => $magehelper->__("Show more orders"),
													'payload' => $listMoreOrders . (string)($i + 1)
												);
												array_push($buttons, $button);
												if ($chatdata->getFacebookConvState() != $chatdata->_listOrdersState)
													if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_listOrdersState))
														$facebook->sendMessage($chatId, $chatdata->_errorMessage);
												$flagBreak = true;
											}
											else if (($i + 1) == $total) // if it's the last one, back to _startState
												if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_startState))
													$facebook->sendMessage($chatId, $chatdata->_errorMessage);

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
							$facebook->sendMessage($chatId, $magehelper->__("This account has no orders."));
							return $facebook->respondSuccess();
						}
					}
					else
						$facebook->sendMessage($chatId, $chatdata->_loginFirstMessage);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommandWithValue($text, $chatdata->_reorderCmd['command'])) // ignore alias
				{
					$facebook->sendMessage($chatId, "Passei aqui");
					if ($chatdata->getIsLogged() == "1")
					{
						$facebook->sendMessage($chatId, $magehelper->__("Please wait while I check that for you."));
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
							$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("to checkout send") . ' "' . $chatdata->_checkoutCmd['command'] . '"');
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
								$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("send the order number."));
						}
						else
							$facebook->sendMessage($chatId, $magehelper->__("Your account dosen't have any orders."));
					}
					else
						$facebook->sendMessage($chatId, $chatdata->_loginFirstMessage);
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->_supportCmd))
				{
					if ($chatdata->getTelegramConvState() != $chatdata->_supportState)
					{
						if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_supportState))
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
						else
							$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("what do you need support for?") . ". " . $chatdata->_cancelMessage);
					}
					else
						$facebook->sendMessage($chatId, $magehelper->__("You're already on support in other chat application, please close it before opening a new one."));
					return $facebook->respondSuccess();
				}
				else if ($chatdata->checkCommand($text, $chatdata->_sendEmailCmd))
				{
					if (!$chatdata->updateChatdata('facebook_conv_state', $chatdata->_sendEmailState))
						$facebook->sendMessage($chatId, $chatdata->_errorMessage);
					else
					{
						$facebook->sendMessage($chatId, $chatdata->_positiveMessages[array_rand($chatdata->_positiveMessages)] . ", " . $magehelper->__("write the email content."));
						$facebook->sendMessage($chatId, $magehelper->__("By doing this you agree that we may contact you directly via chat message.") . " " . $chatdata->_cancelMessage);
					}
					return $facebook->respondSuccess();
				}
				else
				{
					if ($enableFinalMessage2Support == "1")
					{
						$errorFlag = true;
						if ($supportGroupdId == $chatdata->_tgBot)
							if (Mage::getModel('chatbot/api_telegram_handler')->foreignMessageToSupport($chatId, $originalText, $chatdata->_apiKey, $username)) // send chat id, original text and "facebook"
							{
//								if ($chatdata->getTelegramConvState() != $chatdata->_supportState) // TODO
//									$chatdata->updateChatdata('facebook_conv_state', $chatdata->_supportState);
								$errorFlag = false;
							}

						if ($errorFlag)
							$facebook->sendMessage($chatId, $chatdata->_errorMessage);
						else
							$facebook->sendMessage($chatId,
								$magehelper->__("Sorry, I didn't understand that.") . " " .
								$magehelper->__("Please wait while our support check your message so you can talk to a real person.") . " " .
								$chatdata->_cancelMessage
							); // TODO
						return $facebook->respondSuccess();
					}
					//else if ($enable_witai == "1"){}
					else
						$facebook->sendMessage($chatId, $magehelper->__("Sorry, I didn't understand that.")); // TODO
				}
			}

			return $facebook->respondSuccess();
		}
	}

?>