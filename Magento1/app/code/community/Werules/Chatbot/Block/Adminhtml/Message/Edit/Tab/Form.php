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

class Werules_Chatbot_Block_Adminhtml_Message_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare the form
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Message_Edit_Tab_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('message_');
        $form->setFieldNameSuffix('message');
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'message_form',
            array('legend' => Mage::helper('werules_chatbot')->__('Message'))
        );

        $fieldset->addField(
            'sender_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Sender ID'),
                'name'  => 'sender_id',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'content',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Content'),
                'name'  => 'content',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'status',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Status'),
                'name'  => 'status',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'direction',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Direction'),
                'name'  => 'direction',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'chat_message_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Chat Message ID'),
                'name'  => 'chat_message_id',
                'required'  => true,
                'class' => 'required-entry',

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
            'content_type',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Content Type'),
                'name'  => 'content_type',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'message_payload',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Message Payload'),
                'name'  => 'message_payload',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'sent_at',
            'date',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Sent At'),
                'name'  => 'sent_at',
                'required'  => true,
                'class' => 'required-entry',

            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format'  => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
           )
        );

        $fieldset->addField(
            'current_command_details',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Current Command Details'),
                'name'  => 'current_command_details',
                'required'  => true,
                'class' => 'required-entry',

           )
        );

        $fieldset->addField(
            'message_id',
            'text',
            array(
                'label' => Mage::helper('werules_chatbot')->__('Message ID'),
                'name'  => 'message_id',
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
            Mage::registry('current_message')->setStoreId(Mage::app()->getStore(true)->getId());
        }
        $formValues = Mage::registry('current_message')->getDefaultValues();
        if (!is_array($formValues)) {
            $formValues = array();
        }
        if (Mage::getSingleton('adminhtml/session')->getMessageData()) {
            $formValues = array_merge($formValues, Mage::getSingleton('adminhtml/session')->getMessageData());
            Mage::getSingleton('adminhtml/session')->setMessageData(null);
        } elseif (Mage::registry('current_message')) {
            $formValues = array_merge($formValues, Mage::registry('current_message')->getData());
        }
        $form->setValues($formValues);
        return parent::_prepareForm();
    }
}
