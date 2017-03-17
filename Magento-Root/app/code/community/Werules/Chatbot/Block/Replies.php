<?php
class Werules_Chatbot_Block_Replies extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	protected $_itemRendererEnableCase;
	protected $_itemRendererEnableProcessing;
	protected $_itemRendererReplyMode;

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
		$this->addColumn('match_case', array(
			'label' => Mage::helper('core')->__('Match Case'),
			'renderer' => $this->_getRendererEnableCase()
		));
		$this->addColumn('stop_processing', array(
			'label' => Mage::helper('core')->__('Stop Processing'),
			'renderer' => $this->_getRendererEnableProcessing()
		));
		$this->addColumn('reply_mode', array(
			'label' => Mage::helper('core')->__('Reply Mode'),
			'renderer' => $this->_getRendererReplyMode()
		));
		$this->addColumn('similarity', array(
			'label' => Mage::helper('core')->__('Similarity (%)'),
			'style' => 'width: 100%',
			//'type' => 'number',
			//'maxlength' => '3',
			'class' => 'input-number validate-number validate-number-range number-range-1-100'
		));
		$this->addColumn('regular_expression', array(
			'label' => Mage::helper('core')->__('Regular Expression'),
			'style' => 'width: 100%'
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
		}
		return $this->_itemRendererEnableCase;
	}

	protected function _getRendererEnableProcessing()
	{
		if (!$this->_itemRendererEnableProcessing)
		{
			$this->_itemRendererEnableProcessing = $this->getLayout()->createBlock(
				'werules_chatbot/enable',
				//'werules_chatbot/replyMode',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: 100%;'");
		}
		return $this->_itemRendererEnableProcessing;
	}

	protected function _getRendererReplyMode()
	{
		if (!$this->_itemRendererReplyMode)
		{
			$this->_itemRendererReplyMode = $this->getLayout()->createBlock(
				//'werules_chatbot/enable',
				'werules_chatbot/replyMode',
				'',
				array('is_render_to_js_template' => true)
			)->setExtraParams("style='width: auto;'");
		}
		return $this->_itemRendererReplyMode;
	}

	protected function _prepareArrayRow(Varien_Object $row)
	{
		$row->setData(
			'option_extra_attr_' . $this->_getRendererEnableCase()->calcOptionHash($row->getData('match_case')),
			'selected="selected"'
		);
		$row->setData(
			'option_extra_attr_' . $this->_getRendererEnableProcessing()->calcOptionHash($row->getData('stop_processing')),
			'selected="selected"'
		);
		$row->setData(
			'option_extra_attr_' . $this->_getRendererReplyMode()->calcOptionHash($row->getData('reply_mode')),
			'selected="selected"'
		);
	}
}