<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Controller\Checkout;

use G2A\Pay\Helper\Data;
use G2A\Pay\Model\Client\Curl as G2AClient;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Redirect.
 * @package G2A\Pay\Controller\Checkout
 */
class Redirect extends AbstractCheckout
{
    /**
     * @var \G2A\Pay\Helper\Data
     */
    protected $_helper;

    /**
     * @var \G2A\Pay\Model\Client\Curl
     */
    protected $_curl;

    /**
     * Redirect constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Data $helper
     * @param G2AClient $curl
     */
    public function __construct(\Magento\Framework\App\Action\Context $context, Data $helper, G2AClient $curl)
    {
        $this->_curl   = $curl;
        $this->_helper = $helper;
        parent::__construct($context);
        $this->_setOrder();
    }

    /**
     * Redirects to G2A Pay checkout.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \G2A\Pay\Exception\Error
     */
    public function execute()
    {
        $this->_curl->setMethod(G2AClient::METHOD_POST);
        $this->_curl->setUrl($this->_helper->getCreateQuoteUrl());
        $result = $this->_curl->request($this->_helper->createArrayOfCheckoutParams($this->_order));
        if (!$this->_helper->verifyResponse($result, true)) {
            throw new \G2A\Pay\Exception\Error('Wrong data returned from server');
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_helper->getCheckoutUrl($result['token']));

        return $resultRedirect;
    }

    /**
     * Set order object.
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _setOrder()
    {
        if (!$this->_order) {
            $incrementId         = $this->_getCheckout()->getLastRealOrderId();
            $this->_orderFactory = $this->_objectManager->get('Magento\Sales\Model\OrderFactory');
            $this->_order        = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }
}
