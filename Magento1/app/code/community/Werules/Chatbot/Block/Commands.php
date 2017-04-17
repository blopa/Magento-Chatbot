<?php
class Werules_Chatbot_Block_Commands extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_itemRendererCommands;
	protected $_itemRendererEnable;

	public function _prepareToRender()
	{
		$this->addColumn('command_id', array(
			'label' => Mage::helper('core')->__('Command'),
			'renderer' => $this->_getRendererCommands()
		));
		$this->addColumn('enable_command', array(
			'label' => Mage::helper('core')->__('Enable Command'),
			'renderer' => $this->_getRendererEnable()
		));
		$this->addColumn('command_code', array(
			'label' => Mage::helper('core')->__('Command Code'),
			'style' => 'width: 100%'
		));
		$this->addColumn('command_alias_list', array(
			'label' => Mage::helper('core')->__('Command Alias (Separated by Comma)'),
			'style' => 'width: 100%'
		));

		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('core')->__('Add');
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

	protected function _getRendererEnable()
	{
		if (!$this->_itemRendererEnable)
		{
			$this->_itemRendererEnable = $this->getLayout()->createBlock(
				'werules_chatbot/enable',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: 100%;'");
		}
		return $this->_itemRendererEnable;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getRendererCommands()->calcOptionHash($row->getData('command_id')),
			'selected="selected"'
		);
		$row->setData(
			'option_extra_attr_' . $this->_getRendererEnable()->calcOptionHash($row->getData('enable_command')),
			'selected="selected"'
		);
	}
}