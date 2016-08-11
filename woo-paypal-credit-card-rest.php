<?php

/**
 * @link              https://www.premiumdev.com/
 * @since             1.0.0
 * @package           Woo_PayPal_Credit_Card_Rest
 *
 * @wordpress-plugin
 * Plugin Name:       PayPal REST Credit Card for WooCommerce
 * Plugin URI:        https://www.premiumdev.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.1
 * Author:            wpremiumdev
 * Author URI:        https://www.premiumdev.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-paypal-credit-card-rest
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!defined('PREMIUMDEV_WOO_PAYPAL_CREDIT_CARD_REST_PLUGIN_DIR')) {
    define('PREMIUMDEV_WOO_PAYPAL_CREDIT_CARD_REST_PLUGIN_DIR', dirname(__FILE__));
}

if (!defined('PAYPAL_CREDIT_CARD_REST_PLUGIN_BASENAME')) {
    define('PAYPAL_CREDIT_CARD_REST_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pal-credit-card-rest-activator.php
 */
function activate_woo_paypal_credit_card_rest() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-woo-paypal-credit-card-rest-activator.php';
    Woo_PayPal_Credit_Card_Rest_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pal-credit-card-rest-deactivator.php
 */
function deactivate_woo_paypal_credit_card_rest() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-woo-paypal-credit-card-rest-deactivator.php';
    Woo_PayPal_Credit_Card_Rest_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_woo_paypal_credit_card_rest');
register_deactivation_hook(__FILE__, 'deactivate_woo_paypal_credit_card_rest');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-woo-paypal-credit-card-rest.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_paypal_credit_card_rest() {

    $plugin = new Woo_PayPal_Credit_Card_Rest();
    $plugin->run();
}

add_action('plugins_loaded', 'load_woo_paypal_credit_card_rest');

function load_woo_paypal_credit_card_rest() {
    run_woo_paypal_credit_card_rest();
}