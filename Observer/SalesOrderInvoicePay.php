<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Pv\Observer;

use Praxigento\Pv\Repo\Data\Sale as ESale;

/**
 * Update 'date_paid' in PV register and account PV when order is paid completely (bank transfer).
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class SalesOrderInvoicePay
    implements \Magento\Framework\Event\ObserverInterface
{
    /* Names for the items in the event's data */
    const DATA_INVOICE = 'invoice';

    /** @var \Praxigento\Core\Api\Helper\Date */
    private $hlpDate;
    /** @var \Praxigento\Core\Api\App\Logger\Main */
    private $logger;
    /** @var \Praxigento\Pv\Observer\A\Register */
    private $ownRegister;
    /** @var \Praxigento\Pv\Repo\Dao\Sale */
    private $repoSale;

    public function __construct(
        \Praxigento\Core\Api\App\Logger\Main $logger,
        \Praxigento\Pv\Repo\Dao\Sale $repoSale,
        \Praxigento\Core\Api\Helper\Date $hlpDate,
        \Praxigento\Pv\Observer\A\Register $ownRegister
    ) {
        $this->logger = $logger;
        $this->repoSale = $repoSale;
        $this->hlpDate = $hlpDate;
        $this->ownRegister = $ownRegister;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer->getData(self::DATA_INVOICE);
        $state = $invoice->getState();
        if ($state == \Magento\Sales\Model\Order\Invoice::STATE_PAID) {
            /* update date_paid in the PV registry */
            /** @var \Magento\Sales\Model\Order $order */
            $order = $invoice->getOrder();
            $orderId = $order->getEntityId();
            if ($orderId) {
                $datePaid = $this->hlpDate->getUtcNowForDb();
                $this->logger->debug("Update paid date in PV registry on sale order (#$orderId) is paid.");
                $data = [ESale::ATTR_DATE_PAID => $datePaid];
                $this->repoSale->updateById($orderId, $data);
                /* transfer PV to customer account */
                $this->ownRegister->accountPv($order);
            }
        }
    }
}