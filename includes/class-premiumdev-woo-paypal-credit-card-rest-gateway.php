<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_PayPal_Credit_Card_Rest
 * @subpackage Woo_PayPal_Credit_Card_Rest/includes
 * @author     wpremiumdev <wpremiumdev@gmail.com>
 */
class Premiumdev_Woo_PayPal_Credit_Card_Rest_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        try {

            $this->id = 'paypal_credit_card_rest';
            $this->icon = apply_filters('woocommerce_paypal_credit_card_rest_icon', plugins_url('/public/images/cards.png', plugin_basename(dirname(__FILE__))));
            $this->method_title = __('PayPal Credit Card Rest', 'woo-paypal-credit-card-rest');
            $this->supported_currencies = array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'TRY', 'USD');
            $this->method_description = __('PayPal direct credit card payments using the REST API.', 'woo-paypal-credit-card-rest');
            $this->has_fields = true;
            $this->init_form_fields();
            $this->init_settings();
            // Get setting values 
            $this->enabled = $this->get_option('premium_enabled') === "yes" ? true : false;
            $this->title = $this->get_option('premium_title');
            $this->description = $this->get_option('premium_description');
            $this->testmode = $this->get_option('premium_testmode') === "yes" ? 'SANDBOX' : 'LIVE';
            $this->invoice_prefix = $this->get_option('premium_invoice_prefix');
            $this->debug = $this->get_option('premium_debug_log') === "yes" ? true : false;
            $this->log = "";
            $this->woo_paypal_credit_card_rest_api = "";
            if ($this->testmode == 'SANDBOX') {
                $this->woo_paypal_client_id = ($this->get_option('premium_sandbox_client_id')) ? trim($this->get_option('premium_sandbox_client_id')) : '';
                $this->woo_paypal_secret_id = ($this->get_option('premium_sandbox_secret_id')) ? trim($this->get_option('premium_sandbox_secret_id')) : '';
            } else {
                $this->woo_paypal_client_id = ($this->get_option('premium_live_client_id')) ? trim($this->get_option('premium_live_client_id')) : '';
                $this->woo_paypal_secret_id = ($this->get_option('premium_live_secret_id')) ? trim($this->get_option('premium_live_secret_id')) : '';
            }
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('admin_notices', array($this, 'premiumdev_woo_paypal_credit_card_rest_checks_field')); 
        } catch (Exception $ex) {
            wc_add_notice('<strong>' . __('Payment error', 'woo-paypal-credit-card-rest') . '</strong>: ' . $ex->getMessage(), 'error');
            return;
        }
    }

    public function init_form_fields() {
        return $this->form_fields = premiumdev_woo_paypal_credit_card_rest_setting_field();
    }

    public function admin_options() {
        if ($this->premiumdev_woo_paypal_credit_card_rest_is_valid_currency()) {
            ?>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <script type="text/javascript">
                jQuery('#woocommerce_paypal_credit_card_rest_premium_testmode').change(function () {
                    var sandbox = jQuery('#woocommerce_paypal_credit_card_rest_premium_sandbox_client_id, #woocommerce_paypal_credit_card_rest_premium_sandbox_secret_id').closest('tr'),
                            production = jQuery('#woocommerce_paypal_credit_card_rest_premium_live_client_id, #woocommerce_paypal_credit_card_rest_premium_live_secret_id').closest('tr');
                    if (jQuery(this).is(':checked')) {
                        sandbox.show();
                        production.hide();
                    } else {
                        sandbox.hide();
                        production.show();
                    }
                }).change();

            </script><?php
        } else {
            ?><div class="inline error"><p><strong><?php _e('Gateway Disabled', 'woo-paypal-credit-card-rest'); ?></strong>: <?php _e('PayPal does not support your store currency.', 'woo-paypal-credit-card-rest'); ?></p></div> <?php
        }
    }

    public function is_available() {
        if ($this->enabled) {
            if (!$this->woo_paypal_client_id || !$this->woo_paypal_secret_id) {
                return false;
            }
            if (!in_array(get_woocommerce_currency(), apply_filters('paypal_rest_api_supported_currencies', $this->supported_currencies))) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        if (class_exists('WC_Payment_Gateway_CC')) {
            $cc_form = new WC_Payment_Gateway_CC;
            $cc_form->id = $this->id;
            $cc_form->supports = $this->supports;
            $cc_form->form();
        } else {
            $this->credit_card_form();
        }
    }

    public function validate_fields() {
        try {
            $card = premiumdev_woo_paypal_credit_card_rest_is_card_details($_POST);
            
            if (empty($card->exp_month) || empty($card->exp_year)) {
                throw new Exception(__('Card expiration date is invalid', 'woo-paypal-credit-card-rest'));
            }
            
            if ( date("Y") > $card->exp_year) {
                throw new Exception(__('Card expiration year is past', 'woo-paypal-credit-card-rest'));
            }

            if (!ctype_digit($card->cvc)) {
                throw new Exception(__('Card security code is invalid (only digits are allowed)', 'woo-paypal-credit-card-rest'));
            }

            if (
                    !ctype_digit($card->exp_month) ||
                    !ctype_digit($card->exp_year) ||
                    $card->exp_month > 12 ||
                    $card->exp_month < 1 ||
                    $card->exp_year < date('y')
            ) {
                throw new Exception(__('Card expiration date is invalid', 'woo-paypal-credit-card-rest'));
            }

            if (empty($card->number) || !ctype_digit($card->number)) {
                throw new Exception(__('Card number is invalid', 'woo-paypal-credit-card-rest'));
            }
            return true;
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
    }

    public function process_payment($order_id) {
        $this->premiumdev_woo_paypal_credit_card_rest_utility();
        $order = wc_get_order($order_id);
        $card = premiumdev_woo_paypal_credit_card_rest_is_card_details($_POST);
        return $this->premiumdev_woo_paypal_credit_card_rest_do_payment($order, $card);
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $this->premiumdev_woo_paypal_credit_card_rest_utility();
        $return = $this->woo_paypal_credit_card_rest_api->premiumdev_woo_paypal_credit_card_rest_payment_refund($order_id, $amount, $reason);
        if ($return) {
            return $return;
        }
    }

    public function premiumdev_woo_paypal_credit_card_rest_do_payment($order, $card) {
        $this->premiumdev_woo_paypal_credit_card_rest_utility();
        $this->woo_paypal_credit_card_rest_api->premiumdev_woo_paypal_credit_card_rest_create_payment($order, $card);
    }

    public function premiumdev_woo_paypal_credit_card_rest_checks_field() {

        if ($this->enabled == false) {
            return;
        }

        $this->woo_paypal_credit_card_rest_api = $this->premiumdev_woo_paypal_credit_card_rest_utility();

        if (!$this->woo_paypal_client_id) {
            echo '<div class="inline error"><p>' . sprintf(__('Paypal Credit Card Rest error: Please enter your Paypal Credit Card Rest Account Client ID.', 'woo-paypal-credit-card-rest')) . '</p></div>';
        }
        if (!$this->woo_paypal_secret_id) {
            echo '<div class="inline error"><p>' . sprintf(__('Paypal Credit Card Rest error: Please enter your Paypal Credit Card Rest Account Secret ID.', 'woo-paypal-credit-card-rest')) . '</p></div>';
        }
        if (version_compare(phpversion(), '5.2.1', '<')) {
            echo '<div class="error"><p>' . sprintf(__('PayPal Credit Card Rest error:  PayPal Credit Card Rest requires PHP 5.2.1 and above. You are using version %s.', 'woo-paypal-credit-card-rest'), phpversion()) . '</p></div>';
        }
    }

    public function premiumdev_woo_paypal_credit_card_rest_is_valid_currency() {
        return in_array(get_woocommerce_currency(), apply_filters('woocommerce_woo_paypal_credit_card_rest_supported_currencies', array('USD', 'CAD')));
    }

    public function premiumdev_woo_paypal_credit_card_rest_utility() {

        if (empty($this->woo_paypal_credit_card_rest_api)) {
            if (class_exists('Premiumdev_Woo_PayPal_Credit_Card_Rest_API_Utility')) {
                $this->woo_paypal_credit_card_rest_api = new Premiumdev_Woo_PayPal_Credit_Card_Rest_API_Utility();
            } else {
                include_once ( PREMIUMDEV_WOO_PAYPAL_CREDIT_CARD_REST_PLUGIN_DIR . '/includes/class-premiumdev-woo-paypal-credit-card-rest-utility.php' );
                $this->woo_paypal_credit_card_rest_api = new Premiumdev_Woo_PayPal_Credit_Card_Rest_API_Utility();
            }
        }
    }

    public function premiumdev_woo_paypal_credit_card_rest_calculate($order, $send_items = false) {

        $PaymentOrderItems = array();
        $ctr = $giftwrapamount = $total_items = $total_discount = $total_tax = $shipping = 0;
        $ITEMAMT = 0;
        if ($order) {
            $order_total = $order->get_total();
            $items = $order->get_items();
            /*
             * Set shipping and tax values.
             */
            if (get_option('woocommerce_prices_include_tax') == 'yes') {
                $shipping = $order->get_total_shipping() + $order->get_shipping_tax();
                $tax = 0;
            } else {
                $shipping = $order->get_total_shipping();
                $tax = $order->get_total_tax();
            }

            if ('yes' === get_option('woocommerce_calc_taxes') && 'yes' === get_option('woocommerce_prices_include_tax')) {
                $tax = $order->get_total_tax();
            }
        } else {
            //if empty order we get data from cart
            $order_total = WC()->cart->total;
            $items = WC()->cart->get_cart();
            /**
             * Get shipping and tax.
             */
            if (get_option('woocommerce_prices_include_tax') == 'yes') {
                $shipping = WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
                $tax = 0;
            } else {
                $shipping = WC()->cart->shipping_total;
                $tax = WC()->cart->get_taxes_total();
            }

            if ('yes' === get_option('woocommerce_calc_taxes') && 'yes' === get_option('woocommerce_prices_include_tax')) {
                $tax = WC()->cart->get_taxes_total();
            }
        }

        if ($send_items) {
            foreach ($items as $item) {
                /*
                 * Get product data from WooCommerce
                 */
                if ($order) {
                    $_product = $order->get_product_from_item($item);
                    $qty = absint($item['qty']);
                    $item_meta = new WC_Order_Item_Meta($item, $_product);
                    $meta = $item_meta->display(true, true);
                } else {
                    $_product = $item['data'];
                    $qty = absint($item['quantity']);
                    $meta = WC()->cart->get_item_data($item, true);
                }

                $sku = $_product->get_sku();
                $item['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');
                if ($_product->product_type == 'variation') {
                    if (empty($sku)) {
                        $sku = $_product->parent->get_sku();
                    }

                    if (!empty($meta)) {
                        $item['name'] .= " - " . str_replace(", \n", " - ", $meta);
                    }
                }

                $Item = array(
                    'name' => $item['name'], // Item name. 127 char max.
                    'desc' => '', // Item description. 127 char max.
                    'amt' => premiumdev_woo_paypal_credit_card_rest_round($item['line_subtotal'] / $qty), // Cost of item.
                    'number' => $sku, // Item number.  127 char max.
                    'qty' => $qty, // Item qty on order.  Any positive integer.
                );
                array_push($PaymentOrderItems, $Item);
                $ITEMAMT += premiumdev_woo_paypal_credit_card_rest_round($item['line_subtotal'] / $qty) * $qty;
            }

            /**
             * Add custom Woo cart fees as line items
             */
            foreach (WC()->cart->get_fees() as $fee) {
                $Item = array(
                    'name' => $fee->name, // Item name. 127 char max.
                    'desc' => '', // Item description. 127 char max.
                    'amt' => premiumdev_woo_paypal_credit_card_rest_number_format($fee->amount, 2, '.', ''), // Cost of item.
                    'number' => $fee->id, // Item number. 127 char max.
                    'qty' => 1, // Item qty on order. Any positive integer.
                );

                /**
                 * The gift wrap amount actually has its own parameter in
                 * DECP, so we don't want to include it as one of the line
                 * items.
                 */
                if ($Item['number'] != 'gift-wrap') {
                    array_push($PaymentOrderItems, $Item);
                    $ITEMAMT += premiumdev_woo_paypal_credit_card_rest_round($fee->amount);
                } else {
                    $giftwrapamount = premiumdev_woo_paypal_credit_card_rest_round($fee->amount);
                }

                $ctr++;
            }

            //caculate discount
            if ($order) {
                if (!premiumdev_woo_paypal_credit_card_rest_is_wc_version_greater_2_3()) {
                    if ($order->get_cart_discount() > 0) {
                        foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                            $Item = array(
                                'name' => 'Cart Discount',
                                'number' => $code,
                                'qty' => '1',
                                'amt' => '-' . premiumdev_woo_paypal_credit_card_rest_number_format(WC()->cart->coupon_discount_amounts[$code])
                            );
                            array_push($PaymentOrderItems, $Item);
                        }
                        $total_discount -= $order->get_cart_discount();
                    }

                    if ($order->get_order_discount() > 0) {
                        foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                            $Item = array(
                                'name' => 'Order Discount',
                                'number' => $code,
                                'qty' => '1',
                                'amt' => '-' . premiumdev_woo_paypal_credit_card_rest_number_format(WC()->cart->coupon_discount_amounts[$code])
                            );
                            array_push($PaymentOrderItems, $Item);
                        }
                        $total_discount -= $order->get_order_discount();
                    }
                } else {
                    if ($order->get_total_discount() > 0) {
                        $Item = array(
                            'name' => 'Total Discount',
                            'qty' => 1,
                            'amt' => - premiumdev_woo_paypal_credit_card_rest_number_format($order->get_total_discount()),
                            'number' => implode(", ", $order->get_used_coupons())
                        );
                        array_push($PaymentOrderItems, $Item);
                        $total_discount -= $order->get_total_discount();
                    }
                }
            } else {
                if (WC()->cart->get_cart_discount_total() > 0) {
                    foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                        $Item = array(
                            'name' => 'Cart Discount',
                            'qty' => '1',
                            'number' => $code,
                            'amt' => '-' . premiumdev_woo_paypal_credit_card_rest_number_format(WC()->cart->coupon_discount_amounts[$code])
                        );
                        array_push($PaymentOrderItems, $Item);
                        $total_discount -= premiumdev_woo_paypal_credit_card_rest_number_format(WC()->cart->coupon_discount_amounts[$code]);
                    }
                }

                if (premiumdev_woo_paypal_credit_card_rest_is_wc_version_greater_2_3()) {
                    if (WC()->cart->get_order_discount_total() > 0) {
                        foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                            $Item = array(
                                'name' => 'Order Discount',
                                'qty' => '1',
                                'number' => $code,
                                'amt' => '-' . premiumdev_woo_paypal_credit_card_rest_number_format(WC()->cart->coupon_discount_amounts[$code])
                            );
                            array_push($PaymentOrderItems, $Item);
                            $total_discount -= premiumdev_woo_paypal_credit_card_rest_number_format(WC()->cart->coupon_discount_amounts[$code]);
                        }
                    }
                }
            }
        }



        if ($tax > 0) {
            $tax = premiumdev_woo_paypal_credit_card_rest_number_format($tax);
        }

        if ($shipping > 0) {
            $shipping = premiumdev_woo_paypal_credit_card_rest_number_format($shipping);
        }

        if ($total_discount) {
            $total_discount = premiumdev_woo_paypal_credit_card_rest_round($total_discount);
        }

        if (empty($ITEMAMT)) {
            $cart_fees = WC()->cart->get_fees();
            if (isset($cart_fees[0]->id) && $cart_fees[0]->id == 'gift-wrap') {
                $giftwrapamount = isset($cart_fees[0]->amount) ? $cart_fees[0]->amount : 0;
            } else {
                $giftwrapamount = 0;
            }
            $Payment['itemamt'] = $order_total - $tax - $shipping - $giftwrapamount;
        } else {
            $Payment['itemamt'] = premiumdev_woo_paypal_credit_card_rest_number_format($ITEMAMT + $total_discount);
        }


        /*
         * Set tax
         */
        if ($tax > 0) {
            $Payment['taxamt'] = premiumdev_woo_paypal_credit_card_rest_number_format($tax);       // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
        } else {
            $Payment['taxamt'] = 0;
        }

        /*
         * Set shipping
         */
        if ($shipping > 0) {
            $Payment['shippingamt'] = premiumdev_woo_paypal_credit_card_rest_number_format($shipping);      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
        } else {
            $Payment['shippingamt'] = 0;
        }

        $Payment['order_items'] = $PaymentOrderItems;

        // Rounding amendment
        if (trim(premiumdev_woo_paypal_credit_card_rest_number_format($order_total)) !== trim(premiumdev_woo_paypal_credit_card_rest_number_format($Payment['itemamt'] + $giftwrapamount + $tax + $shipping))) {
            $diffrence_amount = premiumdev_woo_paypal_credit_card_rest_get_diffrent($order_total, $Payment['itemamt'] + $tax + $shipping);
            if ($shipping > 0) {
                $Payment['shippingamt'] = premiumdev_woo_paypal_credit_card_rest_number_format($shipping + $diffrence_amount);
            } elseif ($tax > 0) {
                $Payment['taxamt'] = premiumdev_woo_paypal_credit_card_rest_number_format($tax + $diffrence_amount);
            } else {
                //make change to itemamt
                $Payment['itemamt'] = premiumdev_woo_paypal_credit_card_rest_number_format($Payment['itemamt'] + $diffrence_amount);
                //also make change to the first item
                if ($send_items) {
                    $Payment['order_items'][0]['amt'] = premiumdev_woo_paypal_credit_card_rest_number_format($Payment['order_items'][0]['amt'] + $diffrence_amount / $Payment['order_items'][0]['qty']);
                }
            }
        }

        return $Payment;
    }

    public function premiumdev_woo_paypal_credit_card_rest_log($message) {
        if ($this->debug) {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('woo_paypal_credit_card_rest', $message);
        }
    }
}