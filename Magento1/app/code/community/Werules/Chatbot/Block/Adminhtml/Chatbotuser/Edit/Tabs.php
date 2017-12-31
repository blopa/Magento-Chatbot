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

class Werules_Chatbot_Block_Adminhtml_Chatbotuser_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Initialize Tabs
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('chatbotuser_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('werules_chatbot')->__('ChatbotUser'));
    }

    /**
     * before render html
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Edit_Tabs
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'form_chatbotuser',
            array(
                'label'   => Mage::helper('werules_chatbot')->__('ChatbotUser'),
                'title'   => Mage::helper('werules_chatbot')->__('ChatbotUser'),
                'content' => $this->getLayout()->createBlock(
                    'werules_chatbot/adminhtml_chatbotuser_edit_tab_form'
                )
                ->toHtml(),
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addTab(
                'form_store_chatbotuser',
                array(
                    'label'   => Mage::helper('werules_chatbot')->__('Store views'),
                    'title'   => Mage::helper('werules_chatbot')->__('Store views'),
                    'content' => $this->getLayout()->createBlock(
                        'werules_chatbot/adminhtml_chatbotuser_edit_tab_stores'
                    )
                    ->toHtml(),
                )
            );
        }
        return parent::_beforeToHtml();
    }

    /**
     * Retrieve chatbotuser entity
     *
     * @access public
     * @return Werules_Chatbot_Model_Chatbotuser
     */
    public function getChatbotuser()
    {
        return Mage::registry('current_chatbotuser');
    }
}
