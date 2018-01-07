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

class MatchModeList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Provide available options as a value/label array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => __("Equals to")),
            array('value' => 1, 'label' => __("Starts With")),
            array('value' => 2, 'label' => __("Ends With")),
            array('value' => 3, 'label' => __("Contains")),
            array('value' => 4, 'label' => __("Match RegEx"))
        );
    }
}