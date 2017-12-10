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

class Werules_Chatbot_Adminhtml_Chatbot_MessageController extends Werules_Chatbot_Controller_Adminhtml_Chatbot
{
    /**
     * init the message
     *
     * @access protected
     * @return Werules_Chatbot_Model_Message
     */
    protected function _initMessage()
    {
        $messageId  = (int) $this->getRequest()->getParam('id');
        $message    = Mage::getModel('werules_chatbot/message');
        if ($messageId) {
            $message->load($messageId);
        }
        Mage::register('current_message', $message);
        return $message;
    }

    /**
     * default action
     *
     * @access public
     * @return void
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title(Mage::helper('werules_chatbot')->__('Chatbot Settings'))
             ->_title(Mage::helper('werules_chatbot')->__('Messages'));
        $this->renderLayout();
    }

    /**
     * grid action
     *
     * @access public
     * @return void

     */
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
    }

    /**
     * edit message - action
     *
     * @access public
     * @return void

     */
    public function editAction()
    {
        $messageId    = $this->getRequest()->getParam('id');
        $message      = $this->_initMessage();
        if ($messageId && !$message->getId()) {
            $this->_getSession()->addError(
                Mage::helper('werules_chatbot')->__('This message no longer exists.')
            );
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getMessageData(true);
        if (!empty($data)) {
            $message->setData($data);
        }
        Mage::register('message_data', $message);
        $this->loadLayout();
        $this->_title(Mage::helper('werules_chatbot')->__('Chatbot Settings'))
             ->_title(Mage::helper('werules_chatbot')->__('Messages'));
        if ($message->getId()) {
            $this->_title($message->getMessageId());
        } else {
            $this->_title(Mage::helper('werules_chatbot')->__('Add message'));
        }
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->renderLayout();
    }

    /**
     * new message action
     *
     * @access public
     * @return void

     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * save message - action
     *
     * @access public
     * @return void

     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost('message')) {
            try {
                $data = $this->_filterDates($data, array('sent_at'));
                $message = $this->_initMessage();
                $message->addData($data);
                $message->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('Message was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $message->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setMessageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was a problem saving the message.')
                );
                Mage::getSingleton('adminhtml/session')->setMessageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('werules_chatbot')->__('Unable to find message to save.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * delete message - action
     *
     * @access public
     * @return void

     */
    public function deleteAction()
    {
        if ( $this->getRequest()->getParam('id') > 0) {
            try {
                $message = Mage::getModel('werules_chatbot/message');
                $message->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('Message was successfully deleted.')
                );
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error deleting message.')
                );
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('werules_chatbot')->__('Could not find message to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * mass delete message - action
     *
     * @access public
     * @return void

     */
    public function massDeleteAction()
    {
        $messageIds = $this->getRequest()->getParam('message');
        if (!is_array($messageIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select messages to delete.')
            );
        } else {
            try {
                foreach ($messageIds as $messageId) {
                    $message = Mage::getModel('werules_chatbot/message');
                    $message->setId($messageId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('Total of %d messages were successfully deleted.', count($messageIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error deleting messages.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass status change - action
     *
     * @access public
     * @return void

     */
    public function massStatusAction()
    {
        $messageIds = $this->getRequest()->getParam('message');
        if (!is_array($messageIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select messages.')
            );
        } else {
            try {
                foreach ($messageIds as $messageId) {
                $message = Mage::getSingleton('werules_chatbot/message')->load($messageId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d messages were successfully updated.', count($messageIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating messages.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * export as csv - action
     *
     * @access public
     * @return void

     */
    public function exportCsvAction()
    {
        $fileName   = 'message.csv';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_message_grid')
            ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export as MsExcel - action
     *
     * @access public
     * @return void

     */
    public function exportExcelAction()
    {
        $fileName   = 'message.xls';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_message_grid')
            ->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export as xml - action
     *
     * @access public
     * @return void

     */
    public function exportXmlAction()
    {
        $fileName   = 'message.xml';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_message_grid')
            ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Check if admin has permissions to visit related pages
     *
     * @access protected
     * @return boolean

     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/werules_chatbot/message');
    }
}
