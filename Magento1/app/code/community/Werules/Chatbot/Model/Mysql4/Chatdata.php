<?php
class Werules_Chatbot_Model_Mysql4_Chatdata extends Mage_Core_Model_Resource_Db_Abstract
{
	public function _construct()
	{   
		$this->_init('chatbot/chatdata', 'entity_id');  // here entity_id is the primary of the table of our module. And chatbot/chatdata, is the magento table name as mentioned in the config.xml file.
	}
}