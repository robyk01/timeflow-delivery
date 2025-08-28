<?php
/**
 * Plugin Name:       WooCommerce TimeFlow Delivery
 * Plugin URI:        https://example.com/woocommerce-timeflow-delivery/
 * Description:       Allows customers to select delivery date and time slots during WooCommerce checkout, including setting unavailable dates and time slot fees.
 * Version:           2.5.0
 * Author:            Amore Roberto
 * Author URI:        https://example.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woocommerce-timeflow-delivery
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Tested up to:      6.5
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 */

    if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_VERSION', '2.5.0' );
define( 'WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-time-slots-cpt.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-admin-ui.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-frontend-checkout.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-ajax-handler.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-fee-handler.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-settings.php';

// Initialize plugin classes
function init_woocommerce_timeflow_delivery() {
    // Initialize CPT first
    new WooCommerce_TimeFlow_Delivery_Time_Slots_CPT();
    
    // Initialize AJAX handler
    new WooCommerce_TimeFlow_Delivery_Ajax_Handler();
    
    // Initialize settings
    new WooCommerce_TimeFlow_Delivery_Settings();
    
    // Initialize frontend checkout
    new \WooCommerce\TimeFlow\Delivery\Frontend_Checkout();
    
    // Initialize admin UI if in admin
    if (is_admin()) {
        new WooCommerce_TimeFlow_Delivery_Admin_UI();
    }
}
add_action('plugins_loaded', 'init_woocommerce_timeflow_delivery');

// Add WooCommerce dependency check
function check_woocommerce_dependency() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('WooCommerce TimeFlow Delivery requires WooCommerce to be installed and active.', 'woocommerce-timeflow-delivery'); ?></p>
            </div>
            <?php
        });
        return;
    }
}
add_action('admin_init', 'check_woocommerce_dependency');

function timeflow_register_order_meta_fields() {
    $fields = array(
        '_delivery_type',
        '_delivery_date_slot',
        '_delivery_time_slot_id',
    );
    foreach ($fields as $field) {
        register_post_meta('shop_order', $field, array(
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true, // Expose in REST API
            'auth_callback'=> '__return_true', // Allow REST access
        ));
    }
}
add_action('init', 'timeflow_register_order_meta_fields');


