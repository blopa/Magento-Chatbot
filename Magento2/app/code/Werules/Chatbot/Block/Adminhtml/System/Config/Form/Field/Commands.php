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

namespace Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field;

class Commands extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;
    protected $_itemRendererCommands;
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
        $this->addColumn('command_id', array(
            'label' => __("Command"),
            'renderer' => $this->_getRendererCommands()
        ));
        $this->addColumn('enable_command', array(
            'label' => __("Enable Command"),
            'renderer' => $this->_getRendererYesNo()
        ));
        $this->addColumn('command_code', array(
            'label' => __("Command Code"),
            //'style' => 'width: 100%',
            'class' => 'validate-no-html-tags'
        ));
        $this->addColumn('command_alias_list', array(
            'label' => __("Command Alias (Separated by Comma)"),
            //'style' => 'width: 100%',
            'class' => 'validate-no-html-tags'
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = __("Add");
        parent::_construct();
    }

//    protected function _prepareToRender() {}

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
        }
        return $this->_itemRendererCommands;
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = array();
        $optionExtraAttr['option_' . $this->_getRendererCommands()->calcOptionHash($row->getData('command_id'))] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getRendererYesNo()->calcOptionHash($row->getData('enable_command'))] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs', $optionExtraAttr
        );
    }
}