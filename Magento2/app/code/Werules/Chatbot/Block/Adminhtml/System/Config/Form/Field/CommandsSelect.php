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

class CommandsSelect extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\CommandsList
     */
    protected $_commandsList;

    public function __construct(
        \Werules\Chatbot\Block\Adminhtml\System\Config\Form\Field\CommandsList $commandsList,
        \Magento\Backend\Block\Template\Context $context, array $data = array())
    {
        parent::__construct($context, $data);
        $this->_commandsList = $commandsList;
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_commandsList->toOptionArray() as $option) {
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