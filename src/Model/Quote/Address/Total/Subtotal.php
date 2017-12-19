<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Pv\Model\Quote\Address\Total;

use Praxigento\Pv\Config as Cfg;

class Subtotal
    extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /** Code for total itself */
    const CODE = Cfg::CODE_TOTAL_SUBTOTAL;
    /** @var \Praxigento\Pv\Api\Helper\GetPv */
    private $hlpGetPv;
    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface */
    private $hlpPriceCurrency;

    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $hlpPriceCurrency,
        \Praxigento\Pv\Api\Helper\GetPv $hlpGetPv
    ) {
        $this->hlpPriceCurrency = $hlpPriceCurrency;
        $this->hlpGetPv = $hlpGetPv;
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        /* init total structure */
        parent::collect($quote, $shippingAssignment, $total);
        /* reset these totals values */
        $quoteSubtotal = 0;
        /* get fresh grands from calculating totals */
        $grandBase = $total->getData(\Magento\Quote\Api\Data\TotalsInterface::KEY_BASE_GRAND_TOTAL);
        if ($grandBase > 0) {
            /* this is shipping address, compose result (skip processing for billing address)*/
            $items = $quote->getItems();
            if (is_array($items)) {
                /** @var \Magento\Quote\Model\Quote\Item $item */
                foreach ($items as $item) {
                    $qty = $item->getQty();
                    $product = $item->getProduct();
                    $productId = $product->getId();
                    $warehousePv = $this->hlpGetPv->product($productId);
                    $subtotal = number_format($qty * $warehousePv, 2);
                    $quoteSubtotal += $subtotal;
                }
            }
        }
        /* there is no difference between PV and base PV values */
        $total->setBaseTotalAmount(self::CODE, $quoteSubtotal);
        $total->setTotalAmount(self::CODE, $quoteSubtotal);
        return $this;
    }

}