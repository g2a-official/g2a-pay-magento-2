<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Helper;

use Magento\Sales\Model\Order;

/**
 * Class Ipn.
 * @package G2A\Pay\Helper
 */
final class Ipn extends AbstractHelper
{
    const IPN_STATUS_COMPLETE         = 'complete';
    const IPN_STATUS_REFUNDED         = 'refunded';
    const IPN_STATUS_PARTIAL_REFUNDED = 'partial_refunded';
    const IPN_STATUS_REJECTED         = 'rejected';
    const IPN_STATUS_CANCELED         = 'canceled';

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var array
     */
    protected $_data;

    /**
     * Process IPN request.
     *
     * @param array $data
     * @return string
     * @throws \G2A\Pay\Exception\Error
     * @throws \G2A\Pay\Exception\InvalidInput
     */
    public function process(array $data)
    {
        if (!isset($data['status'])) {
            throw new \G2A\Pay\Exception\InvalidInput('Invalid IPN request params');
        }

        $this->_data = $data;

        if (!$this->verifyHash()) {
            throw new \G2A\Pay\Exception\Error('Invalid IPN hash');
        }

        $this->validateOrderData();

        switch ($this->_data['status']) {
            case self::IPN_STATUS_COMPLETE :
                return $this->complete();
            case self::IPN_STATUS_REFUNDED :
                return $this->refund();
            case self::IPN_STATUS_PARTIAL_REFUNDED :
                return $this->partialRefund();
            case self::IPN_STATUS_REJECTED :
            case self::IPN_STATUS_CANCELED :
                return $this->reject();
            default :
                return 'Invalid IPN request params';
        }
    }

    /**
     * Setter for order object.
     *
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->_order = $order;
    }

    /**
     * Proceed actions for complete IPN status.
     *
     * @return string
     */
    private function complete()
    {
        $payment = $this->_order->getPayment();
        $payment->setTransactionId($this->_data['transactionId']);
        $payment
            ->setIsTransactionApproved(true)
            ->setShouldCloseParentTransaction(true)
            ->setIsTransactionClosed(true);

        if ($this->_order->canInvoice()) {
            $invoice = $this->_order->prepareInvoice();
            $invoice->register();
            $invoice->setTransactionId($this->_data['transactionId']);
            $this->_order->addRelatedObject($invoice);
        }
        $this->_order->addStatusHistoryComment('G2A Pay IPN update: payment complete', Order::STATE_COMPLETE);
        $this->_order->getResource()->save($this->_order);

        return 'Payment completed';
    }

    /**
     * Proceed actions for refunded IPN status.
     *
     * @return string
     */
    private function refund()
    {
        $this->_order->addStatusHistoryComment('G2A Pay IPN update: payment refund by ' . $this->_data['refundedAmount']);
        $this->_order->getResource()->save($this->_order);

        return 'Order refunded';
    }

    /**
     * Proceed actions for partial_refunded IPN status.
     *
     * @return string
     */
    private function partialRefund()
    {
        $this->_order->addStatusHistoryComment('G2A Pay IPN update: payment refund by ' . $this->_data['refundedAmount']);
        $this->_order->getResource()->save($this->_order);

        return 'Order partially refunded';
    }

    /**
     * Proceed actions for rejected IPN status.
     *
     * @return string
     */
    private function reject()
    {
        $this->_order->cancel();
        $this->_order->addStatusHistoryComment('G2A Pay IPN update: payment rejected');
        $this->_order->getResource()->save($this->_order);

        return 'Order rejected';
    }

    /**
     * Verifies IPN hash.
     *
     * @return bool
     */
    private function verifyHash()
    {
        return $this->_data['hash'] === $this->generateIpnHash();
    }

    /**
     * returns IPN hash.
     *
     * @return string
     */
    private function generateIpnHash()
    {
        return $this->hash($this->_data['transactionId']
                           . $this->_data['userOrderId']
                           . $this->roundPrice($this->_order->getGrandTotal())
                           . $this->getConfigData('api_secret'));
    }

    /**
     * Validates order.
     * 
     * @throws \G2A\Pay\Exception\InvalidInput
     */
    private function validateOrderData()
    {
        if ($this->_order->getGrandTotal() != $this->_data['amount']) {
            throw new \G2A\Pay\Exception\InvalidInput('Invalid order amount provided');
        }

        if ($this->_order->getOrderCurrencyCode() !== $this->_data['currency']) {
            throw new \G2A\Pay\Exception\InvalidInput('Invalid order currency provided');
        }
    }
}
