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
 * Message admin edit tabs
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Block_Adminhtml_Message_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Initialize Tabs
     *
     * @access public
     * @author Ultimate Module Creator
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('message_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('werules_chatbot')->__('Message'));
    }

    /**
     * before render html
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Message_Edit_Tabs
     * @author Ultimate Module Creator
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'form_message',
            array(
                'label'   => Mage::helper('werules_chatbot')->__('Message'),
                'title'   => Mage::helper('werules_chatbot')->__('Message'),
                'content' => $this->getLayout()->createBlock(
                    'werules_chatbot/adminhtml_message_edit_tab_form'
                )
                ->toHtml(),
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addTab(
                'form_store_message',
                array(
                    'label'   => Mage::helper('werules_chatbot')->__('Store views'),
                    'title'   => Mage::helper('werules_chatbot')->__('Store views'),
                    'content' => $this->getLayout()->createBlock(
                        'werules_chatbot/adminhtml_message_edit_tab_stores'
                    )
                    ->toHtml(),
                )
            );
        }
        return parent::_beforeToHtml();
    }

    /**
     * Retrieve message entity
     *
     * @access public
     * @return Werules_Chatbot_Model_Message
     * @author Ultimate Module Creator
     */
    public function getMessage()
    {
        return Mage::registry('current_message');
    }
}
