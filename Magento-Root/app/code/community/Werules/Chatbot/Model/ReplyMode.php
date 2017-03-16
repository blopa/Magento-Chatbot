<?php
class Werules_Chatbot_Model_ReplyMode
{
	/**
	 * Provide available enable as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value'=>0, 'label'=>'Mode 1'),
			array('value'=>1, 'label'=>'Mode 2'),
			array('value'=>2, 'label'=>'Mode 3')
		);
	}
}