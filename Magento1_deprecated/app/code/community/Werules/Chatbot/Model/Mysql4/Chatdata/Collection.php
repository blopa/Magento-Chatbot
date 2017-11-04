<?php
	class Werules_Chatbot_Model_Mysql4_Chatdata_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
		protected function _construct()
		{
			$this->_init('chatbot/chatdata');
		}
	}