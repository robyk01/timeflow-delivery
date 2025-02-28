<?php

add_action('wp_ajax_timeflow_get_time_slots', 'timeflow_get_time_slots');
add_action('wp_ajax_nopriv_timeflow_get_time_slots', 'timeflow_get_time_slots');

function timeflow_get_time_slots(){
    check_ajax_referer('timeflow_ajax_nonce', 'security');

    if (isset($_POST['date_slot_selection']) && !empty($_POST['date_slot_selection'])) {
        $selected_date = sanitize_text_field($_POST['date_slot_selection']);
        $day_of_week = date('l', strtotime($selected_date));

        $args = array(
            'post_type' => 'delivery_time_slot',
            'meta_query' => array(
                array(
                    'key' => '_time_slot_available_days',
                    'value' => $day_of_week,
                    'compare' => 'LIKE',
                ),
            ),
        );
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $time_slots = array();
            foreach ($query->posts as $time_slot) {
                $time_slot_start = get_post_meta($time_slot->ID, '_time_slot_start_time', true);
                $time_slot_end = get_post_meta($time_slot->ID, '_time_slot_end_time', true);
                $time_slot_fee = get_post_meta($time_slot->ID, '_time_slot_fee', true);
                $time_slot_range = esc_html($time_slot_start) . '-' . esc_html($time_slot_end);
                $time_slots[] = array(
                    'id' => $time_slot->ID,
                    'range' => $time_slot_range,
                    'fee' => $time_slot_fee
                );
            }
            wp_send_json_success($time_slots);
        } else {
            wp_send_json_error('No time slots available');
        }
    } else {
        wp_send_json_error('Invalid date');
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

add_action('wp_ajax_reset_fee_added_flag', 'reset_fee_added_flag');
add_action('wp_ajax_nopriv_reset_fee_added_flag', 'reset_fee_added_flag');
