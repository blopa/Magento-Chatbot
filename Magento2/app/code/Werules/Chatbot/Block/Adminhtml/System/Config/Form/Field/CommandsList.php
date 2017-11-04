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

class CommandsList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Provide available options as a value/label array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
//			array('value' => 0, 'label' => 'Start'),
            array('value' => 1, 'label' => 'List Categories'),
            array('value' => 2, 'label' => 'Search For Product'),
            array('value' => 3, 'label' => 'Login'),
            array('value' => 4, 'label' => 'List Orders'),
            array('value' => 5, 'label' => 'Reorder'),
            array('value' => 6, 'label' => 'Add Product to Cart'),
            array('value' => 7, 'label' => 'Checkout on Website'),
            array('value' => 8, 'label' => 'Clear Cart'),
            array('value' => 9, 'label' => 'Track Order Status'),
            array('value' => 10, 'label' => 'Talk to Support'),
            array('value' => 11, 'label' => 'Send Email'),
            array('value' => 12, 'label' => 'Cancel'),
            array('value' => 13, 'label' => 'Help'),
            array('value' => 14, 'label' => 'About'),
            array('value' => 15, 'label' => 'Logout'),
            array('value' => 16, 'label' => 'Register')
        );
    }
}