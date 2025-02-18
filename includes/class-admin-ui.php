<?php

function display_time_slot_on_order($order){
    $order_id = $order->get_id();
    $selected_time_slot_id = get_post_meta($order_id, '_delivery_time_slot_id', true);
    $selected_date_slot = get_post_meta($order_id, '_delivery_date_slot', true);

    if ($selected_time_slot_id && $selected_date_slot){
        $time_slot = get_post($selected_time_slot_id);
        if ($time_slot && !is_wp_error($time_slot)){
            $time_slot_title = esc_html(get_the_title($time_slot));
            $time_slot_start = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_start_time', true));
            $time_slot_end = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_end_time', true));
            $time_slot_range = $time_slot_start . '-' . $time_slot_end;

            echo '<p><strong>' . __('Date') . ':</strong>' . $selected_date_slot;
            echo '<p><strong>' . __('Time Slot') . ':</strong> ' . $time_slot_title . ' (' . $time_slot_range . ')</p>';
        }
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'display_time_slot_on_order', 10, 1);
add_action('woocommerce_email_customer_details', 'display_time_slot_on_order', 20, 4);
add_action('woocommerce_view_order_details_after_customer_details', 'display_time_slot_on_order', 10, 1);