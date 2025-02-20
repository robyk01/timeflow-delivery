<?php
/**
 * Plugin Name:       WooCommerce TimeFlow Delivery
 * Description:       Allows customers to select delivery date and time slots during WooCommerce checkout.
 * Version:           1.5.0
 * Author:            Amore Roberto
 */
    
    if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

    
define( 'WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_VERSION', '1.0.0' );
define( 'WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-time-slots-cpt.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-admin-ui.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-frontend-checkout.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-ajax-handler.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-enqueue-scripts.php';
require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . '/includes/class-fee-handler.php';