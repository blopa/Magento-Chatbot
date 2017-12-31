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

class Werules_Chatbot_Model_Message extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY    = 'werules_chatbot_message';
    const CACHE_TAG = 'werules_chatbot_message';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'werules_chatbot_message';

    /**
     * Parameter name in event
     *
     * @var string
     */
    protected $_eventObject = 'message';

    /**
     * constructor
     *
     * @access public
     * @return void

     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('werules_chatbot/message');
    }

    /**
     * before save message
     *
     * @access protected
     * @return Werules_Chatbot_Model_Message

     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        }
        $this->setUpdatedAt($now);
        return $this;
    }

    /**
     * save message relation
     *
     * @access public
     * @return Werules_Chatbot_Model_Message

     */
    protected function _afterSave()
    {
        return parent::_afterSave();
    }

    /**
     * get default values
     *
     * @access public
     * @return array

     */
    public function getDefaultValues()
    {
        $values = array();
        $values['status'] = 1;
        return $values;
    }
    
}
