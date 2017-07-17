<?php
/**
 * @author    G2A Team
 * @copyright Copyright (c) 2016 G2A.COM
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Model;

use G2A\Pay\Helper\Data;
use G2A\Pay\Helper\Refund;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;

/**
 * Class G2aPay.
 * @package G2A\Pay\Model
 */
class G2aPay implements MethodInterface
{
    /**
     * Payment code.
     *
     * @var string
     */
    protected $_code = 'g2apay';

    /**
     * @var string
     */
    protected $_formBlockType = 'Magento\Payment\Block\Form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info';

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canOrder = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCapture = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCaptureOnce = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canVoid = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = false;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canReviewPayment = false;

    /**
     * This may happen when amount is captured, but not settled.
     * @var bool
     */
    protected $_canCancelInvoice = true;

    /**
     * Payment data.
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentData;

    /**
     * Core store config.
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var int
     */
    protected $_storeId;

    /**
     * @var InfoInterface
     */
    protected $_infoInstance;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Refund
     */
    protected $_helperRefund;

    /**
     * G2aPay constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $helperData,
        Refund $helperRefund,
        \Magento\Framework\Model\Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_helper       = $helperData;
        $this->_helperRefund = $helperRefund;
        $this->_eventManager = $context->getEventDispatcher();
        $this->_scopeConfig  = $scopeConfig;
    }

    /**
     * Initializes injected data.
     *
     * @param array $data
     * @return void
     */
    protected function initializeData($data = [])
    {
        if (!empty($data['formBlockType'])) {
            $this->_formBlockType = $data['formBlockType'];
        }
    }

