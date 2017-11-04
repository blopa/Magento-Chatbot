<?php
	// class that handles all Facebook requests.
	require_once("Messenger.php");
	//$api_path = Mage::getModuleDir('', 'Werules_Chatbot') . DS . "Model" . DS . "Api" . DS . "witAI" . DS;
	//require_once($api_path . "witAI.php");

	// class Werules_Chatbot_Model_Api_Facebook_MessengerBot extends Werules_Chatbot_Model_Api_Facebook_Messenger
	class MessengerBot extends Messenger
	{
		public $_originalText;
		public $_referral;
		public $_recipientId;
		public $_chatId;
		public $_messageId;
		public $_audioPath;
		public $_isPayload = false;

		public function postMessage($chatId, $message)
		{
			return $this->sendMessage($chatId, $message);
		}
	}

