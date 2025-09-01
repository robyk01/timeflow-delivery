<?php
/**
 * AJAX Handler for WooCommerce TimeFlow Delivery
 *
 * @package WooCommerce\TimeFlow\Delivery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooCommerce_TimeFlow_Delivery_Ajax_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Frontend AJAX actions
        add_action('wp_ajax_get_time_slots', array($this, 'get_time_slots'));
        add_action('wp_ajax_nopriv_get_time_slots', array($this, 'get_time_slots'));
        
        // Session handling
        add_action('wp_ajax_save_delivery_type_session', array($this, 'save_delivery_type_session'));
        add_action('wp_ajax_nopriv_save_delivery_type_session', array($this, 'save_delivery_type_session'));
        
        add_action('wp_ajax_save_date_slot_session', array($this, 'save_date_slot_session'));
        add_action('wp_ajax_nopriv_save_date_slot_session', array($this, 'save_date_slot_session'));
        
        add_action('wp_ajax_save_time_slot_selection', array($this, 'save_time_slot_selection'));
        add_action('wp_ajax_nopriv_save_time_slot_selection', array($this, 'save_time_slot_selection'));
        
        // Admin AJAX actions
        add_action('wp_ajax_timeflow_bulk_create_slots', array($this, 'bulk_create_slots'));
        add_action('wp_ajax_timeflow_get_time_slot', array($this, 'handle_get_time_slot'));
        add_action('wp_ajax_timeflow_add_time_slot', array($this, 'handle_add_time_slot'));
        add_action('wp_ajax_timeflow_delete_time_slot', array($this, 'handle_delete_time_slot'));
        
        // Add AJAX actions
        add_action('wp_ajax_timeflow_update_time_slot', array($this, 'handle_update_time_slot'));
    }

    /**
     * Get time slots for a specific date
     */
    public function get_time_slots() {
        check_ajax_referer('timeflow_get_slots_nonce', 'security');
        
        $date = isset($_POST['date_slot_selection']) ? sanitize_text_field($_POST['date_slot_selection']) : '';
        $delivery_type = isset($_POST['delivery_type']) ? sanitize_text_field($_POST['delivery_type']) : '';
        
        if (empty($date)) {
            wp_send_json_error(array('message' => __('Date is required', 'woocommerce-timeflow-delivery')));
            return;
        }

        // --- Check for Unavailable Dates --- 
        $all_settings = get_option('timeflow_delivery_settings', array()); 
        $unavailable_dates = isset($all_settings['timeflow_delivery_unavailable_dates']) ? $all_settings['timeflow_delivery_unavailable_dates'] : array(); 

        if (!is_array($unavailable_dates)) {
            $unavailable_dates = array();
        }

        if (in_array($date, $unavailable_dates)) {
             error_log('[TimeFlow Debug] Date ' . $date . ' is unavailable. Sending empty slots.');
             wp_send_json_success(array());
             return;
        }

        
        // Get day of week (lowercase)
        $day_of_week = strtolower(date('l', strtotime($date)));
        
        // Query time slots
        $args = array(
            'post_type' => 'delivery_time_slot',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_time_slot_available_days',
                    'value' => $day_of_week,
                    'compare' => 'LIKE'
                )
            )
        );
        
        $time_slots = get_posts($args);
        $formatted_slots = array();
        
        foreach ($time_slots as $slot) {
            $start_time = get_post_meta($slot->ID, '_time_slot_start_time', true);
            $end_time = get_post_meta($slot->ID, '_time_slot_end_time', true);
            $fee = get_post_meta($slot->ID, '_time_slot_fee', true);
            $delivery_methods = get_post_meta($slot->ID, '_time_slot_delivery_methods', true);
            
            if (!is_array($delivery_methods)) {
                $delivery_methods = array('shipping', 'pickup');
            }
            
            // Check if the slot is available for the selected delivery type
            if (empty($delivery_type) || in_array($delivery_type, $delivery_methods)) {
                $formatted_slots[] = array(
                    'id' => $slot->ID,
                    'range' => $start_time . ' - ' . $end_time,
                    'fee' => !empty($fee) ? wc_price($fee) : ''
                );
            }
        }
        
        // Sort by start time
        usort($formatted_slots, function($a, $b) {
            return strtotime($a['range']) - strtotime($b['range']);
        });
        
        wp_send_json_success($formatted_slots);
    }

    /**
     * Bulk create time slots
     */
    public function bulk_create_slots() {
        check_ajax_referer('timeflow-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'woocommerce-timeflow-delivery')));
        }
        
        parse_str($_POST['form_data'], $form_data);
        
        $start_time = isset($form_data['bulk_start_time']) ? sanitize_text_field($form_data['bulk_start_time']) : '';
        $end_time = isset($form_data['bulk_end_time']) ? sanitize_text_field($form_data['bulk_end_time']) : '';
        $interval = isset($form_data['bulk_interval']) ? intval($form_data['bulk_interval']) : 60;
        $days = isset($form_data['bulk_days']) ? array_map('sanitize_text_field', $form_data['bulk_days']) : array();
        $fee = isset($form_data['bulk_fee']) ? sanitize_text_field($form_data['bulk_fee']) : '';
        $delivery_methods = isset($form_data['bulk_delivery_method']) ? array_map('sanitize_text_field', $form_data['bulk_delivery_method']) : array('shipping', 'pickup');
        
        if (empty($start_time) || empty($end_time) || empty($days)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'woocommerce-timeflow-delivery')));
        }
        
        // Convert times to timestamps
        $start_timestamp = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        
        if ($start_timestamp >= $end_timestamp) {
            wp_send_json_error(array('message' => __('End time must be after start time', 'woocommerce-timeflow-delivery')));
        }
        
        // Create time slots
        $count = 0;
        $current_timestamp = $start_timestamp;
        
        while ($current_timestamp < $end_timestamp) {
            $slot_end_timestamp = min($current_timestamp + ($interval * 60), $end_timestamp);
            
            $slot_start_time = date('H:i', $current_timestamp);
            $slot_end_time = date('H:i', $slot_end_timestamp);
            
            // Create post
            $post_data = array(
                'post_title' => $slot_start_time . ' - ' . $slot_end_time,
                'post_status' => 'publish',
                'post_type' => 'delivery_time_slot'
            );
            
            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                // Save meta data
                update_post_meta($post_id, '_time_slot_start_time', $slot_start_time);
                update_post_meta($post_id, '_time_slot_end_time', $slot_end_time);
                update_post_meta($post_id, '_time_slot_available_days', $days);
                update_post_meta($post_id, '_time_slot_delivery_methods', $delivery_methods);
                
                if (!empty($fee)) {
                    update_post_meta($post_id, '_time_slot_fee', $fee);
                }
                
                $count++;
            }
            
            $current_timestamp = $slot_end_timestamp;
        }
        
        wp_send_json_success(array('count' => $count));
    }

    /**
     * Get time slot data
     */
    public function handle_get_time_slot() {
        check_ajax_referer('timeflow-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'woocommerce-timeflow-delivery')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($post_id)) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'woocommerce-timeflow-delivery')));
        }
        
        $start_time = get_post_meta($post_id, '_time_slot_start_time', true);
        $end_time = get_post_meta($post_id, '_time_slot_end_time', true);
        $fee = get_post_meta($post_id, '_time_slot_fee', true);
        $available_days = get_post_meta($post_id, '_time_slot_available_days', true);
        $delivery_methods = get_post_meta($post_id, '_time_slot_delivery_methods', true);
        
        if (!is_array($delivery_methods)) {
            $delivery_methods = array('shipping', 'pickup');
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'fee' => $fee,
            'available_days' => $available_days,
            'delivery_method' => $delivery_methods
        ));
    }

    /**
     * Add a single time slot
     */
    public function handle_add_time_slot() {
        check_ajax_referer('timeflow-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'woocommerce-timeflow-delivery')));
        }
        
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
        $end_time = isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : '';
        $available_days = isset($_POST['available_days']) ? array_map('sanitize_text_field', $_POST['available_days']) : array();
        $fee = isset($_POST['fee']) ? sanitize_text_field($_POST['fee']) : '';
        $delivery_methods = isset($_POST['delivery_method']) ? array_map('sanitize_text_field', $_POST['delivery_method']) : array('shipping', 'pickup');
        
        if (empty($start_time) || empty($end_time) || empty($available_days)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'woocommerce-timeflow-delivery')));
        }
        
        // Convert times to timestamps
        $start_timestamp = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        
        if ($start_timestamp >= $end_timestamp) {
            wp_send_json_error(array('message' => __('End time must be after start time', 'woocommerce-timeflow-delivery')));
        }
        
        // Create post
        $post_data = array(
            'post_title' => $start_time . ' - ' . $end_time,
            'post_status' => 'publish',
            'post_type' => 'delivery_time_slot'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        // Save meta data
        update_post_meta($post_id, '_time_slot_start_time', $start_time);
        update_post_meta($post_id, '_time_slot_end_time', $end_time);
        update_post_meta($post_id, '_time_slot_available_days', $available_days);
        update_post_meta($post_id, '_time_slot_delivery_methods', $delivery_methods);
        
        if (!empty($fee)) {
            update_post_meta($post_id, '_time_slot_fee', $fee);
        }
        
        wp_send_json_success(array('post_id' => $post_id));
    }

    /**
     * Delete a time slot
     */
    public function handle_delete_time_slot() {
        check_ajax_referer('timeflow-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'woocommerce-timeflow-delivery')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($post_id)) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'woocommerce-timeflow-delivery')));
        }
        
        // Check if post exists and is a time slot
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'delivery_time_slot') {
            wp_send_json_error(array('message' => __('Invalid time slot', 'woocommerce-timeflow-delivery')));
        }
        
        // Delete the post
        $result = wp_delete_post($post_id, true);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to delete time slot', 'woocommerce-timeflow-delivery')));
        }
    }

    /**
     * Handle update time slot
     */
    public function handle_update_time_slot() {
        check_ajax_referer('timeflow-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'woocommerce-timeflow-delivery')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
        $end_time = isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : '';
        $available_days = isset($_POST['available_days']) && is_array($_POST['available_days']) ? array_map('sanitize_text_field', $_POST['available_days']) : array();
        $fee = isset($_POST['fee']) ? sanitize_text_field($_POST['fee']) : '';
        $delivery_methods = isset($_POST['delivery_method']) && is_array($_POST['delivery_method']) ? array_map('sanitize_text_field', $_POST['delivery_method']) : array();
        
        // Validate required fields
        if (empty($post_id)) {
            wp_send_json_error(array('message' => __('Invalid time slot ID', 'woocommerce-timeflow-delivery')));
        }

        // Check if post exists and is a time slot
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'delivery_time_slot') {
            wp_send_json_error(array('message' => __('Invalid time slot', 'woocommerce-timeflow-delivery')));
        }
        
        if (empty($start_time)) {
            wp_send_json_error(array('message' => __('Start time is required', 'woocommerce-timeflow-delivery')));
        }
        
        if (empty($end_time)) {
            wp_send_json_error(array('message' => __('End time is required', 'woocommerce-timeflow-delivery')));
        }
        
        if (empty($available_days)) {
            wp_send_json_error(array('message' => __('Please select at least one day', 'woocommerce-timeflow-delivery')));
        }
        
        if (empty($delivery_methods)) {
            wp_send_json_error(array('message' => __('Please select at least one delivery method', 'woocommerce-timeflow-delivery')));
        }
        
        // Convert times to timestamps
        $start_timestamp = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        
        if ($start_timestamp >= $end_timestamp) {
            wp_send_json_error(array('message' => __('End time must be after start time', 'woocommerce-timeflow-delivery')));
        }
        
        // Update the post
        $post_data = array(
            'ID' => $post_id,
            'post_title' => $start_time . ' - ' . $end_time,
            'post_status' => 'publish',
            'post_type' => 'delivery_time_slot'
        );
        
        $result = wp_update_post($post_data);
        
        if ($result) {
            // Save meta data
            update_post_meta($post_id, '_time_slot_start_time', $start_time);
            update_post_meta($post_id, '_time_slot_end_time', $end_time);
            update_post_meta($post_id, '_time_slot_available_days', $available_days);
            update_post_meta($post_id, '_time_slot_delivery_methods', $delivery_methods);
            
            if (!empty($fee)) {
                update_post_meta($post_id, '_time_slot_fee', $fee);
            }
            
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to update time slot', 'woocommerce-timeflow-delivery')));
        }
    }

    /**
     * Save delivery type to session
     */
    public function save_delivery_type_session() {
        check_ajax_referer('timeflow_delivery_nonce', 'security');
        
        $delivery_type = isset($_POST['delivery_type']) ? sanitize_text_field($_POST['delivery_type']) : '';
        
        if (empty($delivery_type)) {
            wp_send_json_error(array('message' => __('Delivery type is required', 'woocommerce-timeflow-delivery')));
        }
        
        // Save to session
        WC()->session->set('timeflow_delivery_type', $delivery_type);
        
        wp_send_json_success();
    }
    
    /**
     * Save date slot to session
     */
    public function save_date_slot_session() {
        check_ajax_referer('timeflow_delivery_nonce', 'security');
        
        $date_slot = isset($_POST['date_slot_selection']) ? sanitize_text_field($_POST['date_slot_selection']) : '';
        
        if (empty($date_slot)) {
            wp_send_json_error(array('message' => __('Date slot is required', 'woocommerce-timeflow-delivery')));
        }
        
        // Save to session
        WC()->session->set('timeflow_date_slot', $date_slot);
        
        wp_send_json_success();
    }
    
    /**
     * Save time slot selection to session
     */
    public function save_time_slot_selection() {
        check_ajax_referer('timeflow_delivery_nonce', 'security');
        
        $time_slot_id = isset($_POST['time_slot_selection']) ? sanitize_text_field($_POST['time_slot_selection']) : '';
        $clear_fee = isset($_POST['clear_fee']) ? (bool)$_POST['clear_fee'] : false;
        
        error_log('[TimeFlow Debug][AJAX Save Slot] Received Slot ID: ' . print_r($time_slot_id, true) . ', Clear Fee: ' . print_r($clear_fee, true));

        if ($clear_fee || $time_slot_id === '-') {
            WC()->session->set('timeflow_time_slot_id', '');
            WC()->session->set('timeflow_time_slot_fee', '');
            WC()->session->set('timeflow_delivery_fee', ''); 
            error_log('[TimeFlow Debug][AJAX Save Slot] Cleared session variables.');
        } else {
            WC()->session->set('timeflow_time_slot_id', $time_slot_id);
            WC()->session->set('time_slot_selection_id', $time_slot_id);
            $fee = get_post_meta($time_slot_id, '_time_slot_fee', true);
            $fee = is_numeric($fee) ? $fee : ''; 
            WC()->session->set('timeflow_time_slot_fee', $fee);
            WC()->session->set('timeflow_delivery_fee', $fee); 
             error_log('[TimeFlow Debug][AJAX Save Slot] Set session variables - ID: ' . $time_slot_id . ', Fee: ' . $fee);
        }
        
        error_log('[TimeFlow Debug][AJAX Save Slot] Sending success response.');
        wp_send_json_success();
    }
}

// Initialize the AJAX handler
new WooCommerce_TimeFlow_Delivery_Ajax_Handler();