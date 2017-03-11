<?php
class Werules_Chatbot_Block_Replies extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_itemRenderer;

	public function _prepareToRender()
	{
		$this->addColumn('catch_phrase', array(
			'label' => Mage::helper('core')->__('Phrase'),
			'style' => 'width: 250px'
		));
		$this->addColumn('reply_phrase', array(
			'label' => Mage::helper('core')->__('Reply'),
			'style' => 'width: 250px'
		));
		$this->addColumn('similarity', array(
			'label' => Mage::helper('core')->__('Similarity'),
			'style' => 'width: 50px',
			//'type' => 'number',
			//'maxlength' => '3',
			'class' => 'input-number validate-number validate-number-range number-range-1-100'
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('core')->__('Add');
	}
}