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

class Werules_Chatbot_Adminhtml_Chatbot_ChatbotapiController extends Werules_Chatbot_Controller_Adminhtml_Chatbot
{
    /**
     * init the chatbotapi
     *
     * @access protected
     * @return Werules_Chatbot_Model_Chatbotapi
     */
    protected function _initChatbotapi()
    {
        $chatbotapiId  = (int) $this->getRequest()->getParam('id');
        $chatbotapi    = Mage::getModel('werules_chatbot/chatbotapi');
        if ($chatbotapiId) {
            $chatbotapi->load($chatbotapiId);
        }
        Mage::register('current_chatbotapi', $chatbotapi);
        return $chatbotapi;
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
             ->_title(Mage::helper('werules_chatbot')->__('ChatbotAPIs'));
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
     * edit chatbotapi - action
     *
     * @access public
     * @return void

     */
    public function editAction()
    {
        $chatbotapiId    = $this->getRequest()->getParam('id');
        $chatbotapi      = $this->_initChatbotapi();
        if ($chatbotapiId && !$chatbotapi->getId()) {
            $this->_getSession()->addError(
                Mage::helper('werules_chatbot')->__('This chatbotapi no longer exists.')
            );
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getChatbotapiData(true);
        if (!empty($data)) {
            $chatbotapi->setData($data);
        }
        Mage::register('chatbotapi_data', $chatbotapi);
        $this->loadLayout();
        $this->_title(Mage::helper('werules_chatbot')->__('Chatbot Settings'))
             ->_title(Mage::helper('werules_chatbot')->__('ChatbotAPIs'));
        if ($chatbotapi->getId()) {
            $this->_title($chatbotapi->getChatbotapiId());
        } else {
            $this->_title(Mage::helper('werules_chatbot')->__('Add chatbotapi'));
        }
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->renderLayout();
    }

    /**
     * new chatbotapi action
     *
     * @access public
     * @return void

     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * save chatbotapi - action
     *
     * @access public
     * @return void

     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost('chatbotapi')) {
            try {
                $chatbotapi = $this->_initChatbotapi();
                $chatbotapi->addData($data);
                $chatbotapi->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('ChatbotAPI was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $chatbotapi->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setChatbotapiData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was a problem saving the chatbotapi.')
                );
                Mage::getSingleton('adminhtml/session')->setChatbotapiData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('werules_chatbot')->__('Unable to find chatbotapi to save.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * delete chatbotapi - action
     *
     * @access public
     * @return void

     */
    public function deleteAction()
    {
        if ( $this->getRequest()->getParam('id') > 0) {
            try {
                $chatbotapi = Mage::getModel('werules_chatbot/chatbotapi');
                $chatbotapi->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('ChatbotAPI was successfully deleted.')
                );
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error deleting chatbotapi.')
                );
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('werules_chatbot')->__('Could not find chatbotapi to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * mass delete chatbotapi - action
     *
     * @access public
     * @return void

     */
    public function massDeleteAction()
    {
        $chatbotapiIds = $this->getRequest()->getParam('chatbotapi');
        if (!is_array($chatbotapiIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotapis to delete.')
            );
        } else {
            try {
                foreach ($chatbotapiIds as $chatbotapiId) {
                    $chatbotapi = Mage::getModel('werules_chatbot/chatbotapi');
                    $chatbotapi->setId($chatbotapiId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('Total of %d chatbotapis were successfully deleted.', count($chatbotapiIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error deleting chatbotapis.')
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
        $chatbotapiIds = $this->getRequest()->getParam('chatbotapi');
        if (!is_array($chatbotapiIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotapis.')
            );
        } else {
            try {
                foreach ($chatbotapiIds as $chatbotapiId) {
                $chatbotapi = Mage::getSingleton('werules_chatbot/chatbotapi')->load($chatbotapiId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotapis were successfully updated.', count($chatbotapiIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotapis.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass Logged? change - action
     *
     * @access public
     * @return void

     */
    public function massLoggedAction()
    {
        $chatbotapiIds = $this->getRequest()->getParam('chatbotapi');
        if (!is_array($chatbotapiIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotapis.')
            );
        } else {
            try {
                foreach ($chatbotapiIds as $chatbotapiId) {
                $chatbotapi = Mage::getSingleton('werules_chatbot/chatbotapi')->load($chatbotapiId)
                    ->setLogged($this->getRequest()->getParam('flag_logged'))
                    ->setIsMassupdate(true)
                    ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotapis were successfully updated.', count($chatbotapiIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotapis.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass Enabled? change - action
     *
     * @access public
     * @return void

     */
    public function massEnabledAction()
    {
        $chatbotapiIds = $this->getRequest()->getParam('chatbotapi');
        if (!is_array($chatbotapiIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotapis.')
            );
        } else {
            try {
                foreach ($chatbotapiIds as $chatbotapiId) {
                $chatbotapi = Mage::getSingleton('werules_chatbot/chatbotapi')->load($chatbotapiId)
                    ->setEnabled($this->getRequest()->getParam('flag_enabled'))
                    ->setIsMassupdate(true)
                    ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotapis were successfully updated.', count($chatbotapiIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotapis.')
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
        $fileName   = 'chatbotapi.csv';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_chatbotapi_grid')
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
        $fileName   = 'chatbotapi.xls';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_chatbotapi_grid')
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
        $fileName   = 'chatbotapi.xml';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_chatbotapi_grid')
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
        return Mage::getSingleton('admin/session')->isAllowed('system/config/werules_chatbot/chatbotapi');
    }
}