    /**
     * Setter for storeId.
     *
     * @param int $storeId
     */
    public function setStore($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * Returns storeId.
     *
     * @return int
     */
    public function getStore()
    {
        return $this->_storeId;
    }

    /**
     * Check order availability.
     *
     * @return bool
     */
    public function canOrder()
    {
        return $this->_canOrder;
    }

    /**
     * Check authorize availability.
     *
     * @return bool
     */
    public function canAuthorize()
    {
        return $this->_canAuthorize;
    }

    /**
     * Check capture availability.
     *
     * @return bool
     */
    public function canCapture()
    {
        return $this->_canCapture;
    }

    /**
     * Check partial capture availability.
     *
     * @return bool
     */
    public function canCapturePartial()
    {
        return $this->_canCapturePartial;
    }

    /**
     * Check whether capture can be performed once and no further capture possible.
     *
     * @return bool
     */
    public function canCaptureOnce()
    {
        return $this->_canCaptureOnce;
    }

    /**
     * Check refund availability.
     *
     * @return bool
     */
    public function canRefund()
    {
        return $this->_canRefund;
    }

    /**
     * Check partial refund availability for invoice.
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        return $this->_canRefundInvoicePartial;
    }

    /**
     * Check void availability.
     * @return bool
     */
    public function canVoid()
    {
        return $this->_canVoid;
    }

    /**
     * Using internal pages for input payment data
     * Can be used in admin.
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return $this->_canUseInternal;
    }

    /**
     * Can be used in regular checkout.
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        return $this->_canUseCheckout;
    }

    /**
     * Can be edit order (renew order).
     *
     * @return bool
     */
    public function canEdit()
    {
        return true;
    }

    /**
     * Check fetch transaction info availability.
     *
     * @return bool
     */
    public function canFetchTransactionInfo()
    {
        return $this->_canFetchTransactionInfo;
    }

    /**
     * Fetch transaction info.
     *
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return [];
    }

    /**
     * Retrieve payment system relation flag.
     *
     * @return bool
     */
    public function isGateway()
    {
        return $this->_isGateway;
    }

    /**
     * Retrieve payment method online/offline flag.
     *
     * @return bool
     */
    public function isOffline()
    {
        return $this->_isOffline;
    }

    /**
     * Flag if we need to run payment initialize while order place.
     *
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return $this->_isInitializeNeeded;
    }

    /**
     * To check billing country is allowed for the payment method.
     *
     * @param string $country
     * @return bool
     */
    public function canUseForCountry($country)
    {
        return true;
    }

    /**
     * Check method for processing with base currency.
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * Retrieve payment method code.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCode()
    {
        if (empty($this->_code)) {
            throw new LocalizedException(__('We cannot retrieve the payment method code.'));
        }

        return $this->_code;
    }

    /**
     * Retrieve block type for method form generation.
     *
     * @return string
     */
    public function getFormBlockType()
    {
        return $this->_formBlockType;
    }

    /**
     * Retrieve block type for display method information.
     *
     * @return string
     */
    public function getInfoBlockType()
    {
        return $this->_infoBlockType;
    }

    /**
     * Retrieve payment information model object.
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getInfoInstance()
    {
        $instance = $this->_infoInstance;
        if (!$instance instanceof InfoInterface) {
            throw new LocalizedException(__('We cannot retrieve the payment information object instance.'));
        }

        return $instance;
    }

    /**
     * Retrieve payment information model object.
     *
     * @param InfoInterface $info
     * @return void
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->_infoInstance = $info;
    }

    /**
     * Validate payment method information object.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Payment) {
            $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }
        if (!$this->canUseForCountry($billingCountry)) {
            throw new LocalizedException(
                __('You can\'t use the payment type you selected to make payments to the billing country.')
            );
        }

        return $this;
    }

    /**
     * Order payment abstract method.
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function order(InfoInterface $payment, $amount)
    {
        if (!$this->canOrder()) {
            throw new LocalizedException(__('The order action is not available.'));
        }

        return $this;
    }

    /**
     * Authorize payment abstract method.
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }

        return $this;
    }

    /**
     * Capture payment abstract method.
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        return $this;
    }

    /**
     * Refund specified amount for payment.
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \G2A\Pay\Exception\Error
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        }

        if (!$this->_helperRefund->verifyResponse($this->_helperRefund
            ->proceed($payment->getData()['creditmemo']->getOrder(), $amount))) {
            throw new \G2A\Pay\Exception\Error('Online refund fails');
        }

        return $this;
    }

    /**
     * Cancel payment abstract method.
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     */
    public function cancel(InfoInterface $payment)
    {
        return $this;
    }

    /**
     * Void payment abstract method.
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment)
    {
        if (!$this->canVoid()) {
            throw new LocalizedException(__('The void action is not available.'));
        }

        return $this;
    }

    /**
     * Whether this method can accept or deny payment.
     * @return bool
     */
    public function canReviewPayment()
    {
        return $this->_canReviewPayment;
    }

    /**
     * Attempt to accept a payment that us under review.
     *
     * @param InfoInterface $payment
     * @return false
     * @throws LocalizedException
     */
    public function acceptPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
    }

    /**
     * Attempt to deny a payment that us under review.
     *
     * @param InfoInterface $payment
     * @return false
     * @throws LocalizedException
     */
    public function denyPayment(InfoInterface $payment)
    {
        if (!$this->canReviewPayment()) {
            throw new LocalizedException(__('The payment review action is unavailable.'));
        }

        return false;
    }

    /**
     * Retrieve payment method title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->_helper->getCreateQuoteUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/g2a_pay/' . $field;

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Assign data to info model instance.
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->_eventManager->dispatch(
            'payment_method_assign_data_' . $this->getCode(),
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE   => $data,
            ]
        );

        $this->_eventManager->dispatch(
            'payment_method_assign_data',
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE   => $data,
            ]
        );

        return $this;
    }

    /**
     * Check whether payment method can be used.
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return (bool) (int) $this->getConfigData('active');
    }

    /**
     * Is active.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) (int) $this->getConfigData('active', $storeId);
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true.
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
    }

    /**
     * Get config payment action url
     * Used to universalize payment actions when processing payment place.
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }
}
