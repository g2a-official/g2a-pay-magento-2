<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * Class AbstractHelper.
 * @package G2A\Pay\Helper
 */
abstract class AbstractHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const REFUND_SANDBOX_URL          = 'https://www.test.pay.g2a.com/rest/transactions/';
    const REFUND_PRODUCTION_URL       = 'https://pay.g2a.com/rest/transactions/';
    const RESPONSE_SUCCESS_STATUS     = 'ok';

    /**
     * Return config value for specified field.
     *
     * @param $field
     * @return string
     */
    public function getConfigData($field)
    {
        return $this->scopeConfig->getValue('payment/g2a_pay/' . $field,
            ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return order price rounded to two decimal places.
     *
     * @param $price
     * @return string
     */
    public function roundToTwoDecimal($price)
    {
        return number_format((float) $price, 2, '.', '');
    }

    /**
     * Round order price.
     *
     * @param $price
     * @return float
     */
    public function roundPrice($price)
    {
        return round($price, 2);
    }

    /**
     * Return refund URL.
     *
     * @return string
     */
    public function getRefundUrl()
    {
        if ($this->getConfigData('sandbox')) {
            return self::REFUND_SANDBOX_URL;
        }

        return self::REFUND_PRODUCTION_URL;
    }

    /**
     * Returns string hashed with sha256.
     *
     * @param $string
     * @return string
     */
    public function hash($string)
    {
        return hash('sha256', $string);
    }

    /**
     * Verifies response from G2A Pay server.
     *
     * @param $response
     * @param bool $tokenRequired
     * @return bool
     */
    public function verifyResponse($response, $tokenRequired = false)
    {
        if (!isset($response['status'])
            || strtolower($response['status']) !== self::RESPONSE_SUCCESS_STATUS) {
            return false;
        }

        if ($tokenRequired && !isset($response['token'])) {
            return false;
        }

        return true;
    }
}
