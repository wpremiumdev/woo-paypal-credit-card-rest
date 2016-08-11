<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woo_PayPal_Credit_Card_Rest
 * @subpackage Woo_PayPal_Credit_Card_Rest/includes
 * @author     wpremiumdev <wpremiumdev@gmail.com>
 */
class Woo_PayPal_Credit_Card_Rest_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
                'woo-paypal-credit-card-rest', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

}
