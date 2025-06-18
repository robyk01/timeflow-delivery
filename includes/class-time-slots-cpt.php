<?php
/**
 * Custom Post Type Handler for Time Slots
 *
 * @package WooCommerce\TimeFlow\Delivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class WooCommerce_TimeFlow_Delivery_Time_Slots_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('init', array($this, 'register_post_type'));
	}

	/**
	 * Register the delivery_time_slot post type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Delivery Time Slots', 'post type general name', 'woocommerce-timeflow-delivery' ),
			'singular_name'      => _x( 'Delivery Time Slot', 'post type singular name', 'woocommerce-timeflow-delivery' ),
			'menu_name'          => _x( 'Time Slots', 'admin menu', 'woocommerce-timeflow-delivery' ),
			'name_admin_bar'     => _x( 'Time Slot', 'add new on admin bar', 'woocommerce-timeflow-delivery' ),
			'add_new'            => _x( 'Add New', 'time slot', 'woocommerce-timeflow-delivery' ),
			'add_new_item'       => __( 'Add New Time Slot', 'woocommerce-timeflow-delivery' ),
			'new_item'           => __( 'New Time Slot', 'woocommerce-timeflow-delivery' ),
			'edit_item'          => __( 'Edit Time Slot', 'woocommerce-timeflow-delivery' ),
			'view_item'          => __( 'View Time Slot', 'woocommerce-timeflow-delivery' ),
			'all_items'          => __( 'All Time Slots', 'woocommerce-timeflow-delivery' ),
			'search_items'       => __( 'Search Time Slots', 'woocommerce-timeflow-delivery' ),
			'parent_item_colon'  => __( 'Parent Time Slots:', 'woocommerce-timeflow-delivery' ),
			'not_found'          => __( 'No time slots found.', 'woocommerce-timeflow-delivery' ),
			'not_found_in_trash' => __( 'No time slots found in Trash.', 'woocommerce-timeflow-delivery' )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Stores delivery time slots.', 'woocommerce-timeflow-delivery' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, 
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
			'show_in_rest'       => false, // Disable Gutenberg editor
		);

		register_post_type( 'delivery_time_slot', $args );
	}
}

// Initialize the CPT handler
new WooCommerce_TimeFlow_Delivery_Time_Slots_CPT();