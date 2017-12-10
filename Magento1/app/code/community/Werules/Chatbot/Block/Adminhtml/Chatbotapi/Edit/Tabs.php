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
 * ChatbotAPI admin edit tabs
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Block_Adminhtml_Chatbotapi_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
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
        $this->setId('chatbotapi_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('werules_chatbot')->__('ChatbotAPI'));
    }

    /**
     * before render html
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotapi_Edit_Tabs
     * @author Ultimate Module Creator
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'form_chatbotapi',
            array(
                'label'   => Mage::helper('werules_chatbot')->__('ChatbotAPI'),
                'title'   => Mage::helper('werules_chatbot')->__('ChatbotAPI'),
                'content' => $this->getLayout()->createBlock(
                    'werules_chatbot/adminhtml_chatbotapi_edit_tab_form'
                )
                ->toHtml(),
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addTab(
                'form_store_chatbotapi',
                array(
                    'label'   => Mage::helper('werules_chatbot')->__('Store views'),
                    'title'   => Mage::helper('werules_chatbot')->__('Store views'),
                    'content' => $this->getLayout()->createBlock(
                        'werules_chatbot/adminhtml_chatbotapi_edit_tab_stores'
                    )
                    ->toHtml(),
                )
            );
        }
        return parent::_beforeToHtml();
    }

    /**
     * Retrieve chatbotapi entity
     *
     * @access public
     * @return Werules_Chatbot_Model_Chatbotapi
     * @author Ultimate Module Creator
     */
    public function getChatbotapi()
    {
        return Mage::registry('current_chatbotapi');
    }
}
