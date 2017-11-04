<?php
class Werules_Chatbot_Block_Enable extends Mage_Core_Block_Html_Select
{
	public function _toHtml()
	{
		$options = Mage::getSingleton('chatbot/enable')->toOptionArray();
		if (!$this->getOptions())
		{
			foreach ($options as $option)
			{
				$this->addOption($option['value'], $option['label']);
			}
		}

		return parent::_toHtml();
	}

	public function setInputName($value)
	{
		return $this->setName($value);
	}
}