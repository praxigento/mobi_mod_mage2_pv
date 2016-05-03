<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Pv\Service\Transfer\Request;

class CreditToCustomer extends Base
{
    const TO_CUSTOMER_ID = 'to_customer_id';

    /**
     * @return int
     */
    public function getToCustomerId()
    {
        $result = parent::getData(self::TO_CUSTOMER_ID);
        return $result;
    }

    /**
     * @param int $data
     */
    public function setToCustomerId($data)
    {
        parent::setData(self::TO_CUSTOMER_ID, $data);
    }
}