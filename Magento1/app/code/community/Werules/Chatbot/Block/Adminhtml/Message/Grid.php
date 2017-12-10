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
 * Message admin grid block
 *
 * @category    Werules
 * @package     Werules_Chatbot
 * @author      Ultimate Module Creator
 */
class Werules_Chatbot_Block_Adminhtml_Message_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $this->setId('messageGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Message_Grid
     * @author Ultimate Module Creator
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('werules_chatbot/message')
            ->getCollection();
        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare grid collection
     *
     * @access protected
     * @return Werules_Chatbot_Block_Adminhtml_Message_Grid
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
            'message_id',
            array(
                'header'    => Mage::helper('werules_chatbot')->__('Message ID'),
                'align'     => 'left',
                'index'     => 'message_id',
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
            'sender_id',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Sender ID'),
                'index'  => 'sender_id',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'content',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Content'),
                'index'  => 'content',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Status'),
                'index'  => 'status',
                'type'=> 'number',

            )
        );
        $this->addColumn(
            'direction',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Direction'),
                'index'  => 'direction',
                'type'=> 'number',

            )
        );
        $this->addColumn(
            'chat_message_id',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Chat Message ID'),
                'index'  => 'chat_message_id',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'chatbot_type',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Chatbot Type'),
                'index'  => 'chatbot_type',
                'type'=> 'number',

            )
        );
        $this->addColumn(
            'content_type',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Content Type'),
                'index'  => 'content_type',
                'type'=> 'number',

            )
        );
        $this->addColumn(
            'message_payload',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Message Payload'),
                'index'  => 'message_payload',
                'type'=> 'text',

            )
        );
        $this->addColumn(
            'sent_at',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Sent At'),
                'index'  => 'sent_at',
                'type'=> 'date',

            )
        );
        $this->addColumn(
            'current_command_details',
            array(
                'header' => Mage::helper('werules_chatbot')->__('Current Command Details'),
                'index'  => 'current_command_details',
                'type'=> 'text',

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
     * @return Werules_Chatbot_Block_Adminhtml_Message_Grid
     * @author Ultimate Module Creator
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('message');
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
        return $this;
    }

    /**
     * get the row url
     *
     * @access public
     * @param Werules_Chatbot_Model_Message
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
     * @return Werules_Chatbot_Block_Adminhtml_Message_Grid
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
     * @param Werules_Chatbot_Model_Resource_Message_Collection $collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return Werules_Chatbot_Block_Adminhtml_Message_Grid
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
