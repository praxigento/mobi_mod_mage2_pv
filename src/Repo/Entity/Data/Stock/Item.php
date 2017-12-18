<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Pv\Repo\Entity\Data\Stock;

class Item
    extends \Praxigento\Core\App\Repo\Data\Entity\Base
{
    const ATTR_PV = 'pv';
    const ATTR_ITEM_REF = 'item_ref';
    const ENTITY_NAME = 'prxgt_pv_stock_item';

    public static function getPrimaryKeyAttrs()
    {
        return [self::ATTR_ITEM_REF];
    }

    /** @return float */
    public function getPv()
    {
        $result = parent::get(self::ATTR_PV);
        return $result;
    }

    /** @return int */
    public function getItemRef()
    {
        $result = parent::get(self::ATTR_ITEM_REF);
        return $result;
    }

    /** @param float $data */
    public function setPv($data)
    {
        parent::set(self::ATTR_PV, $data);
    }

    /** @param int $data */
    public function setItemRef($data)
    {
        parent::set(self::ATTR_ITEM_REF, $data);
    }
}