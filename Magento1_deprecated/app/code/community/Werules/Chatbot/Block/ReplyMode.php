<?php
class Werules_Chatbot_Block_ReplyMode extends Mage_Core_Block_Html_Select
{
	public function _toHtml()
	{
		$options = Mage::getSingleton('chatbot/replyMode')->toOptionArray();
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