<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2017
 *
 * This file is part of Werules/Chatbot.
 *
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// this class is used to replace Magento own class that contains a glitch
class Werules_Chatbot_Model_Adminhtml_Serialized extends Mage_Adminhtml_Model_System_Config_Backend_Serialized
{
    protected function _afterLoad()
    {
        if (!is_array($this->getValue())) {
            $serializedValue = $this->getValue();
            $unserializedValue = false;
            if ($serializedValue) {
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
