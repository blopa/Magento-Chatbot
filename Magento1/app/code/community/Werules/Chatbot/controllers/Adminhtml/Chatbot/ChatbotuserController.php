<?php
/**
 * Werules_Chatbot extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       Werules
 * @package        Werules_Chatbot
 * @copyright      Copyright (c) 2017
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * ChatbotUser admin controller
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Adminhtml_Chatbot_ChatbotuserController extends Werules_Chatbot_Controller_Adminhtml_Chatbot
{
    /**
     * init the chatbotuser
     *
     * @access protected
     * @return Werules_Chatbot_Model_Chatbotuser
     */
    protected function _initChatbotuser()
    {
        $chatbotuserId  = (int) $this->getRequest()->getParam('id');
        $chatbotuser    = Mage::getModel('werules_chatbot/chatbotuser');
        if ($chatbotuserId) {
            $chatbotuser->load($chatbotuserId);
        }
        Mage::register('current_chatbotuser', $chatbotuser);
        return $chatbotuser;
    }

    /**
     * default action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title(Mage::helper('werules_chatbot')->__('Chatbot Settings'))
             ->_title(Mage::helper('werules_chatbot')->__('ChatbotUsers'));
        $this->renderLayout();
    }

    /**
     * grid action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
    }

    /**
     * edit chatbotuser - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function editAction()
    {
        $chatbotuserId    = $this->getRequest()->getParam('id');
        $chatbotuser      = $this->_initChatbotuser();
        if ($chatbotuserId && !$chatbotuser->getId()) {
            $this->_getSession()->addError(
                Mage::helper('werules_chatbot')->__('This chatbotuser no longer exists.')
            );
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getChatbotuserData(true);
        if (!empty($data)) {
            $chatbotuser->setData($data);
        }
        Mage::register('chatbotuser_data', $chatbotuser);
        $this->loadLayout();
        $this->_title(Mage::helper('werules_chatbot')->__('Chatbot Settings'))
             ->_title(Mage::helper('werules_chatbot')->__('ChatbotUsers'));
        if ($chatbotuser->getId()) {
            $this->_title($chatbotuser->getChatbotuserId());
        } else {
            $this->_title(Mage::helper('werules_chatbot')->__('Add chatbotuser'));
        }
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->renderLayout();
    }

    /**
     * new chatbotuser action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * save chatbotuser - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost('chatbotuser')) {
            try {
                $chatbotuser = $this->_initChatbotuser();
                $chatbotuser->addData($data);
                $chatbotuser->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('ChatbotUser was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $chatbotuser->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setChatbotuserData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was a problem saving the chatbotuser.')
                );
                Mage::getSingleton('adminhtml/session')->setChatbotuserData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('werules_chatbot')->__('Unable to find chatbotuser to save.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * delete chatbotuser - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function deleteAction()
    {
        if ( $this->getRequest()->getParam('id') > 0) {
            try {
                $chatbotuser = Mage::getModel('werules_chatbot/chatbotuser');
                $chatbotuser->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('ChatbotUser was successfully deleted.')
                );
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error deleting chatbotuser.')
                );
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('werules_chatbot')->__('Could not find chatbotuser to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * mass delete chatbotuser - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function massDeleteAction()
    {
        $chatbotuserIds = $this->getRequest()->getParam('chatbotuser');
        if (!is_array($chatbotuserIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotusers to delete.')
            );
        } else {
            try {
                foreach ($chatbotuserIds as $chatbotuserId) {
                    $chatbotuser = Mage::getModel('werules_chatbot/chatbotuser');
                    $chatbotuser->setId($chatbotuserId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('werules_chatbot')->__('Total of %d chatbotusers were successfully deleted.', count($chatbotuserIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error deleting chatbotusers.')
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
     * @author Ultimate Module Creator
     */
    public function massStatusAction()
    {
        $chatbotuserIds = $this->getRequest()->getParam('chatbotuser');
        if (!is_array($chatbotuserIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotusers.')
            );
        } else {
            try {
                foreach ($chatbotuserIds as $chatbotuserId) {
                $chatbotuser = Mage::getSingleton('werules_chatbot/chatbotuser')->load($chatbotuserId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotusers were successfully updated.', count($chatbotuserIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotusers.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass Enable Promotional Messages change - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function massEnablePromotionalMessagesAction()
    {
        $chatbotuserIds = $this->getRequest()->getParam('chatbotuser');
        if (!is_array($chatbotuserIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotusers.')
            );
        } else {
            try {
                foreach ($chatbotuserIds as $chatbotuserId) {
                $chatbotuser = Mage::getSingleton('werules_chatbot/chatbotuser')->load($chatbotuserId)
                    ->setEnablePromotionalMessages($this->getRequest()->getParam('flag_enable_promotional_messages'))
                    ->setIsMassupdate(true)
                    ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotusers were successfully updated.', count($chatbotuserIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotusers.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass Enable Support change - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function massEnableSupportAction()
    {
        $chatbotuserIds = $this->getRequest()->getParam('chatbotuser');
        if (!is_array($chatbotuserIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotusers.')
            );
        } else {
            try {
                foreach ($chatbotuserIds as $chatbotuserId) {
                $chatbotuser = Mage::getSingleton('werules_chatbot/chatbotuser')->load($chatbotuserId)
                    ->setEnableSupport($this->getRequest()->getParam('flag_enable_support'))
                    ->setIsMassupdate(true)
                    ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotusers were successfully updated.', count($chatbotuserIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotusers.')
                );
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass Is Admin? change - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function massAdminAction()
    {
        $chatbotuserIds = $this->getRequest()->getParam('chatbotuser');
        if (!is_array($chatbotuserIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('werules_chatbot')->__('Please select chatbotusers.')
            );
        } else {
            try {
                foreach ($chatbotuserIds as $chatbotuserId) {
                $chatbotuser = Mage::getSingleton('werules_chatbot/chatbotuser')->load($chatbotuserId)
                    ->setAdmin($this->getRequest()->getParam('flag_admin'))
                    ->setIsMassupdate(true)
                    ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d chatbotusers were successfully updated.', count($chatbotuserIds))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('werules_chatbot')->__('There was an error updating chatbotusers.')
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
     * @author Ultimate Module Creator
     */
    public function exportCsvAction()
    {
        $fileName   = 'chatbotuser.csv';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_chatbotuser_grid')
            ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export as MsExcel - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function exportExcelAction()
    {
        $fileName   = 'chatbotuser.xls';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_chatbotuser_grid')
            ->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export as xml - action
     *
     * @access public
     * @return void
     * @author Ultimate Module Creator
     */
    public function exportXmlAction()
    {
        $fileName   = 'chatbotuser.xml';
        $content    = $this->getLayout()->createBlock('werules_chatbot/adminhtml_chatbotuser_grid')
            ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Check if admin has permissions to visit related pages
     *
     * @access protected
     * @return boolean
     * @author Ultimate Module Creator
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/werules_chatbot/chatbotuser');
    }
}
