<?php

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\CreditCard;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
use PayPal\Api\Refund;
use PayPal\Api\Sale;

class Premiumdev_Woo_PayPal_Credit_Card_Rest_API_Utility {

    protected $card;
    protected $FundingInstrument;
    protected $Payer;
    protected $order_item;
    protected $item;
    protected $item_list;
    protected $details;
    protected $payment_data;
    protected $amount;
    protected $transaction;
    protected $payment;
    protected $payment_method;
    protected $gateway;

    public function __construct() {

        $this->premiumdev_woo_paypal_credit_card_rest_api_lib();
        $this->premiumdev_woo_paypal_credit_card_rest_transaction_obj();
        $this->payment_method = (isset($_POST['payment_method'])) ? $_POST['payment_method'] : 'paypal_credit_card_rest';
        if ($this->payment_method == 'paypal_credit_card_rest') {
            $this->gateway = new Premiumdev_Woo_PayPal_Credit_Card_Rest_Gateway();
        }
        $this->testmode = $this->gateway->get_option('premium_testmode') === "yes" ? 'SANDBOX' : 'LIVE';
        $this->debug = $this->gateway->get_option('premium_debug_log') === "yes" ? true : false;
        if ($this->testmode == "SANDBOX") {
            $this->woo_paypal_client_id = ($this->gateway->get_option('premium_sandbox_client_id')) ? trim($this->gateway->get_option('premium_sandbox_client_id')) : '';
            $this->woo_paypal_secret_id = ($this->gateway->get_option('premium_sandbox_secret_id')) ? trim($this->gateway->get_option('premium_sandbox_secret_id')) : '';
        } else {
            $this->woo_paypal_client_id = ($this->gateway->get_option('premium_live_client_id')) ? trim($this->gateway->get_option('premium_live_client_id')) : '';
            $this->woo_paypal_secret_id = ($this->gateway->get_option('premium_live_secret_id')) ? trim($this->gateway->get_option('premium_live_secret_id')) : '';
        }
    }

    public function premiumdev_woo_paypal_credit_card_rest_create_payment($order, $card_data) {
        global $woocommerce;
        try {
            $this->premiumdev_woo_paypal_credit_card_rest_set_trnsaction_value($order, $card_data);
            $this->payment->create($this->premiumdev_woo_paypal_credit_card_rest_getAuth());            
            if ($this->payment->state == "approved") {
                $this->gateway->premiumdev_woo_paypal_credit_card_rest_log(__('Approved Payment state.', 'woo-paypal-credit-card-rest'));
                $transactions = $this->payment->getTransactions();
                $relatedResources = $transactions[0]->getRelatedResources();
                $sale = $relatedResources[0]->getSale();
                $saleId = $sale->getId();
                $order->add_order_note(__('PayPal Credit Card Rest payment completed', 'woo-paypal-credit-card-rest'));
                $order->payment_complete($saleId);
                WC()->cart->empty_cart();
                $return_url = $order->get_checkout_order_received_url();
                if (is_ajax()) {
                    wp_send_json(array(
                        'result' => 'success',
                        'redirect' => apply_filters('woocommerce_checkout_no_payment_needed_redirect', $return_url, $order)
                    ));
                } else {
                    wp_safe_redirect(
                            apply_filters('woocommerce_checkout_no_payment_needed_redirect', $return_url, $order)
                    );
                    exit;
                }
            } else {
                wc_add_notice(__('Error Payment state:' . $this->payment->state, 'woo-paypal-credit-card-rest'), 'error');
                $this->gateway->premiumdev_woo_paypal_credit_card_rest_log(__('Error Payment state:' . $this->payment->state, 'woo-paypal-credit-card-rest'));
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'woo-paypal-credit-card-rest'), 'error');
            $this->gateway->premiumdev_woo_paypal_credit_card_rest_log($ex->getData());
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
            exit;
        } catch (Exception $ex) {
            wc_add_notice(__("Error processing checkout. Please try again. ", 'woo-paypal-credit-card-rest'), 'error');
            $this->gateway->premiumdev_woo_paypal_credit_card_rest_log($ex->getMessage());
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
    }    

    public function premiumdev_woo_paypal_credit_card_rest_transaction_obj() {
        $this->card = new CreditCard();
        $this->order_item = array();
        $this->send_items = true;
    }

    public function premiumdev_woo_paypal_credit_card_rest_payment_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $this->gateway->premiumdev_woo_paypal_credit_card_rest_log('Begin Refund');
        $this->gateway->premiumdev_woo_paypal_credit_card_rest_log('Order: ' . print_r($order, true));
        $this->gateway->premiumdev_woo_paypal_credit_card_rest_log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        if (!$order || !$order->get_transaction_id() || !$this->woo_paypal_client_id || !$this->woo_paypal_secret_id) {
            return false;
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }

            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $sale = Sale::get($order->get_transaction_id(), $this->premiumdev_woo_paypal_credit_card_rest_getAuth());
        $this->amount = new Amount();
        $this->amount->setCurrency(get_woocommerce_currency());
        $this->amount->setTotal(premiumdev_woo_paypal_credit_card_rest_number_format($amount, $order));
        $refund = new Refund();
        $refund->setAmount($this->amount);
        try {
            $this->gateway->premiumdev_woo_paypal_credit_card_rest_log('Refund Request: ' . print_r($refund, true));
            $refundedSale = $sale->refund($refund, $this->premiumdev_woo_paypal_credit_card_rest_getAuth());
            if ($refundedSale->state == 'completed') {
                $order->add_order_note('Refund Transaction ID:' . $refundedSale->getId());
                if (isset($reason) && !empty($reason)) {
                    $order->add_order_note('Reason for Refund :' . $reason);
                }
                $max_remaining_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
                if (!$max_remaining_refund > 0) {
                    $order->update_status('refunded');
                }
                return true;
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            $this->gateway->premiumdev_woo_paypal_credit_card_rest_log($ex->getData());
            $error_data = json_decode($ex->getData());
            if (is_object($error_data) && !empty($error_data)) {
                $error_message = ($error_data->message) ? $error_data->message : $error_data->information_link;
                return new WP_Error('paypal_credit_card_rest_refund-error', $error_message);
            } else {
                return new WP_Error('paypal_credit_card_rest_refund-error', $ex->getData());
            }
        } catch (Exception $ex) {
            $this->gateway->premiumdev_woo_paypal_credit_card_rest_log($ex->getMessage());
            return new WP_Error('paypal_credit_card_rest_refund-error', $ex->getMessage());
        }
    }
    
