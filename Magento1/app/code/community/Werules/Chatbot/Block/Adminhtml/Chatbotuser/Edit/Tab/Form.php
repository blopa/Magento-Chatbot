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

class Werules_Chatbot_Block_Adminhtml_Chatbotuser_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare the form
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Edit_Tab_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('chatbotuser_');
        $form->setFieldNameSuffix('chatbotuser');
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'chatbotuser_form',
            array('legend' => Mage::helper('werules_chatbot')->__('ChatbotUser'))
        );

        $fieldset->addField(
            'chatbotuser_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('ChatbotUser ID'),
                'name'  => 'chatbotuser_id',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'session_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Session ID'),
                'name'  => 'session_id',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'enable_promotional_messages',
            'select',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Enable Promotional Messages'),
                'name'  => 'enable_promotional_messages',
                'required'  => true,
                'class' => 'required-entry',

            'values'=> array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('werules_chatbot')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('werules_chatbot')->__('No'),
                ),
            ),
           )
        );

        $fieldset->addField(
            'enable_support',
            'select',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Enable Support'),
                'name'  => 'enable_support',
                'required'  => true,
                'class' => 'required-entry',

            'values'=> array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('werules_chatbot')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('werules_chatbot')->__('No'),
                ),
            ),
           )
        );

        $fieldset->addField(
            'admin',
            'select',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Is Admin?'),
                'name'  => 'admin',
                'required'  => true,
                'class' => 'required-entry',

            'values'=> array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('werules_chatbot')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('werules_chatbot')->__('No'),
                ),
            ),
           )
        );
        $fieldset->addField(
            'status',
            'select',
            array(
                'label'  => Mage::helper('werules_chatbot')->__('Status'),
                'name'   => 'status',
                'values' => array(
                    array(
                        'value' => 1,
                        'label' => Mage::helper('werules_chatbot')->__('Enabled'),
                    ),
                    array(
                        'value' => 0,
                        'label' => Mage::helper('werules_chatbot')->__('Disabled'),
                    ),
                ),
            )
        );
        if (Mage::app()->isSingleStoreMode()) {
            $fieldset->addField(
                'store_id',
                'hidden',
                array(
                    'name'      => 'stores[]',
                    'value'     => Mage::app()->getStore(true)->getId()
                )
            );
            Mage::registry('current_chatbotuser')->setStoreId(Mage::app()->getStore(true)->getId());
        }
        $formValues = Mage::registry('current_chatbotuser')->getDefaultValues();
        if (!is_array($formValues)) {
            $formValues = array();
        }
        if (Mage::getSingleton('adminhtml/session')->getChatbotuserData()) {
            $formValues = array_merge($formValues, Mage::getSingleton('adminhtml/session')->getChatbotuserData());
            Mage::getSingleton('adminhtml/session')->setChatbotuserData(null);
        } elseif (Mage::registry('current_chatbotuser')) {
            $formValues = array_merge($formValues, Mage::registry('current_chatbotuser')->getData());
        }
        $form->setValues($formValues);
        return parent::_prepareForm();
    }
}
