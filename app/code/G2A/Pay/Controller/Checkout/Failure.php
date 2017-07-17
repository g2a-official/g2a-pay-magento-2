<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Controller\Checkout;

use Magento\Framework\App\Action\Action;

/**
 * Class Failure.
 * @package G2A\Pay\Controller\Checkout
 */
class Failure extends Action
{
    /**
     * Handle failed payment action.
     */
    public function execute()
    {
        $this->messageManager
            ->addErrorMessage(__('Customer has left G2A Pay checkout before completing the payment or payment fails'));
        $this->_redirect('checkout/onepage/failure/');
    }
}
