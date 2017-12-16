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

namespace Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field;

class DefaultReplies extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;
    protected $_itemRendererMatchCase;
    protected $_itemRendererMatchMode;
    protected $_itemRendererEnable;

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
            'renderer' => $this->_getRendererEnable()
        ));
//        $this->addColumn('stop_processing', array(
//            'label' => __("Stop Processing"),
//            'renderer' => $this->_getRendererEnable()
//        ));
        $this->addColumn('match_mode', array(
            'label' => __("Match Mode"),
            'renderer' => $this->_getRendererMatchMode()
        ));
        $this->addColumn('match_sintax', array(
            'label' => __("Match Text or Regex"),
            //'style' => 'width: 100%',
            'class' => 'validate-no-html-tags'
        ));
        $this->addColumn('match_case', array(
            'label' => __("Match Case"),
            'renderer' => $this->_getRendererMatchCase()
        ));
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
        return $this->getLayout()->createBlock(
            'Werules\Chatbot\Block\Adminhtml\System\Config\Options\YesNo',
            '',
            array('data' => array('is_render_to_js_template' => true))
//                array('is_render_to_js_template' => true)
        ); // ->setExtraParams('style="width: 100%;"');
    }

    protected function _getRendererEnable()
    {
        if (!$this->_itemRendererEnable)
        {
            $this->_itemRendererEnable = $this->_getRendererYesNo();
        }
        return $this->_itemRendererEnable;
    }

    protected function _getRendererMatchCase()
    {
        if (!$this->_itemRendererMatchCase)
        {
            $this->_itemRendererMatchCase = $this->_getRendererYesNo();
        }
        return $this->_itemRendererMatchCase;
    }

    protected function _getRendererMatchMode()
    {
        if (!$this->_itemRendererMatchMode)
        {
            $this->_itemRendererMatchMode = $this->getLayout()->createBlock(
                'Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\MatchModeSelect',
                '',
                array('data' => array('is_render_to_js_template' => true))
//                  array('is_render_to_js_template' => true)
            ); // ->setExtraParams('style="width: 100%;"');
        }
        return $this->_itemRendererMatchMode;
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = array();
        $optionExtraAttr['option_' . $this->_getRendererEnable()->calcOptionHash($row->getData('enable_reply'))] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getRendererMatchMode()->calcOptionHash($row->getData('match_mode'))] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getRendererMatchCase()->calcOptionHash($row->getData('match_case'))] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs', $optionExtraAttr
        );
    }
}