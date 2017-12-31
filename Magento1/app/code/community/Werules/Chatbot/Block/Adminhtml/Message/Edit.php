<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
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

class Werules_Chatbot_Block_Adminhtml_Message_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'werules_chatbot';
        $this->_controller = 'adminhtml_message';
        $this->_updateButton(
            'save',
            'label',
            Mage::helper('werules_chatbot')->__('Save Message')
        );
        $this->_updateButton(
            'delete',
            'label',
            Mage::helper('werules_chatbot')->__('Delete Message')
        );
        $this->_addButton(
            'saveandcontinue',
            array(
                'label'   => Mage::helper('werules_chatbot')->__('Save And Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class'   => 'save',
            ),
            -100
        );
        $this->_formScripts[] = "
            function saveAndContinueEdit() {
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * get the edit form header
     *
     * @access public
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('current_message') && Mage::registry('current_message')->getId()) {
            return Mage::helper('werules_chatbot')->__(
                "Edit Message '%s'",
                $this->escapeHtml(Mage::registry('current_message')->getMessageId())
            );
        } else {
            return Mage::helper('werules_chatbot')->__('Add Message');
        }
    }
}
