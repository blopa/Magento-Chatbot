<?php
class Werules_Chatbot_Block_Message_Options extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_itemRendererEnableCase;

	public function _prepareToRender()
	{
		$this->addColumn('enable_option', array(
			'label' => Mage::helper('core')->__('Enable Option'),
			'renderer' => $this->_getRendererEnableCase()
		));
		$this->addColumn('menu_option', array(
			'label' => Mage::helper('core')->__('Option Text'),
			'style' => 'width: 250px'
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
				//'werules_chatbot/replyMode',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: auto;'");
			$this->_itemRendererEnableCase->setExtraParams("onChange='werulesTogleEnable(this)'");
		}
		return $this->_itemRendererEnableCase;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getRendererEnableCase()->calcOptionHash($row->getData('enable_option')),
			'selected="selected"'
		);
	}
}