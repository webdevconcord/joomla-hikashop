<?php

/**
 * PHP version 7.4.26
 *
 * @category  Class
 * @package   HikaShop
 * @author    ConcordPay <serhii.shylo@mustpay.tech>
 * @copyright 2021 ConcordPay
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://concordpay.concord.ua
 * @since     3.8.0
 */

// Protection against direct access
defined('_JEXEC') or die('Restricted access');
?><?php

/**
 * Class plgHikashoppaymentConcordpay
 *
 * @category Class
 * @package  HikaShop
 * @author   ConcordPay <serhii.shylo@mustpay.tech>
 * @license  GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link     https://concordpay.concord.ua
 *
 * @since version 3.8.0
 *
 * @property stdClass $payment_params
 * @property stdClass $currency
 * @property string $url_itemid
 * @property array $vars
 */
class plgHikashoppaymentConcordpay extends hikashopPaymentPlugin
{
    const RESPONSE_TYPE_PAYMENT = 'payment';
    const RESPONSE_TYPE_REVERSE = 'reverse';
    const SIGNATURE_SEPARATOR   = ';';

    const TRANSACTION_STATUS_APPROVED = 'Approved';
    const TRANSACTION_STATUS_DECLINED = 'Declined';

    /**
     * Array keys for generate request signature.
     *
     * @var   string[]
     * @since 3.8.0
     */
    protected $keysForRequestSignature = array(
        'merchant_id',
        'order_id',
        'amount',
        'currency_iso',
        'description'
    );

