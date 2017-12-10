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
 * ChatbotUser admin grid block
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Block_Adminhtml_Chatbotuser_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * constructor
     *
     * @access public
     * @author Ultimate Module Creator
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('chatbotuserGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Grid
     * @author Ultimate Module Creator
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('werules_chatbot/chatbotuser')
            ->getCollection();
        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare grid collection
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Grid
     * @author Ultimate Module Creator
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Id'),
                'index'  => 'entity_id',
                'type'   => 'number'
            )
        );
        $this->addColumn(
            'chatbotuser_id',
            array(
                'header'    => Mage::helper('werules_chatbot')->__('ChatbotUser ID'),
                'align'     => 'left',
                'index'     => 'chatbotuser_id',
            )
        );
        
        $this->addColumn(
            'status',
            array(
                'header'  => Mage::helper('werules_chatbot')->__('Status'),
                'index'   => 'status',
                'type'    => 'options',
                'options' => array(
                    '1' => Mage::helper('werules_chatbot')->__('Enabled'),
                    '0' => Mage::helper('werules_chatbot')->__('Disabled'),
                )
            )
        );
        $this->addColumn(
            'session_id',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Session ID'),
                'index'  => 'session_id',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'enable_promotional_messages',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Enable Promotional Messages'),
                'index'  => 'enable_promotional_messages',
                'type'    => 'options',
                    'options'    => array(
                    '1' => Mage::helper('werules_chatbot')->__('Yes'),
                    '0' => Mage::helper('werules_chatbot')->__('No'),
                )

            )
        );
        $this->addColumn(
            'enable_support',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Enable Support'),
                'index'  => 'enable_support',
                'type'    => 'options',
                    'options'    => array(
                    '1' => Mage::helper('werules_chatbot')->__('Yes'),
                    '0' => Mage::helper('werules_chatbot')->__('No'),
                )

            )
        );
        $this->addColumn(
            'admin',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Is Admin?'),
                'index'  => 'admin',
                'type'    => 'options',
                    'options'    => array(
                    '1' => Mage::helper('werules_chatbot')->__('Yes'),
                    '0' => Mage::helper('werules_chatbot')->__('No'),
                )

            )
        );
        if (!Mage::app()->isSingleStoreMode() && !$this->_isExport) {
            $this->addColumn(
                'store_id',
                array(
                    'header'     => Mage::helper('werules_chatbot')->__('Store Views'),
                    'index'      => 'store_id',
                    'type'       => 'store',
                    'store_all'  => true,
                    'store_view' => true,
                    'sortable'   => false,
                    'filter_condition_callback'=> array($this, '_filterStoreCondition'),
                )
            );
        }
        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Created at'),
                'index'  => 'created_at',
                'width'  => '120px',
                'type'   => 'datetime',
            )
        );
        $this->addColumn(
            'updated_at',
            array(
                'header'    => Mage::helper('werules_chatbot')->__('Updated at'),
                'index'     => 'updated_at',
                'width'     => '120px',
                'type'      => 'datetime',
            )
        );
        $this->addColumn(
            'action',
            array(
                'header'  =>  Mage::helper('werules_chatbot')->__('Action'),
                'width'   => '100',
                'type'    => 'action',
                'getter'  => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('werules_chatbot')->__('Edit'),
                        'url'     => array('base'=> '*/*/edit'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'is_system' => true,
                'sortable'  => false,
            )
        );
        $this->addExportType('*/*/exportCsv', Mage::helper('werules_chatbot')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('werules_chatbot')->__('Excel'));
        $this->addExportType('*/*/exportXml', Mage::helper('werules_chatbot')->__('XML'));
        return parent::_prepareColumns();
    }

    /**
     * prepare mass action
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Grid
     * @author Ultimate Module Creator
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('chatbotuser');
        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label'=> Mage::helper('werules_chatbot')->__('Delete'),
                'url'  => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('werules_chatbot')->__('Are you sure?')
            )
        );
        $this->getMassactionBlock()->addItem(
            'status',
            array(
                'label'      => Mage::helper('werules_chatbot')->__('Change status'),
                'url'        => $this->getUrl('*/*/massStatus', array('_current'=>true)),
                'additional' => array(
                    'status' => array(
                        'name'   => 'status',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('werules_chatbot')->__('Status'),
                        'values' => array(
                            '1' => Mage::helper('werules_chatbot')->__('Enabled'),
                            '0' => Mage::helper('werules_chatbot')->__('Disabled'),
                        )
                    )
                )
            )
        );
        $this->getMassactionBlock()->addItem(
            'enable_promotional_messages',
            array(
                'label'      => Mage::helper('werules_chatbot')->__('Change Enable Promotional Messages'),
                'url'        => $this->getUrl('*/*/massEnablePromotionalMessages', array('_current'=>true)),
                'additional' => array(
                    'flag_enable_promotional_messages' => array(
                        'name'   => 'flag_enable_promotional_messages',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('werules_chatbot')->__('Enable Promotional Messages'),
                        'values' => array(
                                '1' => Mage::helper('werules_chatbot')->__('Yes'),
                                '0' => Mage::helper('werules_chatbot')->__('No'),
                            )

                    )
                )
            )
        );
        $this->getMassactionBlock()->addItem(
            'enable_support',
            array(
                'label'      => Mage::helper('werules_chatbot')->__('Change Enable Support'),
                'url'        => $this->getUrl('*/*/massEnableSupport', array('_current'=>true)),
                'additional' => array(
                    'flag_enable_support' => array(
                        'name'   => 'flag_enable_support',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('werules_chatbot')->__('Enable Support'),
                        'values' => array(
                                '1' => Mage::helper('werules_chatbot')->__('Yes'),
                                '0' => Mage::helper('werules_chatbot')->__('No'),
                            )

                    )
                )
            )
        );
        $this->getMassactionBlock()->addItem(
            'admin',
            array(
                'label'      => Mage::helper('werules_chatbot')->__('Change Is Admin?'),
                'url'        => $this->getUrl('*/*/massAdmin', array('_current'=>true)),
                'additional' => array(
                    'flag_admin' => array(
                        'name'   => 'flag_admin',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('werules_chatbot')->__('Is Admin?'),
                        'values' => array(
                                '1' => Mage::helper('werules_chatbot')->__('Yes'),
                                '0' => Mage::helper('werules_chatbot')->__('No'),
                            )

                    )
                )
            )
        );
        return $this;
    }

    /**
     * get the row url
     *
     * @access public
     * @param Werules_Chatbot_Model_Chatbotuser
     * @return string
     * @author Ultimate Module Creator
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * get the grid url
     *
     * @access public
     * @return string
     * @author Ultimate Module Creator
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    /**
     * after collection load
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Grid
     * @author Ultimate Module Creator
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    /**
     * filter store column
     *
     * @access protected
     * @param Werules_Chatbot_Model_Resource_Chatbotuser_Collection $collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return Werules_Chatbot_Block_Adminhtml_Chatbotuser_Grid
     * @author Ultimate Module Creator
     */
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->addStoreFilter($value);
        return $this;
    }
}
