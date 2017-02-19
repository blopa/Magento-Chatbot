<?php
class Werules_Chatbot_Model_Chats
{
	/**
	 * Provide available chats as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value'=>0, 'label'=>'Disable'),
			array('value'=>1, 'label'=>'Telegram Group'),
			array('value'=>2, 'label'=>'Messenger Group')
//			array('value'=>3, 'label'=>'Whatsapp Group')
//			array('value'=>4, 'label'=>'WeChat Group')
		);
	}
}