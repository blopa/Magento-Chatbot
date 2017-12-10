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

class Werules_Chatbot_Block_Adminhtml_Chatbotapi_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare the form
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotapi_Edit_Tab_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('chatbotapi_');
        $form->setFieldNameSuffix('chatbotapi');
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'chatbotapi_form',
            array('legend' => Mage::helper('werules_chatbot')->__('ChatbotAPI'))
        );

        $fieldset->addField(
            'chatbotapi_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('ChatbotAPI ID'),
                'name'  => 'chatbotapi_id',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'hash_key',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Hash Key'),
                'name'  => 'hash_key',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'logged',
            'select',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Logged?'),
                'name'  => 'logged',
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
            'enabled',
            'select',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Enabled?'),
                'name'  => 'enabled',
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
            'chatbot_type',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Chatbot Type'),
                'name'  => 'chatbot_type',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'chat_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Chat ID'),
                'name'  => 'chat_id',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'conversation_state',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Conversation State'),
                'name'  => 'conversation_state',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'fallback_qty',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Fallback Quantity'),
                'name'  => 'fallback_qty',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'chatbotuser_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Chatbotuser ID'),
                'name'  => 'chatbotuser_id',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'last_command_details',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Last Command Details'),
                'name'  => 'last_command_details',
                'required'  => true,
                'class' => 'required-entry',

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
            Mage::registry('current_chatbotapi')->setStoreId(Mage::app()->getStore(true)->getId());
        }
        $formValues = Mage::registry('current_chatbotapi')->getDefaultValues();
        if (!is_array($formValues)) {
            $formValues = array();
        }
        if (Mage::getSingleton('adminhtml/session')->getChatbotapiData()) {
            $formValues = array_merge($formValues, Mage::getSingleton('adminhtml/session')->getChatbotapiData());
            Mage::getSingleton('adminhtml/session')->setChatbotapiData(null);
        } elseif (Mage::registry('current_chatbotapi')) {
            $formValues = array_merge($formValues, Mage::registry('current_chatbotapi')->getData());
        }
        $form->setValues($formValues);
        return parent::_prepareForm();
    }
}
