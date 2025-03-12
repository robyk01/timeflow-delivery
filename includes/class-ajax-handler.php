<?php

add_action('wp_ajax_timeflow_get_time_slots', 'timeflow_get_time_slots');
add_action('wp_ajax_nopriv_timeflow_get_time_slots', 'timeflow_get_time_slots');

function timeflow_get_time_slots(){
    check_ajax_referer('timeflow_ajax_nonce', 'security');

    if ( empty($_POST['date_slot_selection']) || empty($_POST['delivery_type']) ) {
        wp_send_json_error('Invalid date or delivery type');
        wp_die();
    }

    $selected_date = sanitize_text_field($_POST['date_slot_selection']);
    $delivery_type = sanitize_text_field($_POST['delivery_type']);

    WC()->session->set('timeflow_delivery_type', $delivery_type);

    $day_of_week = strtolower(date('l', strtotime($selected_date)));

    if ($delivery_type === 'shipping'){
        $shipping_value = '1';
    } else {
        $shipping_value = '2';
    }

    $args = array(
        'post_type'  => 'delivery_time_slot',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key'     => '_time_slot_available_days',
                'value'   => $day_of_week,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => '_time_slot_available_shipping',
                'value'   => '"' . $shipping_value . '"',
                'compare' => 'LIKE',
            ),
        ),
        'orderby'  => 'meta_value',
        'meta_key' => '_time_slot_start_time',
        'order'    => 'ASC',
    );


    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $time_slots = array();
        foreach ($query->posts as $time_slot) {
            $time_slot_start = get_post_meta($time_slot->ID, '_time_slot_start_time', true);
            $time_slot_end   = get_post_meta($time_slot->ID, '_time_slot_end_time', true);
            $time_slot_fee   = get_post_meta($time_slot->ID, '_time_slot_fee', true);
            $time_slot_range = esc_html($time_slot_start) . '-' . esc_html($time_slot_end);
            
            $time_slots[] = array(
                'id'    => $time_slot->ID,
                'range' => $time_slot_range,
                'fee'   => $time_slot_fee
            );
        }
        wp_send_json_success($time_slots);
    } else {
        wp_send_json_error('No time slots available');
    }
    

    wp_die();
}


add_action('wp_ajax_save_time_slot_selection', 'save_time_slot_selection');
add_action('wp_ajax_nopriv_save_time_slot_selection', 'save_time_slot_selection');

function save_time_slot_selection() {
    check_ajax_referer('timeflow_ajax_fees', 'security');

    if (isset($_POST['time_slot_selection'])) {
        WC()->session->set('time_slot_selection_id', sanitize_text_field($_POST['time_slot_selection']));
        wp_send_json_success('Time slot saved');
    } else {
        wp_send_json_error('Time slot not provided');
    }
    wp_die(); 
}

add_action('wp_ajax_save_delivery_type_session', 'save_delivery_type_session');
add_action('wp_ajax_nopriv_save_delivery_type_session', 'save_delivery_type_session');


add_action('wp_ajax_save_date_slot_session', 'save_date_slot_session'); 
add_action('wp_ajax_nopriv_save_date_slot_session', 'save_date_slot_session');

function save_date_slot_session() {
    if (!check_ajax_referer('timeflow_delivery_nonce', 'security', false)) { 
        wp_send_json_error('Invalid security token sent.');
        return;
    }

    if (!isset($_POST['date_slot_selection'])) {
        wp_send_json_error('Date slot not provided');
        return;
    }

    $date_slot_selection = sanitize_text_field($_POST['date_slot_selection']);

    WC()->session->set('time_slot_selection_date', $date_slot_selection); 
    wp_send_json_success("Date slot updated to: " . $date_slot_selection);
}


function save_delivery_type_session() {

    if (!check_ajax_referer('timeflow_delivery_nonce', 'security', false)) {
        wp_send_json_error('Invalid security token sent.');
        return;
    }

    if (!isset($_POST['delivery_type'])) {
        wp_send_json_error('Delivery type not provided');
        return;
    }

    $delivery_type = sanitize_text_field($_POST['delivery_type']);
    

    WC()->session->set('timeflow_delivery_type', $delivery_type);
    wp_send_json_success("Delivery type updated to: " . $delivery_type);
}

add_action('wp_ajax_save_delivery_type_session', 'save_delivery_type_session');
add_action('wp_ajax_nopriv_save_delivery_type_session', 'save_delivery_type_session');