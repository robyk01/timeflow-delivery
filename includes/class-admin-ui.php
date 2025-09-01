<?php
/**
 * Admin UI for WooCommerce TimeFlow Delivery
 *
 * @package WooCommerce\TimeFlow\Delivery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooCommerce_TimeFlow_Delivery_Admin_UI {

    /**
     * Constructor
     */
    public function __construct() {
        // Admin menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Order details display
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_admin_order_delivery_details'), 20, 1);
        add_action('woocommerce_email_customer_details', array($this, 'display_admin_order_delivery_details'), 20, 4);
        add_action('woocommerce_view_order_details_after_customer_details', array($this, 'display_admin_order_delivery_details'), 10, 1);

        // New column: delivery date
        add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_order_list_column' ) );
        
        // Populate the column with content
        add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'add_delivery_date_content' ), 10, 2 );

        // Make the column sortable
        add_filter( 'woocommerce_shop_order_list_table_sortable_columns', array( $this, 'make_delivery_date_column_sortable' ) );
        
        // Handle the sorting logic
        add_filter( 'woocommerce_shop_order_list_table_prepare_items_query_args', array($this, 'handle_sorting_logic') );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        error_log('Adding delivery info meta box');
        add_submenu_page(
            'woocommerce',
            __('TimeFlow Delivery', 'woocommerce-timeflow-delivery'),
            __('TimeFlow Delivery', 'woocommerce-timeflow-delivery'),
            'manage_woocommerce',
            'timeflow-delivery',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Include the admin template
        require_once WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_DIR . 'templates/admin/time-slots.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if (strpos($hook, 'timeflow-delivery') === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'timeflow-admin-style',
            WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_VERSION
        );

        // Enqueue admin script
        wp_enqueue_script(
            'timeflow-admin-script',
            WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('timeflow-admin-script', 'timeflowAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('timeflow-admin-nonce'),
            'i18n' => array(
                'error' => __('An error occurred.', 'woocommerce-timeflow-delivery'),
                'confirmDelete' => __('Are you sure you want to delete this time slot?', 'woocommerce-timeflow-delivery'),
                'quickEdit' => __('Quick Edit', 'woocommerce-timeflow-delivery'),
                'startTime' => __('Start Time', 'woocommerce-timeflow-delivery'),
                'endTime' => __('End Time', 'woocommerce-timeflow-delivery'),
                'availableDays' => __('Available Days', 'woocommerce-timeflow-delivery'),
                'fee' => __('Fee', 'woocommerce-timeflow-delivery'),
                'deliveryMethod' => __('Delivery Method', 'woocommerce-timeflow-delivery'),
                'shipping' => __('Shipping', 'woocommerce-timeflow-delivery'),
                'pickup' => __('Pickup', 'woocommerce-timeflow-delivery'),
                'save' => __('Save', 'woocommerce-timeflow-delivery'),
                'monday' => __('Monday', 'woocommerce-timeflow-delivery'),
                'tuesday' => __('Tuesday', 'woocommerce-timeflow-delivery'),
                'wednesday' => __('Wednesday', 'woocommerce-timeflow-delivery'),
                'thursday' => __('Thursday', 'woocommerce-timeflow-delivery'),
                'friday' => __('Friday', 'woocommerce-timeflow-delivery'),
                'saturday' => __('Saturday', 'woocommerce-timeflow-delivery'),
                'sunday' => __('Sunday', 'woocommerce-timeflow-delivery'),
                'fillRequired' => __('Please fill in all required fields.', 'woocommerce-timeflow-delivery'),
                'selectDay' => __('Please select at least one day.', 'woocommerce-timeflow-delivery'),
                'selectDelivery' => __('Please select at least one delivery method.', 'woocommerce-timeflow-delivery'),
            )
        ));
    }

    /**
     * Display delivery details in admin order page
     */
    public function display_admin_order_delivery_details($order) {
        if (!$order || !is_a($order, 'WC_Order')) {
            return;
        }

        $order_id = $order->get_id();
        $delivery_type = get_post_meta($order_id, '_delivery_type', true);
        $selected_date = get_post_meta($order_id, '_delivery_date_slot', true);
        $time_slot_id = get_post_meta($order_id, '_delivery_time_slot_id', true);

        echo '<div class="order_data_column" style="width: 100%; clear: both; margin-top: 15px;">';
        echo '<h4>' . __('Delivery Information', 'woocommerce-timeflow-delivery') . '</h4>';
        echo '<table class="woocommerce-table woocommerce-table--delivery-details" style="width: 100%;">';

        // Delivery Type Row
        echo '<tr>';
        echo '<th style="text-align: left; padding: 8px;">' . __('Delivery Method:', 'woocommerce-timeflow-delivery') . '</th>';
        if ($delivery_type) {
            $delivery_label = ($delivery_type === 'shipping') ? __('Shipping', 'woocommerce-timeflow-delivery') : __('Pickup', 'woocommerce-timeflow-delivery');
            echo '<td style="padding: 8px;">' . esc_html($delivery_label) . '</td>';
        } else {
            echo '<td style="padding: 8px;">' . __('Not Selected', 'woocommerce-timeflow-delivery') . '</td>'; 
        }
        echo '</tr>';

        // Delivery Date Row
        echo '<tr>';
        echo '<th style="text-align: left; padding: 8px;">' . __('Delivery Date:', 'woocommerce-timeflow-delivery') . '</th>';
        if ($selected_date) {
            $formatted_date = date_i18n(get_option('date_format'), strtotime($selected_date));
            echo '<td style="padding: 8px;">' . esc_html($formatted_date) . '</td>';
        } else {
            echo '<td style="padding: 8px;">' . __('Not Selected', 'woocommerce-timeflow-delivery') . '</td>'; 
        }
        echo '</tr>';

        echo '<tr>';
        echo '<th style="text-align: left; padding: 8px;">' . __('Time Slot:', 'woocommerce-timeflow-delivery') . '</th>';
        if ($time_slot_id) {
            $time_slot_start = get_post_meta($time_slot_id, '_time_slot_start_time', true);
            $time_slot_end = get_post_meta($time_slot_id, '_time_slot_end_time', true);
            $time_slot_fee = get_post_meta($time_slot_id, '_time_slot_fee', true);
            $time_slot_range = '';
            if ($time_slot_start && $time_slot_end) {
                $time_slot_range = esc_html($time_slot_start) . ' - ' . esc_html($time_slot_end);
            }
            $time_slot_display = $time_slot_range;
            if ($time_slot_fee) {
                $time_slot_display .= ' (Fee: ' . wc_price($time_slot_fee) . ')';
            }
            echo '<td style="padding: 8px;">' . $time_slot_display . '</td>';
        } else {
            echo '<td style="padding: 8px;">' . __('Not Selected', 'woocommerce-timeflow-delivery') . '</td>'; 
        }
        echo '</tr>';

        echo '</table>';
        echo '</div>';
    }

    /**
     * New column: delivery date
     */
    public function add_order_list_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $column_name => $column_info ) {
            $new_columns[ $column_name ] = $column_info;
            if ( 'order_number' === $column_name ) {
                $new_columns['timeflow_delivery_date'] = __( 'Delivery Date', 'woocommerce-timeflow-delivery' );
            }
        }
        return $new_columns;
    }

    /**
     * Populate columb with data
     */
    public function add_delivery_date_content( $column, $order) {
        if ( 'timeflow_delivery_date' === $column ) {
            $selected_date = $order->get_meta('_delivery_date_slot');
    
            if  ( $selected_date ) {
                echo esc_html( date_i18n( get_option('date_format'), strtotime( $selected_date ) ) );
            } else {
                echo '-';
            }
            error_log($selected_date);
        }
    }

    /**
     * Make column sortable
     */
     public function make_delivery_date_column_sortable($columns){
        $columns['timeflow_delivery_date'] = 'timeflow_delivery_date';
        return $columns;
     }


     /**
      * Handle sorting logic
      */
    public function handle_sorting_logic($query_args){
        if ( isset( $_GET['orderby'] ) && 'timeflow_delivery_date' === $_GET['orderby'] ) {
            $query_args['meta_key'] = '_delivery_date_slot';   
            $query_args['orderby']  = 'meta_value';            // sorting by meta value
            $query_args['order']    = $_GET['order'] ?? 'ASC';
        }
        return $query_args;
    }
}