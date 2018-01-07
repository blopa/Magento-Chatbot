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

class CommandsList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $commands = $this->toArray();
        $result = array();
        foreach ($commands as $key => $command)
        {
            $arr = array(
                'value' => $key,
                'label' => $command
            );
            array_push($result, $arr);
        }

        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
//            0 => __("Start"),
            1 => __("List Categories"),
            2 => __("Search For Product"),
            3 => __("Login"),
            4 => __("List Orders"),
            5 => __("Reorder"),
            6 => __("Add Product to Cart"),
            7 => __("Checkout on Website"),
            8 => __("Clear Cart"),
            9 => __("Track Order Status"),
            10 => __("Talk to Support"),
            11 => __("Send Email"),
            12 => __("Cancel"),
            13 => __("Help"),
            14 => __("About"),
            15 => __("Logout"),
            16 => __("Register"),
            17 => __("List More")
        );
    }
}