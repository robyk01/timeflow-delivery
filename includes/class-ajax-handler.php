<?php

add_action('wp_ajax_timeflow_get_time_slots', 'timeflow_get_time_slots');
add_action('wp_ajax_nopriv_timeflow_get_time_slots', 'timeflow_get_time_slots');

function timeflow_get_time_slots(){
    $selected_date = isset($_POST['date_slot_selection']) ? sanitize_text_field($_POST['date_slot_selection']) : '';
    $time_slot = display_time_slot_checkout_field();

    $response_data = array(
        'success' => true,
        'selected_date' => $selected_date,
        'time_slots' => $time_slot,
    );

    wp_send_json($response_data);

    wp_die();
}