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
		$success = false;
		$hash = Mage::app()->getRequest()->getParam('hash');
		if ($hash)
		{
			$data = array();
			$customerid = Mage::getSingleton('customer/session')->getCustomer()->getId();
			$chatdata = Mage::getModel('chatbot/chatdata')->load($customerid, 'customer_id');
			if ($chatdata->getCustomerId()) // check if customer already is on chatdata model
			{
				foreach ($chatdata as $chatdata_customer)
				{
					if ($chatdata_customer->getTelegramChatId()) {
						$data["telegram_chat_id"] = $chatdata_customer->getTelegramChatId();
						$data["telegram_conv_state"] = $chatdata_customer->getTelegramConvState();
					}
					if ($chatdata_customer->getFacebookChatId()) {
						$data["facebook_chat_id"] = $chatdata_customer->getFacebookChatId();
						$data["facebook_conv_state"] = $chatdata_customer->getFacebookConvState();
					}
					if ($chatdata_customer->getTelegramChatId()) {
						$data["whatsapp_chat_id"] = $chatdata_customer->getWhatsappChatId();
						$data["whatsapp_conv_state"] = $chatdata_customer->getWhatsappConvState();
					}
					$chatdata_customer->delete();
				}
				if ($data)
				{
					$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
					if ($chatdata->getHashKey())
					{
						$data["customer_id"] = $customerid;
						$chatdata->addData($data);
						$chatdata->save();
					}
					else
					{
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
				$chatdata = Mage::getModel('chatbot/chatdata')->load($hash, 'hash_key');
				if ($chatdata->getHashKey()) {
					$chatdata->updateChatdata("customer_id", $customerid);
					$success = true;
				}
			}
		}
		if ($success)
			Mage::getSingleton('customer/session')->addSuccess('All good :D');
		else
			Mage::getSingleton('customer/session')->addError('All bad :(');
	}
}