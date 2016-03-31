<?php
/**
 * Populate DB schema with module's initial data
 * .
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Pv\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Praxigento\Accounting\Lib\Entity\Type\Asset as TypeAsset;
use Praxigento\Accounting\Lib\Entity\Type\Operation as TypeOperation;
use Praxigento\Pv\Config as Cfg;

class InstallData extends \Praxigento\Core\Setup\Data\Base
{
    private function _addAccountingAssetsTypes()
    {
        $this->_getConn()->insertArray(
            $this->_getTableName(TypeAsset::ENTITY_NAME),
            [TypeAsset::ATTR_CODE, TypeAsset::ATTR_NOTE],
            [
                [
                    Cfg::CODE_TYPE_ASSET_PV,
                    'PV (Points of volume or volume points).'
                ]
            ]
        );
    }

    private function _addAccountingOperationsTypes()
    {
        $this->_getConn()->insertArray(
            $this->_getTableName(TypeOperation::ENTITY_NAME),
            [TypeOperation::ATTR_CODE, TypeOperation::ATTR_NOTE],
            [
                [Cfg::CODE_TYPE_OPER_PV_SALE_PAID, 'PV assets related to order processing.'],
                [Cfg::CODE_TYPE_OPER_PV_TRANSFER, 'PV transfer between customers accounts.']
            ]
        );
    }

    protected function _setup(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->_addAccountingAssetsTypes();
        $this->_addAccountingOperationsTypes();
    }
}