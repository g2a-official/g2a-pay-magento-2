<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Controller\Checkout;

use Magento\Framework\App\Action\Action;

/**
 * Class AbstractCheckout.
 * @package G2A\Pay\Controller\Checkout
 */
abstract class AbstractCheckout extends Action
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * Return checkout session object.
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * Set order object.
     *
     * @return \Magento\Sales\Model\Order
     */
    abstract protected function _setOrder();
}