    /**
     * Array keys for generate response signature.
     *
     * @var   string[]
     * @since 3.8.0
     */
    protected $keysForResponseSignature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency'
    );

    /**
     * Allowed response operation type.
     *
     * @var string[]
     *
     * @since version 3.8.0
     */
    protected $allowedOperationTypes = array(
        self::RESPONSE_TYPE_PAYMENT,
        self::RESPONSE_TYPE_REVERSE
    );

    /**
     * ConcordPay API URL.
     *
     * @var   string
     * @since version 3.8.0
     */
    protected $url = 'https://pay.concord.ua/api/';

    /**
     * Load the language file on instantiation
     *
     * @var   boolean
     * @since 3.1
     */
    protected $autoloadLanguage = true;

    /**
     * Currencies, the module can work with this.
     *
     * @var   string[]
     * @since version 3.8.0
     */
    public $accepted_currencies = array(980 => 'UAH');

    /**
     * Debug data.
     *
     * @var   array
     * @since version 3.8.0
     */
    public $debugData = array();

    /**
     * Does this field support multiple values?
     *
     * @var   bool
     * @since version 3.8.0
     */
    public $multiple = true;

    /**
     * Payment gateway name.
     *
     * @var   string
     * @since version 3.8.0
     */
    public $name = 'concordpay';

    /**
     * Plugin admin settings.
     *
     * @var   array
     * @since version
     */
    public $pluginConfig = array(
        'merchant_id'      => array('CONCORDPAY_MERCHANT_ID', 'input'),
        'secret_key'       => array('CONCORDPAY_SECRET_KEY', 'input'),
        'iframe'           => array('CONCORDPAY_IFRAME_MODE', 'boolean'),
        'verified_status'  => array('CONCORDPAY_VERIFIED_STATUS', 'orderstatus'),
        'invalid_status'   => array('CONCORDPAY_INVALID_STATUS', 'orderstatus'),
        'refunded_status'  => array('CONCORDPAY_REFUNDED_STATUS', 'orderstatus'),
        'language'         => array('CONCORDPAY_PAGE_LANGUAGE', 'list', array(
            'uk' => 'UK',
            'ru' => 'RU',
            'en' => 'EN'
        )),
    );

    /**
     * This method will be called by HikaShop when the order is created at the end of the checkout.
     * You might want to do some processing here.
     * If you are developing a payment gateway plugin,
     * you might want to redirect the customer to the gateway payment page.
     * In the information sent to it, you will probably be able to specify a notification url.
     *
     * @param $order     stdClass
     * @param $methods   array
     * @param $method_id string
     *
     * @return bool|void
     *
     * @since version 3.8.0
     */
    function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        parent::onAfterOrderConfirm($order, $methods, $method_id);
        if (empty($this->payment_params)) {
            return false;
        }

        $this->vars = $this->getVars($order);

        return $this->showPage('end');
    }

    /**
     * Generate payment form params.
     *
     * @param $order stdClass Order object.
     *
     * @return array
     *
     * @since version 3.8.0
     */
    function getVars(stdClass $order)
    {
        $base_url     = HIKASHOP_LIVE . 'index.php?option=com_hikashop';
        $approve_url  = $base_url . '&ctrl=checkout&task=after_end&order_id=' . $order->order_id . $this->url_itemid;
        $decline_url  = $base_url . '&ctrl=order&task=cancel_order&order_id=' . $order->order_id . $this->url_itemid;
        $callback_url = $base_url . '&ctrl=checkout&task=notify&notif_payment=concordpay&tmpl=component&lang=' . $this->locale . $this->url_itemid;

        $client_first_name = $order->cart->billing_address->address_firstname;
        $client_last_name  = $order->cart->billing_address->address_lastname;

        $email = $order->customer->user_email;
        $phone = $order->cart->billing_address->address_telephone;

        $description = JText::_('CONCORDPAY_ORDER_DESCRIPTION') . ' ' . htmlspecialchars($_SERVER['HTTP_HOST'])
            . " , $client_first_name $client_last_name, $phone.";
        $vars = array(
            'operation'    => 'Purchase',
            'merchant_id'  => $this->payment_params->merchant_id,
            'amount'       => $order->order_full_price,
            'order_id'     => $order->order_id,
            'currency_iso' => $order->order_currency_info->currency_code,
            'description'  => $description,
            'approve_url'  => $approve_url,
            'decline_url'  => $decline_url,
            'cancel_url'   => $decline_url,
            'callback_url' => $callback_url,
            'language'     => $this->payment_params->language,
            // Statistics.
            'client_last_name'  => $client_last_name,
            'client_first_name' => $client_first_name,
            'email' => $email,
            'phone' => $phone,
        );

        $vars['signature'] = $this->getRequestSignature($vars);

        return $vars;
    }

    /**
     * This method will be called by HikaShop when a payment notification is received for your plugin.
     * Here you will have to make sure that the notification is securized
     * and then you will be able to update the order based on the payment information.
     *
     * @param $statuses string[]
     *
     * @return bool
     *
     * @since version 3.8.0
     */
    function onPaymentNotification(&$statuses)
    {
        $response = json_decode(file_get_contents('php://input'), true);
        $order_id = (int)$response['orderReference'];
        $dbOrder  = $this->getOrder($order_id);

        if (empty($dbOrder)) {
            echo "Could not load any order for your notification $order_id";
            return false;
        }

        $this->loadPaymentParams($dbOrder);
        if (empty($this->payment_params)) {
            return false;
        }
        $this->loadOrderData($dbOrder);

        // Check merchant.
        $merchant_id = $this->payment_params->merchant_id;
        if (!isset($response['merchantAccount']) || $response['merchantAccount'] !== $merchant_id) {
            return false;
        }

        // Check amount.
        $amount = (float)$dbOrder->order_full_price;
        if (!isset($response['amount']) || (float)$response['amount'] !== $amount) {
            return false;
        }

        // Check currency.
        $currency = $this->currency->currency_code;
        if (!isset($response['currency']) || $response['currency'] !== $currency) {
            return false;
        }

        // Check operation type.
        if (!isset($response['type']) || !in_array($response['type'], $this->allowedOperationTypes, true)) {
            return false;
        }

        // Check signature.
        $signData = array(
            'merchantAccount' => $merchant_id,
            'orderReference'  => $order_id,
            'amount'          => $amount,
            'currency'        => $currency
        );

        $history   = new stdClass();
        $signature = $this->getResponseSignature($signData);
        if (!isset($response['merchantSignature']) || $response['merchantSignature'] !== $signature) {
            $history->notified = 0;
            $history->reason = JText::_('CONCORDPAY_ERROR_INVALID_SIGNATURE');
            $history->data = "Error: Invalid signature ConcordPay transaction ID: " . $response['transactionId'];

            $this->modifyOrder($order_id, $this->payment_params->invalid_status, $history);

            return false;
        }

        $order_status = $this->payment_params->verified_status;
        if ($dbOrder->order_status === $order_status && $response['type'] !== self::RESPONSE_TYPE_REVERSE) {
            // If the order is paid.
            return true;
        }

        // Update order status.
        if ($response['transactionStatus'] ===self::TRANSACTION_STATUS_APPROVED ) {
            // Ordinary payment.
            if ($response['type'] === self::RESPONSE_TYPE_PAYMENT) {
                $history->reason = JText::_('CONCORDPAY_PAYMENT_ORDER_CONFIRMED');
                $history->notified = 1;
                $history->data = JText::_('CONCORDPAY_ORDER_APPROVED') . ' ' . $response["transactionId"];

                $this->modifyOrder($order_id, $order_status, $history);

                return true;
            }

            // Refunded payment.
            if ($response['type'] === self::RESPONSE_TYPE_REVERSE) {
                $history->reason = JText::_('CONCORDPAY_PAYMENT_ORDER_REFUNDED');
                $history->notified = 1;
                $history->data = JText::_('CONCORDPAY_PAYMENT_REFUNDED') . ' ' . $response["transactionId"];

                $this->modifyOrder($order_id, $this->payment_params->refunded_status, $history);

                return true;
            }
        }

        if ($response['transactionStatus'] ===self::TRANSACTION_STATUS_DECLINED ) {
            $history->reason = JText::_('CONCORDPAY_PAYMENT_ORDER_DECLINED');
            $history->notified = 1;
            $history->data = "Payment declined. ConcordPay transaction ID: " . $response["transactionId"];

            $this->modifyOrder($order_id, $this->payment_params->invalid_status, $history);
        }

        return false;
    }

    /**
     * Payment settings default values.
     *
     * @param $element stdClass
     *
     * @since 3.8.0
     *
     * @return void
     */
    function getPaymentDefaultValues(&$element)
    {
        $element->payment_name                     = 'ConcordPay';
        $element->payment_description              = JText::_('CONCORDPAY_DESCRIPTION');
        $element->payment_images                   = 'cp-visa,cp-mastercard,cp-googlepay,cp-applepay';
        $element->payment_params->notification     = 1;
        $element->payment_params->iframe           = 0;
        $element->payment_params->verified_status  = 'confirmed';
        $element->payment_params->invalid_status   = 'cancelled';
        $element->payment_params->cancelled_status = 'cancelled';
        $element->payment_params->refunded_status  = 'refunded';
        $element->payment_params->language         = 'uk';
    }

    /**
     * Service method for get signature.
     *
     * @param $option array
     * @param $keys   array
     *
     * @return false|string
     *
     * @since version 3.8.0
     */
    protected function getSignature($option, $keys)
    {
        $hash = array();
        foreach ($keys as $data_key) {
            if (!isset($option[$data_key])) {
                $option[$data_key] = '';
            }
            if (is_array($option[$data_key])) {
                foreach ($option[$data_key] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $option[$data_key];
            }
        }

        $hash = implode(self::SIGNATURE_SEPARATOR, $hash);

        return hash_hmac('md5', $hash, $this->payment_params->secret_key);
    }

    /**
     * Generate request signature.
     *
     * @param $options array
     *
     * @return false|string
     *
     * @since version 3.8.0
     */
    public function getRequestSignature($options)
    {
        return $this->getSignature($options, $this->keysForRequestSignature);
    }

    /**
     * Generate response signature.
     *
     * @param $options array
     *
     * @return false|string
     *
     * @since version 3.8.0
     */
    public function getResponseSignature($options)
    {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     *
     * @since version 4.0
     */
    public static function getSubscribedEvents(): array
    {
        return array();
    }
}
