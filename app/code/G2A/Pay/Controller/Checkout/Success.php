<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Controller\Checkout;

use Magento\Framework\App\Action\Action;

/**
 * Class Success.
 * @package G2A\Pay\Controller\Checkout
 */
class Success extends Action
{
    /**
     * Handle action after successful payment.
     */
    public function execute()
    {
        $this->messageManager->addSuccessMessage(__('Payment processed successfully'));
        $this->_redirect('checkout/onepage/success/');
    }
}
