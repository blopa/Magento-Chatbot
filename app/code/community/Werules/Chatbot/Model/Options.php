<?php
class Werules_Chatbot_Model_Options
{
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value'=>0, 'label'=>'List Categories'),
			array('value'=>1, 'label'=>'Search For Product'),
			array('value'=>2, 'label'=>'Login'),
			array('value'=>3, 'label'=>'List Orders'),
			array('value'=>4, 'label'=>'Reorder'),
			array('value'=>5, 'label'=>'Add Product To Cart'),
			array('value'=>6, 'label'=>'Checkout On Site'),
			array('value'=>7, 'label'=>'Track Order Status'),
			array('value'=>8, 'label'=>'Talk to Support'),
			array('value'=>9, 'label'=>'Send Email')
		);
	}
}