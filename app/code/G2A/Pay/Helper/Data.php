<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Helper;

use Magento\Catalog\Helper\Product;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;

/**
 * Class Data.
 * @package G2A\Pay\Helper
 */
final class Data extends AbstractHelper
{
    const CREATE_QUOTE_PRODUCTION_URL = 'https://checkout.pay.g2a.com/index/createQuote';
    const CREATE_QUOTE_SANDBOX_URL    = 'https://checkout.test.pay.g2a.com/index/createQuote';
    const CHECKOUT_SANDBOX_URL        = 'https://checkout.test.pay.g2a.com/index/gateway?token=';
    const CHECKOUT_PRODUCTION_URL     = 'https://checkout.pay.g2a.com/index/gateway?token=';

    /**
     * @var Product
     */
    private $_catalogProductHelper;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Product $catalogProductHelper
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context,
                                Product $catalogProductHelper)
    {
        $this->_catalogProductHelper = $catalogProductHelper;
        parent::__construct($context);
    }

    /**
     * Returns array with checkout params.
     *
     * @param Order $order
     * @return array
     */
    public function createArrayOfCheckoutParams(Order $order)
    {
        $orderParams = [
            'api_hash'    => $this->getConfigData('api_hash'),
            'hash'        => $this->generateOrderHash($order),
            'order_id'    => $order->getId(),
            'email'       => $order->getCustomerEmail(),
            'amount'      => $this->roundToTwoDecimal($order->getGrandTotal()),
            'currency'    => $order->getOrderCurrencyCode(),
            'items'       => $this->getCreateQuoteItemsData($order),
            'description' => '',
            'url_failure' => $this->_urlBuilder->getUrl('g2apay/checkout/failure'),
            'url_ok'      => $this->_urlBuilder->getUrl('g2apay/checkout/success'),
        ];

        if (!is_null($addresses = $this->getAddresses($order))) {
            $orderParams['addresses'] = $addresses;
        }

        return $orderParams;
    }

    /**
     * Returns array with billing and shipping address.
     *
     * @param Order $order
     * @return array
     */
    public function getAddresses(Order $order)
    {
        $addresses       = [];
        $billingAddress  = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        if (is_null($billingAddress) && is_null($shippingAddress)) {
            return;
        }

        $addresses['billing'] = $this->generateAddressArray($billingAddress);

        if (is_null($shippingAddress)) {
            $addresses['shipping'] = $addresses['billing'];

            return $addresses;
        }

        $addresses['shipping'] = $this->generateAddressArray($shippingAddress);

        return $addresses;
    }

    /**
     * Returns address array.
     *
     * @param Address $address
     * @return array
     */
    public function generateAddressArray(Address $address)
    {
        return [
            'firstname' => $address->getFirstname(),
            'lastname'  => $address->getLastname(),
            'line_1'    => $address->getStreetLine(1),
            'line_2'    => $address->getStreetLine(2),
            'zip_code'  => $address->getPostcode(),
            'city'      => $address->getCity(),
            'company'   => is_null($address->getCompany()) ? '' : $address->getCompany(),
            'county'    => $address->getRegion(),
            'country'   => $address->getCountryId(),
        ];
    }

    /**
     * Maps order items array into data
     * To use with G2A Pay Create quote data.
     *
     * @param Order $order
     * @return array
     */
    public function getCreateQuoteItemsData(Order $order)
    {
        $data = [];

        $items = $order->getAllVisibleItems();

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $item) {
            $qty       = $item->getQtyOrdered();
            $productId = $item->getId();
            $itemPrice = $this->roundToTwoDecimal($item->getPrice() - $item->getDiscountAmount());
            $data[]    = [
                'qty'    => $qty,
                'name'   => $item->getName(),
                'sku'    => $item->getSku(),
                'amount' => $itemPrice * $qty,
                'type'   => $item->getProductType(),
                'id'     => $productId,
                'price'  => $itemPrice,
                'url'    => $this->_catalogProductHelper->getProductUrl($productId),
            ];
        }

        $shippingAmount = $this->roundToTwoDecimal($order->getShippingAmount() - $order->getShippingDiscountAmount());

        if ($shippingAmount != 0) {
            $shippingMethod = $order->getShippingMethod(true);
            $data[]         = [
                'qty'    => 1,
                'name'   => $shippingMethod->getData('method'),
                'sku'    => 1,
                'amount' => $shippingAmount,
                'type'   => $shippingMethod->getData('carrier_code'),
                'id'     => 1,
                'price'  => $shippingAmount,
                'url'    => $this->_getUrl(''),
            ];
        }

        $taxAmount = $this->roundToTwoDecimal($order->getTaxAmount());

        if ($taxAmount != 0) {
            $data[] = [
                'qty'    => 1,
                'name'   => 'Order Tax',
                'sku'    => 1,
                'amount' => $taxAmount,
                'type'   => 'tax',
                'id'     => 1,
                'price'  => $taxAmount,
                'url'    => $this->_getUrl(''),
            ];
        }

        return $data;
    }

    /**
     * Generates API hash for given $order
     * To use with G2A Pay create quote request.
     *
     * @param Order $order
     * @return string
     */
    private function generateOrderHash(Order $order)
    {
        return $this->hash($order->getId()
                           . $this->roundToTwoDecimal($order->getGrandTotal())
                           . $order->getOrderCurrencyCode()
                           . $this->getConfigData('api_secret'));
    }

    /**
     * Returns createQuote URL for active payment mode.
     *
     * @return string
     */
    public function getCreateQuoteUrl()
    {
        if ($this->getConfigData('sandbox')) {
            return self::CREATE_QUOTE_SANDBOX_URL;
        }

        return self::CREATE_QUOTE_PRODUCTION_URL;
    }

    /**
     * Returns checkout URL for active payment mode.
     *
     * @param null|string $token
     * @return string
     * @throws \G2A\Pay\Exception\InvalidInput
     */
    public function getCheckoutUrl($token = null)
    {
        if (empty($token) || !is_string($token)) {
            throw new \G2A\Pay\Exception\InvalidInput('Provided checkout token is invalid');
        }
        if ($this->getConfigData('sandbox')) {
            return self::CHECKOUT_SANDBOX_URL . $token;
        }

        return self::CHECKOUT_PRODUCTION_URL . $token;
    }
}
