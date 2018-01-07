<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
 *
 * This file is part of Werules/Chatbot.
 *
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * MIT License for more details.
 *
 * You should have received a copy of the MIT License
 * along with this program. If not, see <https://opensource.org/licenses/MIT>.
 */

namespace Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field;

class NaturalLanguageProcessorReplies extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;
    protected $_itemRendererCommands;
    protected $_itemRendererReplyMode;
    protected $_itemRendererYesNo;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = array()
    )
    {
        $this->_elementFactory  = $elementFactory;
        parent::__construct($context,$data);
    }
    protected function _construct()
    {
        $this->addColumn('enable_reply', array(
            'label' => __("Enable"),
            'renderer' => $this->_getRendererYesNo()
        ));
        $this->addColumn('command_id', array(
            'label' => __("Mapped Command"),
            'renderer' => $this->_getRendererCommands()
        ));
//        $this->addColumn('nlp_entity', array(
//            'label' => __("wit.ai entity"),
//            //'style' => 'width: 100%',
//            'class' => 'validate-no-html-tags'
//        ));
        $this->addColumn('confidence', array(
            'label' => __("Acceptable Confidence (%)"),
            //'style' => 'width: 100%',
            'class' => 'input-number validate-number validate-number-range number-range-1-100'
        ));
//        $this->addColumn('stop_processing', array(
//            'label' => __("Stop Processing"),
//            'renderer' => $this->_getRendererYesNo()
//        ));
//        $this->addColumn('reply_mode', array(
//            'label' => __("Reply Mode"),
//            'renderer' => $this->_getRendererReplyMode()
//        ));
        $this->addColumn('reply_text', array(
            'label' => __("Reply Text"),
            //'style' => 'width: 100%',
            'class' => 'validate-no-html-tags'
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = __("Add");
        parent::_construct();
    }

    protected function _getRendererYesNo()
    {
        if (!$this->_itemRendererYesNo)
        {
            $this->_itemRendererYesNo = $this->getLayout()->createBlock(
                'Werules\Chatbot\Block\Adminhtml\System\Config\Options\YesNo',
                '',
                array('data' => array('is_render_to_js_template' => true))
//                array('is_render_to_js_template' => true)
            ); // ->setExtraParams('style="width: 100%;"');
        }
        return $this->_itemRendererYesNo;
    }

//    protected function _getRendererReplyMode()
//    {
//        if (!$this->_itemRendererReplyMode)
//        {
//            $this->_itemRendererReplyMode = $this->getLayout()->createBlock(
//                'Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\ReplyModeSelect',
//                '',
////                array('data' => array('is_render_to_js_template' => true))
//                  array('is_render_to_js_template' => true)
//            ); // ->setExtraParams('style="width: 100%;"');
//        }
//        return $this->_itemRendererReplyMode;
//    }

    protected function _getRendererCommands()
    {
        if (!$this->_itemRendererCommands)
        {
            $this->_itemRendererCommands = $this->getLayout()->createBlock(
                'Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\CommandsSelect',
                '',
                array('data' => array('is_render_to_js_template' => true))
//                array('is_render_to_js_template' => true)
            ); // ->setExtraParams('style="width: 100%;"');
//            $this->_itemRendererCommands->setClass('custom_class');
        }
        return $this->_itemRendererCommands;
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = array();
        $optionExtraAttr['option_' . $this->_getRendererCommands()->calcOptionHash($row->getData('command_id'))] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getRendererYesNo()->calcOptionHash($row->getData('enable_reply'))] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs', $optionExtraAttr
        );
//        $optionExtraAttr = array();
//        $optionExtraAttr['option_' . $this->_getRendererReplyMode()->calcOptionHash($row->getData('reply_mode'))] = 'selected="selected"';
//        $row->setData(
//            'option_extra_attrs', $optionExtraAttr
//        );
    }
}