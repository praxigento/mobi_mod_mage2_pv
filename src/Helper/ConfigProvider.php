<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Pv\Helper;

use Praxigento\Pv\Repo\Entity\Data\Quote as EPvQuote;
use Praxigento\Pv\Repo\Entity\Data\Quote\Item as EPvQuoteItem;

/**
 * Populate cart/quote JSON configuration with PV data (JS globals on frontend: "window.checkoutConfig = {}").
 *
 * see \Magento\Checkout\Model\ConfigProviderInterface
 */
class ConfigProvider
{
    const JSON_CAN_SEE_PV = 'praxigentoCustomerCanSeePv';
    const JSON_ITEM_CAN_SEE_PV = 'prxgt_pv_can_see'; // flag bound to the item (TODO: use JSON_CAN_SEE_PV on the front)
    const JSON_ITEM_PV_TOTAL = 'prxgt_pv_total';
    const JSON_TOTAL_SEG_DISCOUNT = 'prxgt_pv_cart_discount';
    const JSON_TOTAL_SEG_GRAND = 'prxgt_pv_cart_grand';
    const JSON_TOTAL_SEG_SUBTOTAL = 'prxgt_pv_cart_subtotal';

    /** @var \Praxigento\Pv\Helper\Customer */
    private $hlpPvCust;
    /** @var \Praxigento\Core\Api\Helper\Registry */
    private $hlpReg;
    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    private $repoCart;
    /** @var \Praxigento\Pv\Repo\Entity\Quote */
    private $repoPvQuote;
    /** @var \Praxigento\Pv\Repo\Entity\Quote\Item */
    private $repoPvQuoteItem;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $repoCart,
        \Praxigento\Core\Api\Helper\Registry $hlpReg,
        \Praxigento\Pv\Repo\Entity\Quote $repoPvQuote,
        \Praxigento\Pv\Repo\Entity\Quote\Item $repoPvQuoteItem,
        \Praxigento\Pv\Helper\Customer $hlpPvCust
    ) {
        $this->repoCart = $repoCart;
        $this->hlpReg = $hlpReg;
        $this->repoPvQuote = $repoPvQuote;
        $this->repoPvQuoteItem = $repoPvQuoteItem;
        $this->hlpPvCust = $hlpPvCust;
    }

    /**
     * Populate checkout configuration with PV data.
     *
     * @param array $configData
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addPvData($configData)
    {
        $custGroupId = $this->getCustomerGroupId($configData);
        if (!is_null($custGroupId)) {
            $canSeePv = $this->hlpPvCust->canSeePv($custGroupId);
            $configData[self::JSON_CAN_SEE_PV] = $canSeePv;
            if ($canSeePv) {
                $configData = $this->addPvTotalsToCart($configData);
                $configData = $this->addPvToItems($configData, $canSeePv);
            }
        }
        return $configData;
    }

    /**
     * Populate checkout configuration with 'CanSeePv' flag.
     *
     * @param array $configData
     * @param bool $canSeePv
     * @return array
     */
    private function addPvToItems($configData, $canSeePv)
    {
        if (
            isset($configData['totalsData']) &&
            isset($configData['totalsData']['items']) &&
            is_array($configData['totalsData']['items'])
        ) {
            $items = $configData['totalsData']['items'];
            foreach ($items as $key => $item) {
                $id = $item['item_id'];
                /** @var EPvQuoteItem $pvData */
                $pvData = $this->repoPvQuoteItem->getById($id);
                if ($pvData) {
                    $total = $pvData->getTotal();
                    $total = number_format($total, 2, '.', '');
                    $item[self::JSON_ITEM_PV_TOTAL] = $total;
                }
                $item[self::JSON_ITEM_CAN_SEE_PV] = $canSeePv;
                $items[$key] = $item;
            }
            $configData['totalsData']['items'] = $items;
        }
        return $configData;
    }

    /**
     * @param array $configData
     * @return array
     */
    private function addPvTotalsToCart($configData)
    {
        $cartId = $this->getCartId($configData);
        /** @var EPvQuote $pvTotals */
        $pvTotals = $this->repoPvQuote->getById($cartId);
        if ($pvTotals) {
            /* get totals segments from configuration data (different structures for diff. requests) */
            $totals = null;
            if (isset($configData['total_segments'])) {
                $totals = $configData['total_segments'];
            } elseif (isset($configData['totalsData']['total_segments'])) {
                $totals = $configData['totalsData']['total_segments'];
            }
            /* populate totals segments with PV data */
            if ($totals) {
                $sub = $total = number_format($pvTotals->getSubtotal(), 2, '.', '');
                $discount = $total = number_format($pvTotals->getDiscount(), 2, '.', '');
                $grand = $total = number_format($pvTotals->getTotal(), 2, '.', '');
                $segment = [
                    'code' => self::JSON_TOTAL_SEG_SUBTOTAL,
                    'value' => $sub
                ];
                $totals[] = $segment;
                $segment = [
                    'code' => self::JSON_TOTAL_SEG_DISCOUNT,
                    'value' => $discount
                ];
                $totals[] = $segment;
                $segment = [
                    'code' => self::JSON_TOTAL_SEG_GRAND,
                    'value' => $grand
                ];
                $totals[] = $segment;
                /* put PV into config */
                if (isset($configData['total_segments'])) {
                    $configData['total_segments'] = $totals;
                } elseif (isset($configData['totalsData']['total_segments'])) {
                    $configData['totalsData']['total_segments'] = $totals;
                }
            }

        }
        return $configData;
    }

    /**
     * Get cart using REST input parameters.
     *
     * @return \Magento\Quote\Api\Data\CartInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCart()
    {
        $result = null;
        $restIn = $this->hlpReg->getRestInputParams();
        if (
            is_array($restIn) &&
            (count($restIn) >= 2) &&
            ($restIn[1] instanceof \Magento\Checkout\Model\TotalsInformation)
        ) {
            /* probably this is '/V1/carts/mine/totals-information' request where first arg is cartId */
            $cartId = (int)$restIn[0];
            $result = $this->repoCart->get($cartId);
        }
        return $result;
    }

    private function getCartId($configData)
    {
        $result = null;
        if (
            isset($configData['quoteData']) &&
            isset($configData['quoteData']['entity_id'])
        ) {
            $result = (int)$configData['quoteData']['entity_id'];
        } else {
            $cart = $this->getCart();
            if ($cart) {
                $result = $cart->getId();
            }
        }
        return $result;
    }

    /**
     * Get customer group from config data or from REST input data.
     *
     * @param $configData
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerGroupId($configData)
    {
        $result = null;
        if (
            isset($configData['quoteData']) &&
            isset($configData['quoteData']['customer_group_id'])
        ) {
            $result = (int)$configData['quoteData']['customer_group_id'];
        } else {
            $cart = $this->getCart();
            if ($cart) {
                $result = $cart->getCustomerGroupId();
            }
        }
        return $result;
    }
}