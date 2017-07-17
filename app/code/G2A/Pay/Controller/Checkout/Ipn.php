<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Controller\Checkout;

use Magento\Framework\App\ResponseInterface;

class Ipn extends AbstractCheckout
{
    /**
     * @var array
     */
    private $postParams;

    /**
     * @var \G2A\Pay\Helper\Ipn
     */
    protected $_ipnHelper;

    /**
     * Ipn constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(\Magento\Framework\App\Action\Context $context, \G2A\Pay\Helper\Ipn $ipnHelper)
    {
        parent::__construct($context);
        $this->_ipnHelper = $ipnHelper;
        $this->postParams = $this->getRequest()->getParams();
        $this->_setOrder();
    }

    /**
     * Set order object.
     * @throws \G2A\Pay\Exception\Error
     */
    protected function _setOrder()
    {
        if (!isset($this->postParams['userOrderId'])) {
            throw new \G2A\Pay\Exception\Error('Invalid IPN request params');
        }

        $this->_orderFactory = $this->_objectManager->get('Magento\Sales\Model\OrderFactory');
        $this->_order        = $this->_orderFactory->create()->loadByAttribute('entity_id', $this->postParams['userOrderId']);
    }

    /**
     * Dispatch request.
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->_ipnHelper->setOrder($this->_order);

        echo $this->_ipnHelper->process($this->postParams);
    }
}