    public function premiumdev_woo_paypal_credit_card_rest_set_card_details($order, $card_data) {
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_type($card_data);
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_number($card_data);
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_expire_month($card_data);
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_expire_year($card_data);
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_cvv($card_data);
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_first_name($order);
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_set_last_name($order);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_type($card_data) {
        $first_four = substr($card_data->number, 0, 4);
        $card_type = premiumdev_woo_paypal_credit_card_rest_card_type_from_account_number($first_four);
        $this->card->setType($card_type);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_number($card_data) {
        $this->card->setNumber($card_data->number);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_expire_month($card_data) {
        $this->card->setExpireMonth($card_data->exp_month);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_expire_year($card_data) {
        $this->card->setExpireYear($card_data->exp_year);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_cvv($card_data) {
        $this->card->setCvv2($card_data->cvc);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_first_name($order) {
        $this->card->setFirstName($order->billing_first_name);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_card_set_last_name($order) {
        $this->card->setLastName($order->billing_last_name);
    }
    
    public function premiumdev_woo_paypal_credit_card_rest_set_trnsaction_value($order, $card_data) {
        $this->premiumdev_woo_paypal_credit_card_rest_set_card_details($order, $card_data);
        $this->fundingInstrument = new FundingInstrument();
        $this->fundingInstrument->setCreditCard($this->card);
        $this->payer = new Payer();
        $this->payer->setPaymentMethod("credit_card");
        $this->payer->setFundingInstruments(array($this->fundingInstrument));
        $this->premiumdev_woo_paypal_credit_card_rest_set_item($order);
        $this->premiumdev_woo_paypal_credit_card_rest_set_item_list();
        $this->premiumdev_woo_paypal_credit_card_rest_set_detail_values();
        $this->premiumdev_woo_paypal_credit_card_rest_set_amount_values($order);
        $this->premiumdev_woo_paypal_credit_card_rest_set_transaction();
        $this->premiumdev_woo_paypal_credit_card_rest_set_payment();
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_item($order) {
        $this->payment_data = $this->gateway->premiumdev_woo_paypal_credit_card_rest_calculate($order, $this->send_items);
        foreach ($this->payment_data['order_items'] as $item) {
            $this->item = new Item();
            $this->item->setName($item['name']);
            $this->item->setCurrency(get_woocommerce_currency());
            $this->item->setQuantity($item['qty']);
            $this->item->setPrice($item['amt']);
            array_push($this->order_item, $this->item);
        }
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_item_list() {
        $this->item_list = new ItemList();
        $this->item_list->setItems($this->order_item);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_detail_values() {
        $this->details = new Details();
        if (isset($this->payment_data['shippingamt'])) {
            $this->details->setShipping($this->payment_data['shippingamt']);
        }
        if (isset($this->payment_data['taxamt'])) {
            $this->details->setTax($this->payment_data['taxamt']);
        }
        if ($this->payment_data['itemamt']) {
            $this->details->setSubtotal($this->payment_data['itemamt']);
        }
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_amount_values($order) {
        $this->amount = new Amount();
        $this->amount->setCurrency(get_woocommerce_currency());
        $this->amount->setTotal(premiumdev_woo_paypal_credit_card_rest_number_format($order->get_total(), $order));
        $this->amount->setDetails($this->details);
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_transaction() {
        $this->transaction = new Transaction();
        $this->transaction->setAmount($this->amount);
        $this->transaction->setItemList($this->item_list);
        $this->transaction->setDescription("Payment description");
        $this->transaction->setInvoiceNumber(uniqid());
    }

    public function premiumdev_woo_paypal_credit_card_rest_set_payment() {
        $this->payment = new Payment();
        $this->payment->setIntent("sale");
        $this->payment->setPayer($this->payer);
        $this->payment->setTransactions(array($this->transaction));
    }
    
    public function premiumdev_woo_paypal_credit_card_rest_getAuth() {
        $auth = new ApiContext(new OAuthTokenCredential($this->woo_paypal_client_id, $this->woo_paypal_secret_id));
        $auth->setConfig(array('mode' => $this->testmode, 'http.headers.PayPal-Partner-Attribution-Id' => 'mbjtechnolabs_SP'));
        return $auth;
    }

    public function premiumdev_woo_paypal_credit_card_rest_api_lib() {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        require_once( PREMIUMDEV_WOO_PAYPAL_CREDIT_CARD_REST_PLUGIN_DIR . '/includes/lib/autoload.php' );
    }
}