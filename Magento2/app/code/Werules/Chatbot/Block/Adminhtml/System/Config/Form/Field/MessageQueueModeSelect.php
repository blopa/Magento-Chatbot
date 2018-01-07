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

class MessageQueueModeSelect extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\MessageQueueModeList
     */
    protected $_messageQueueModeList;

    public function __construct(
        \Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\MessageQueueModeList $messageQueueModeList,
        \Magento\Backend\Block\Template\Context $context, array $data = array())
    {
        parent::__construct($context, $data);
        $this->_messageQueueModeList = $messageQueueModeList;
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_messageQueueModeList->toOptionArray() as $option) {
                $this->addOption($option['value'], $option['label']);
            }
        }
        return parent::_toHtml();
    }

    public function getName()
    {
        return $this->getInputName();
    }
}