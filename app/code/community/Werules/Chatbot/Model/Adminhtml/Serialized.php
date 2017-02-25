<?php

class Werules_Chatbot_Model_Adminhtml_Serialized extends Mage_Adminhtml_Model_System_Config_Backend_Serialized
{
	protected function _afterLoad()
	{
		if (!is_array($this->getValue())) {
			$serializedValue = $this->getValue();
			$unserializedValue = false;
			if (!empty($serializedValue)) {
				try {
					$unserializedValue = Mage::helper('core/unserializeArray')
						->unserialize((string)$serializedValue); // fix magento unserializing bug
				} catch (Exception $e) {
					Mage::logException($e);
				}
			}
			$this->setValue($unserializedValue);
		}
	}

	protected function _beforeSave()
	{
		$value = $this->getValue();
		if (is_array($value)) {
			unset($value['__empty']);
		}
		$this->setValue($value);

		if (is_array($this->getValue()))
		{
			$this->setValue(serialize($this->getValue()));
		}
	}
}
