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
 * Message admin edit form
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Block_Adminhtml_Message_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * constructor
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
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
     * @author Ultimate Module Creator
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
