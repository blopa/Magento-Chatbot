<?php
/**
 * Werules_Chatbot extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       Werules
 * @package        Werules_Chatbot
 * @copyright      Copyright (c) 2017
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * ChatbotUser edit form tab
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Block_Adminhtml_Chatbotuser_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare the form
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Edit_Tab_Form
     * @author Ultimate Module Creator
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
