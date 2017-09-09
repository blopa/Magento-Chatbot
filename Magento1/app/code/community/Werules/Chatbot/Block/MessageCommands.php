<?php
class Werules_Chatbot_Block_MessageCommands extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_itemRendererEnableCase;
    protected $_itemRendererCommands;

	public function _prepareToRender()
	{
		$this->addColumn('enable_option', array(
			'label' => Mage::helper('core')->__('Enable Command'),
			'renderer' => $this->_getRendererEnableCase()
		));
		$this->addColumn('command_id', array(
			'label' => Mage::helper('core')->__('Command'),
			'renderer' => $this->_getRendererCommands()
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('core')->__('Add');
	}

	protected function _getRendererEnableCase()
	{
		if (!$this->_itemRendererEnableCase)
		{
			$this->_itemRendererEnableCase = $this->getLayout()->createBlock(
				'werules_chatbot/enable',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: auto;'");
			$this->_itemRendererEnableCase->setExtraParams("onChange='werulesTogleEnable(this)'");
		}
		return $this->_itemRendererEnableCase;
	}

	protected function _getRendererCommands()
	{
		if (!$this->_itemRendererCommands)
		{
			$this->_itemRendererCommands = $this->getLayout()->createBlock(
				'werules_chatbot/commandsSelect',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: 100%;'");
		}
		return $this->_itemRendererCommands;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getRendererCommands()->calcOptionHash($row->getData('command_id')),
			'selected="selected"'
		);
	}
}