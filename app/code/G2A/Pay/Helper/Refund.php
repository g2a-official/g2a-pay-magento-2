<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Helper;

use G2A\Pay\Model\Client\Curl as G2AClient;
use Magento\Sales\Model\Order;

/**
 * Class Refund.
 * @package G2A\Pay\Helper
 */
final class Refund extends AbstractHelper
{
    /**
     * @var \G2A\Pay\Model\Client\Curl
     */
    protected $_curl;

    /**
     * Refund constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param G2AClient $client
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context, G2AClient $client)
    {
        parent::__construct($context);
        $this->_curl = $client;
    }

    /**
     * Proceeds refund request.
     *
     * @param Order $order
     * @param $amount
     * @return array
     */
    public function proceed(Order $order, $amount)
    {
        $this->_curl->setUrl($this->getRefundUrl() . $order->getInvoiceCollection()->getData()[0]['transaction_id']);
        $this->_curl->setHeaders(['Authorization' => $this->generateAuthorizationHeader()]);
        $this->_curl->setMethod(G2AClient::METHOD_PUT);

        return $this->_curl->request([
            'action' => 'refund',
            'amount' => $amount,
            'hash'   => $this->generateHash($order, $amount),
        ]);
    }

    /**
     * Returns refund hash.
     *
     * @param Order $order
     * @param $amount
     * @return string
     */
    public function generateHash(Order $order, $amount)
    {
        return $this->hash($order->getInvoiceCollection()->getData()[0]['transaction_id']
                           . $order->getId()
                           . $this->roundToTwoDecimal($order->getGrandTotal())
                           . $this->roundToTwoDecimal($amount)
                           . $this->getConfigData('api_secret'));
    }

    /**
     * Returns authorization hash.
     *
     * @return string
     */
    public function generateAuthorizationHeader()
    {
        return $this->getConfigData('api_hash') . ';' . $this->hash($this->getConfigData('api_hash')
                   . $this->getConfigData('merchant_email')
                   . $this->getConfigData('api_secret'));
    }
}
