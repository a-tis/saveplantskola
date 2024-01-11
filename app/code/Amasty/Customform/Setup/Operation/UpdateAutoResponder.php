<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Customform
 */


namespace Amasty\Customform\Setup\Operation;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Block\Adminhtml\Form\Edit\Tab\Main;
use Magento\Framework\DB\Ddl\Table;

class UpdateAutoResponder
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('am_customform_form');
        $setup->getConnection()->addColumn(
            $tableName,
            FormInterface::AUTO_REPLY_TEMPLATE,
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'size' => 50,
                'comment' => 'Auto Reply Email Template',
            ]
        );

        $setup->getConnection()->addColumn(
            $tableName,
            FormInterface::AUTO_REPLY_ENABLE,
            [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => Main::SYSTEM_CONFIG_VALUE,
                'comment' => 'Is Auto Responder Enabled',
            ]
        );
    }
}
