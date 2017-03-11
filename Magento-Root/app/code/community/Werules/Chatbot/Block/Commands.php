<?php
class Werules_Chatbot_Block_Commands extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_itemRenderer;

	public function _prepareToRender()
	{
		$this->addColumn('command_id', array(
			'label' => Mage::helper('core')->__('Command'),
			'renderer' => $this->_getRenderer()
		));
		$this->addColumn('command_code', array(
			'label' => Mage::helper('core')->__('Command Code'),
			'style' => 'width: 150px'
		));
		$this->addColumn('command_alias_list', array(
			'label' => Mage::helper('core')->__('Alias'),
			'style' => 'width: 250px'
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('core')->__('Add');
	}

	protected function _getRenderer()
	{
		if (!$this->_itemRenderer)
		{
			$this->_itemRenderer = $this->getLayout()->createBlock(
				'werules_chatbot/commandsSelect',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: auto;'");
		}
		return $this->_itemRenderer;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getRenderer()->calcOptionHash($row->getData('command_id')),
			'selected="selected"'
		);
	}
}