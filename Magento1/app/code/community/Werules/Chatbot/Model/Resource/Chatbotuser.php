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

class Werules_Chatbot_Model_Resource_Chatbotuser extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor
     *
     * @access public

     */
    public function _construct()
    {
        $this->_init('werules_chatbot/chatbotuser', 'entity_id');
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @access public
     * @param int $chatbotuserId
     * @return array

     */
    public function lookupStoreIds($chatbotuserId)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('werules_chatbot/chatbotuser_store'), 'store_id')
            ->where('chatbotuser_id = ?', (int)$chatbotuserId);
        return $adapter->fetchCol($select);
    }

    /**
     * Perform operations after object load
     *
     * @access public
     * @param Mage_Core_Model_Abstract $object
     * @return Werules_Chatbot_Model_Resource_Chatbotuser

     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $stores = $this->lookupStoreIds($object->getId());
            $object->setData('store_id', $stores);
        }
        return parent::_afterLoad($object);
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param Werules_Chatbot_Model_Chatbotuser $object
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        if ($object->getStoreId()) {
            $storeIds = array(Mage_Core_Model_App::ADMIN_STORE_ID, (int)$object->getStoreId());
            $select->join(
                array('chatbot_chatbotuser_store' => $this->getTable('werules_chatbot/chatbotuser_store')),
                $this->getMainTable() . '.entity_id = chatbot_chatbotuser_store.chatbotuser_id',
                array()
            )
            ->where('chatbot_chatbotuser_store.store_id IN (?)', $storeIds)
            ->order('chatbot_chatbotuser_store.store_id DESC')
            ->limit(1);
        }
        return $select;
    }

    /**
     * Assign chatbotuser to store views
     *
     * @access protected
     * @param Mage_Core_Model_Abstract $object
     * @return Werules_Chatbot_Model_Resource_Chatbotuser

     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $oldStores = $this->lookupStoreIds($object->getId());
        $newStores = (array)$object->getStores();
        if (empty($newStores)) {
            $newStores = (array)$object->getStoreId();
        }
        $table  = $this->getTable('werules_chatbot/chatbotuser_store');
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);
        if ($delete) {
            $where = array(
                'chatbotuser_id = ?' => (int) $object->getId(),
                'store_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }
        if ($insert) {
            $data = array();
            foreach ($insert as $storeId) {
                $data[] = array(
                    'chatbotuser_id'  => (int) $object->getId(),
                    'store_id' => (int) $storeId
                );
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return parent::_afterSave($object);
    }
}
