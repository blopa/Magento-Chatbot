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

class WelcomeOptions extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;
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
        $this->addColumn('enable_option', array(
            'label' => __("Enable"),
            'renderer' => $this->_getRendererEnable()
        ));
        $this->addColumn('option_text', array(
            'label' => __("Option Text"),
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

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = array();
        $optionExtraAttr['option_' . $this->_getRendererEnable()->calcOptionHash($row->getData('enable_option'))] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs', $optionExtraAttr
        );
    }
}