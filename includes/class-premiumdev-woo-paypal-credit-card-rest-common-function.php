<?php

function premiumdev_woo_paypal_credit_card_rest_setting_field() {
    return array(
        'premium_enabled' => array(
            'title' => __('Enable/Disable', 'woo-paypal-credit-card-rest'),
            'label' => __('Enable Woo PayPal Credit Card Rest', 'woo-paypal-credit-card-rest'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no'
        ),
        'premium_title' => array(
            'title' => __('Title', 'woo-paypal-credit-card-rest'),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'default' => __('PayPal Credit Card Rest', 'woo-paypal-credit-card-rest')
        ),
        'premium_description' => array(
            'title' => __('Description', 'woo-paypal-credit-card-rest'),
            'type' => 'textarea',
            'description' => __('This controls the description which the user sees during checkout.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'default' => __("Pay with your credit card Rest via PayPal Website Payments credit card rest.", 'woo-paypal-credit-card-rest')
        ),
        'premium_testmode' => array(
            'title' => __('Test Mode', 'woo-paypal-credit-card-rest'),
            'type' => 'checkbox',
            'default' => 'yes',
            'description' => __('Place the payment gateway in development mode.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'label' => __('Enable PayPal Sandbox/Test Mode', 'woo-paypal-credit-card-rest')
        ),
        'premium_sandbox_client_id' => array(
            'title' => __('Sandbox Client ID', 'woo-paypal-credit-card-rest'),
            'type' => 'password',
            'description' => __('Enter your Sandbox PayPal Rest API Client ID.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'label' => __('Create Sandbox PayPal Rest API Client ID from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'woo-paypal-credit-card-rest'),
            'default' => ''
        ),
        'premium_sandbox_secret_id' => array(
            'title' => __('Sandbox Secret ID', 'woo-paypal-credit-card-rest'),
            'type' => 'password',
            'description' => __('Enter your Sandbox PayPal Rest API Secret ID.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'label' => __('Create Sandbox PayPal Rest API Secret ID from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'woo-paypal-credit-card-rest'),
            'default' => ''
        ),
        'premium_live_client_id' => array(
            'title' => __('Sandbox Client ID', 'woo-paypal-credit-card-rest'),
            'type' => 'password',
            'description' => __('Enter your Sandbox PayPal Rest API Client ID.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'label' => __('Create Sandbox PayPal Rest API Client ID from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'woo-paypal-credit-card-rest'),
            'default' => ''
        ),
        'premium_live_secret_id' => array(
            'title' => __('Sandbox Secret ID', 'woo-paypal-credit-card-rest'),
            'type' => 'password',
            'description' => __('Enter your Sandbox PayPal Rest API Secret ID.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'label' => __('Create Sandbox PayPal Rest API Secret ID from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'woo-paypal-credit-card-rest'),
            'default' => ''
        ),
        'premium_invoice_prefix' => array(
            'title' => __('Invoice Prefix', 'woo-paypal-credit-card-rest'),
            'type' => 'text',
            'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'default' => ''
        ),
        'premium_debug_log' => array(
            'title' => __('Debug Log', 'woo-paypal-credit-card-rest'),
            'type' => 'checkbox',
            'description' => __('Enable Log Pal Pro', 'woo-paypal-credit-card-rest'),
            'desc_tip' => true,
            'default' => 'no'
        )
    );
}

function premiumdev_woo_paypal_credit_card_rest_is_card_details($posted) {
    $card_number = isset($posted['paypal_credit_card_rest-card-number']) ? wc_clean($posted['paypal_credit_card_rest-card-number']) : '';
    $card_cvc = isset($posted['paypal_credit_card_rest-card-cvc']) ? wc_clean($posted['paypal_credit_card_rest-card-cvc']) : '';
    $card_expiry = isset($posted['paypal_credit_card_rest-card-expiry']) ? wc_clean($posted['paypal_credit_card_rest-card-expiry']) : '';

    // Format values
    $card_number = str_replace(array(' ', '-'), '', $card_number);
    $card_expiry = array_map('trim', explode('/', $card_expiry));
    $card_exp_month = str_pad($card_expiry[0], 2, "0", STR_PAD_LEFT);
    $card_exp_year = isset($card_expiry[1]) ? $card_expiry[1] : '';

    if (isset($_POST['paypal_credit_card_rest-card-start'])) {
        $card_start = wc_clean($_POST['paypal_credit_card_rest-card-start']);
        $card_start = array_map('trim', explode('/', $card_start));
        $card_start_month = str_pad($card_start[0], 2, "0", STR_PAD_LEFT);
        $card_start_year = $card_start[1];
    } else {
        $card_start_month = '';
        $card_start_year = '';
    }

    if (strlen($card_exp_year) == 2) {
        $card_exp_year += 2000;
    }

    if (strlen($card_start_year) == 2) {
        $card_start_year += 2000;
    }

    return (object) array(
                'number' => $card_number,
                'type' => '',
                'cvc' => $card_cvc,
                'exp_month' => $card_exp_month,
                'exp_year' => $card_exp_year,
                'start_month' => $card_start_month,
                'start_year' => $card_start_year
    );
}

function premiumdev_woo_paypal_credit_card_rest_get_diffrent($amout_1, $amount_2) {
    $diff_amount = $amout_1 - $amount_2;
    return $diff_amount;
}

function premiumdev_woo_paypal_credit_card_rest_is_wc_version_greater_2_3() {
    return premiumdev_woo_paypal_credit_card_rest_get_wc_version() && version_compare(premiumdev_woo_paypal_credit_card_rest_get_wc_version(), '2.3', '>=');
}

function premiumdev_woo_paypal_credit_card_rest_get_wc_version() {
    return defined('WC_VERSION') && WC_VERSION ? WC_VERSION : null;
}

function premiumdev_woo_paypal_credit_card_rest_round($price) {
    $precision = 2;
    if (!premiumdev_woo_paypal_credit_card_rest_currency_has_decimals(get_woocommerce_currency())) {
        $precision = 0;
    }
    return round($price, $precision);
}

function premiumdev_woo_paypal_credit_card_rest_currency_has_decimals($currency) {
    if (in_array($currency, array('HUF', 'JPY', 'TWD'))) {
        return false;
    }
    return true;
}

function premiumdev_woo_paypal_credit_card_rest_number_format($price) {
    $decimals = 2;
    if (!premiumdev_woo_paypal_credit_card_rest_currency_has_decimals(get_woocommerce_currency())) {
        $decimals = 0;
    }
    return number_format($price, $decimals, '.', '');
}

function premiumdev_woo_paypal_credit_card_rest_card_type_from_account_number($account_number) {
    $types = array(
        'visa' => '/^4/',
        'mc' => '/^5[1-5]/',
        'amex' => '/^3[47]/',
        'discover' => '/^(6011|65|64[4-9]|622)/',
        'diners' => '/^(36|38|30[0-5])/',
        'jcb' => '/^35/',
        'maestro' => '/^(5018|5020|5038|6304|6759|676[1-3])/',
        'laser' => '/^(6706|6771|6709)/',
    );
    foreach ($types as $type => $pattern) {
        if (1 === preg_match($pattern, $account_number)) {
            return $type;
        }
    }
    return null;
}
