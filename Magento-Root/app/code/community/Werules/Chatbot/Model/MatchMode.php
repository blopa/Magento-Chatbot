<?php
class Werules_Chatbot_Model_MatchMode
{
	/**
	 * Provide available enable as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value'=>0, 'label'=>'Similarity'),
			array('value'=>1, 'label'=>'Starts With'),
			array('value'=>2, 'label'=>'Ends With'),
			array('value'=>3, 'label'=>'Contains'),
			array('value'=>4, 'label'=>'Match Regular Expression'),
			array('value'=>5, 'label'=>'Equals to')
		);
	}
}