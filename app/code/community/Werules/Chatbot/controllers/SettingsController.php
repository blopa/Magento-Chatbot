<?php
class Werules_Chatbot_SettingsController extends Mage_Core_Controller_Front_Action {
	public function preDispatch() // function that makes the settings page only available when the user is logged in
	{
		parent::preDispatch();
		$loginUrl = Mage::helper('customer')->getLoginUrl();

		if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
			$this->setFlag('', self::FLAG_NO_DISPATCH, true);
		}
	}
	public function indexAction() // main action, sets layout and page title
	{
		$this->loadLayout();
		// some code
		$this->requestHandler();

		$this->_initLayoutMessages('customer/session');
		$this->renderLayout();
	}

	public function saveAction()
	{
		$magehelper = Mage::helper('core');
		$postData = $this->getRequest()->getPost(); // get all post data
		if ($postData)
		{
			$clientid = Mage::getSingleton('customer/session')->getCustomer()->getId(); // get customer id
			$chatdata = Mage::getModel('chatbot/chatdata')->load($clientid, 'customer_id'); // load profile info from customer id
			try
			{
				$data = array(
					"enable_telegram" => ((isset($postData['enable_telegram'])) ? 1 : 0),
					"enable_facebook" => ((isset($postData['enable_facebook'])) ? 1 : 0)
					//"enable_whatsapp" => ((isset($postData['enable_whatsapp'])) ? 1 : 0),
					//"enable_wechat" => ((isset($postData['enable_wechat'])) ? 1 : 0)
				);
				if (!$chatdata->getCustomerId()) // attach class to customer id
				{
					$data["hash_key"] = substr(md5(uniqid(str_shuffle("e09rgu89y54h"), true)), 0, 150); // TODO
					$data["customer_id"] = $clientid;
				}
				$chatdata->addData($data);
				$chatdata->save();

				Mage::getSingleton('customer/session')->addSuccess($magehelper->__("Chatbot settings saved successfully.")); // throw success message to the html page
			}
			catch (Exception $e)
			{
				Mage::getSingleton('customer/session')->addError($magehelper->__("Something went wrong, please try again.")); // throw error message to the html page
			}
		}
		$this->_redirect('chatbot/settings/index'); // redirect customer to settings page
	}

	private function requestHandler()
	{
		$hash = Mage::app()->getRequest()->getParam('hash');
		if ($hash)
			$this->loginFromChatbot($hash);
	}

	private function loginFromChatbot($hash)
	{
		$success = false;
		$error = false;
		$logged = true;
		$data = array();
		$magehelper = Mage::helper('core');
		$chatdataHash = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
		if ($chatdataHash->getIsLogged() == "0")
		{
			$logged = false;
			$customerid = Mage::getSingleton('customer/session')->getCustomer()->getId();
			$chatdata = Mage::getModel('chatbot/chatdata')->load($customerid, 'customer_id');
			if ($chatdata->getCustomerId()) // check if customer already is on chatdata model
			{
				try
				{
					while ($chatdata->getCustomerId()) // gather all data from all chatdata models
					{
						if ($chatdata->getTelegramChatId() && $chatdata->getFacebookChatId() && $chatdata->getWhatsappChatId() && $chatdata->getWechatChatId())
							break;
						if ($chatdata->getTelegramChatId()) {
							$data["telegram_chat_id"] = $chatdata->getTelegramChatId();
							$data["telegram_message_id"] = $chatdata->getTelegramMessageId();
							$data["telegram_conv_state"] = $chatdata->getTelegramConvState();
							$data["telegram_support_reply_chat_id"] = $chatdata->getTelegramSupportReplyChatId();
						}
						if ($chatdata->getFacebookChatId()) {
							$data["facebook_chat_id"] = $chatdata->getFacebookChatId();
							$data["facebook_message_id"] = $chatdata->getFacebookMessageId();
							$data["facebook_conv_state"] = $chatdata->getFacebookConvState();
							$data["facebook_support_reply_chat_id"] = $chatdata->getFacebookSupportReplyChatId();
						}
						if ($chatdata->getWhatsappChatId()) {
							$data["whatsapp_chat_id"] = $chatdata->getWhatsappChatId();
							$data["whatsapp_message_id"] = $chatdata->getWhatsappMessageId();
							$data["whatsapp_conv_state"] = $chatdata->getWhatsappConvState();
							$data["whatsapp_support_reply_chat_id"] = $chatdata->getWhatsappSupportReplyChatId();
						}
						if ($chatdata->getWechatChatId()) {
							$data["wechat_chat_id"] = $chatdata->getWechatChatId();
							$data["wechat_message_id"] = $chatdata->getWechatMessageId();
							$data["wechat_conv_state"] = $chatdata->getWechatpConvState();
							$data["wechat_support_reply_chat_id"] = $chatdata->getWechatSupportReplyChatId();
						}
						if (!isset($data["created_at"]))
							$data["created_at"] = $chatdata->getCreatedAt();
						$chatdata->delete();
						$chatdata = Mage::getModel('chatbot/chatdata')->load($customerid, 'customer_id'); // reload
					}
					if (!empty($data)) // if any found, prepare to merge
					{
						$data["updated_at"] = date('Y-m-d H:i:s');
						$data["is_logged"] = "1";
						$data["customer_id"] = $customerid;

						$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
						if (!$chatdata->getHashKey())
							$data["hash_key"] = $hash;

						$chatdata->addData($data);
						$chatdata->save();
						$success = true;
					}
				}
				catch (Exception $e)
				{
					$error = true;
				}
			}
			else // if is the first time for this customer, just save it
			{
				try
				{
					$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
					if ($chatdata->getHashKey()) {
						$data = array(
							"customer_id" => $customerid,
							"is_logged" => "1",
							"updated_at" => date('Y-m-d H:i:s')
						);
						$chatdata->addData($data);
						$chatdata->save();
						$success = true;
					}
				}
				catch (Exception $e)
				{
					$error = true;
				}
			}
		}
		// messages
		if ($success)
			Mage::getSingleton('customer/session')->addSuccess($magehelper->__("Your account is now attached with our chatbot."));
		if ($error)
			Mage::getSingleton('customer/session')->addError($magehelper->__("Something went wrong, please try again."));
		if ($logged)
			Mage::getSingleton('customer/session')->addNotice($magehelper->__("You're already logged."));
	}
}