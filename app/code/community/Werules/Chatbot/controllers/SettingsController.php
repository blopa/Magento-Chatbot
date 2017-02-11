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

	private function requestHandler()
	{
		$hash = Mage::app()->getRequest()->getParam('hash');
		if ($hash)
			$this->loginFromChatbot($hash);
	}

	private function loginFromChatbot($hash)
	{
		$success = false;
		$data = array();
		$customerid = Mage::getSingleton('customer/session')->getCustomer()->getId();
		$chatdata = Mage::getModel('chatbot/chatdata')->load($customerid, 'customer_id');
		if ($chatdata->getCustomerId()) // check if customer already is on chatdata model
		{
			while ($chatdata->getCustomerId()) // gather all data from all chatdata models
			{
				if ($chatdata->getTelegramChatId()) {
					$data["telegram_chat_id"] = $chatdata->getTelegramChatId();
					$data["telegram_conv_state"] = $chatdata->getTelegramConvState();
				}
				if ($chatdata->getFacebookChatId()) {
					$data["facebook_chat_id"] = $chatdata->getFacebookChatId();
					$data["facebook_conv_state"] = $chatdata->getFacebookConvState();
				}
				if ($chatdata->getTelegramChatId()) {
					$data["whatsapp_chat_id"] = $chatdata->getWhatsappChatId();
					$data["whatsapp_conv_state"] = $chatdata->getWhatsappConvState();
				}
				$chatdata->delete();
				$chatdata = Mage::getModel('chatbot/chatdata')->load($customerid, 'customer_id');
			}
			if ($data) // if any found, prepare to merge
			{
				$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
				if ($chatdata->getHashKey())
				{
					$data["is_logged"] = "1";
					$data["customer_id"] = $customerid;
					$chatdata->addData($data);
					$chatdata->save();
				}
				else
				{
					$data["is_logged"] = "1";
					$data["hash_key"] = $hash;
					$data["customer_id"] = $customerid;
					$chatdata->addData($data);
					$chatdata->save();
				}
				$success = true;
			}
		}
		else // if is the first time for this customer, just save it
		{
			$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
			if ($chatdata->getHashKey()) {
				$data = array(
					"customer_id" => $customerid,
					"is_logged" => "1"
				);
				$chatdata->addData($data);
				$chatdata->save();
				$success = true;
			}
		}
		// messages
		if ($success)
			Mage::getSingleton('customer/session')->addSuccess('All good :D');
		else
			Mage::getSingleton('customer/session')->addError('All bad :(');
	}
}