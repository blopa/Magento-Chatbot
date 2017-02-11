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
		$debug = "<br>";
		$success = false;
		$hash = Mage::app()->getRequest()->getParam('hash');
		if ($hash)
		{
			$data = array();
			$customerid = Mage::getSingleton('customer/session')->getCustomer()->getId();
			$chatdata = Mage::getModel('chatbot/chatdata')->load($customerid, 'customer_id');
			if ($chatdata->getCustomerId()) // check if customer already is on chatdata model
			{
				$debug .= " 1 - ";
				while ($chatdata->getCustomerId())
				{
					$debug .= " 2 - ";
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
				if ($data)
				{
					$debug .= " 3 - ";
					$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
					if ($chatdata->getHashKey())
					{
						$debug .= " 4 - ";
						$data["customer_id"] = $customerid;
						$chatdata->addData($data);
						$chatdata->save();
					}
					else
					{
						$debug .= " 5 - ";
						$data["hash_key"] = $hash;
						$data["customer_id"] = $customerid;
						$chatdata->addData($data);
						$chatdata->save();
					}
					$success = true;
				}
			}
			else
			{
				$debug .= " 6 - ";
				$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
				if ($chatdata->getHashKey()) {
					$debug .= " 7 - ";
					$chatdata->updateChatdata("customer_id", $customerid);
					$success = true;
				}
			}
		}
		if ($success)
			Mage::getSingleton('customer/session')->addSuccess('All good :D');
		else
			Mage::getSingleton('customer/session')->addError('All bad :(');
		Mage::getSingleton('customer/session')->addError('da um check ->' . $debug);
	}
}