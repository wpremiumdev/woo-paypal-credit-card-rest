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
class Woo_PayPal_Credit_Card_Rest {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Woo_PayPal_Credit_Card_Rest_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->plugin_name = 'woo-paypal-credit-card-rest';
        $this->version = '1.0.1';

        $this->load_dependencies();
        $this->set_locale();
        $this->woo_gateway_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();
		$prefix = is_network_admin() ? 'network_admin_' : '';
		add_filter("{$prefix}plugin_action_links_".PAYPAL_CREDIT_CARD_REST_PLUGIN_BASENAME, array($this, 'premiumdev_paypal_credit_card_rest_plugin_link'), 10, 4);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Woo_PayPal_Credit_Card_Rest_Loader. Orchestrates the hooks of the plugin.
     * - Woo_PayPal_Credit_Card_Rest_i18n. Defines internationalization functionality.
     * - Woo_PayPal_Credit_Card_Rest_Admin. Defines all hooks for the admin area.
     * - Woo_PayPal_Credit_Card_Rest_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-credit-card-rest-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-credit-card-rest-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-paypal-credit-card-rest-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-woo-paypal-credit-card-rest-public.php';

        /**
         * The class responsible for defining all actions that occur in the Credit Card Rest Section Form
         * 
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-premiumdev-woo-paypal-credit-card-rest-common-function.php';

        /**
         * The class responsible for defining all actions that occur in the Credit Card Rest Gateways
         * 
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-premiumdev-woo-paypal-credit-card-rest-gateway.php';


        $this->loader = new Woo_PayPal_Credit_Card_Rest_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Woo_PayPal_Credit_Card_Rest_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Woo_PayPal_Credit_Card_Rest_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Add Payment Gateways Woocommerce Section
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function woo_gateway_hooks() {
        add_filter('woocommerce_payment_gateways', array($this, 'premiumdev_methods_woo_paypal_credit_card_rest_gateways'), 10, 1);
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Woo_PayPal_Credit_Card_Rest_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Woo_PayPal_Credit_Card_Rest_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Woo_PayPal_Credit_Card_Rest_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    public function premiumdev_methods_woo_paypal_credit_card_rest_gateways($methods) {
        $methods[] = 'Premiumdev_Woo_PayPal_Credit_Card_Rest_Gateway';
        return $methods;
    }
	
	public function premiumdev_paypal_credit_card_rest_plugin_link($actions, $plugin_file, $plugin_data, $context) {
        $custom_actions = array(
            'configure' => sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paypal_credit_card_rest' ), __( 'Configure', 'woo-paypal-credit-card-rest' ) ),
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.premiumdev.com/product/paypal-credit-card-rest-woocommerce/', __('Docs', 'woo-paypal-credit-card-rest')),
            'support' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/woo-paypal-credit-card-rest', __('Support', 'woo-paypal-credit-card-rest')),
            'review' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/view/plugin-reviews/woo-paypal-credit-card-rest', __('Write a Review', 'woo-paypal-credit-card-rest')),
        );        
        return array_merge($custom_actions, $actions);
    }

}