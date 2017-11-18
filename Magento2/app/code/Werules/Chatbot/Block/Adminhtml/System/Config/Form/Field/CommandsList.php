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
//            array('value' => 0, 'label' => __("Start")),
            array('value' => 1, 'label' => __("List Categories")),
            array('value' => 2, 'label' => __("Search For Product")),
            array('value' => 3, 'label' => __("Login")),
            array('value' => 4, 'label' => __("List Orders")),
            array('value' => 5, 'label' => __("Reorder")),
            array('value' => 6, 'label' => __("Add Product to Cart")),
            array('value' => 7, 'label' => __("Checkout on Website")),
            array('value' => 8, 'label' => __("Clear Cart")),
            array('value' => 9, 'label' => __("Track Order Status")),
            array('value' => 10, 'label' => __("Talk to Support")),
            array('value' => 11, 'label' => __("Send Email")),
            array('value' => 12, 'label' => __("Cancel")),
            array('value' => 13, 'label' => __("Help")),
            array('value' => 14, 'label' => __("About")),
            array('value' => 15, 'label' => __("Logout")),
            array('value' => 16, 'label' => __("Register"))
        );
    }
}