<?php
class Werules_Chatbot_Model_Chatdata extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		//parent::_construct();
		$this->_init('chatbot/chatdata'); // this is location of the resource file.
	}
}