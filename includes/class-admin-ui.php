<?php

function display_time_slot_on_order($order_id){
    error_log('display_time_slot_on_order() function - START - Order ID: ' . $order_id); // Debugging START - log order ID
    $selected_time_slot_id = get_post_meta($order_id, '_delivery_time_slot_id', true);

    if ($selected_time_slot_id){
        $time_slot = get_post($selected_time_slot_id);
        if ($time_slot && !is_wp_error($time_slot)){
            $time_slot_title = esc_html(get_the_title($time_slot));
            $time_slot_start = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_start_time', true));
            $time_slot_end = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_end_time', true));
            $time_slot_range = $time_slot_start . '-' . $time_slot_end;

        ?>
        <div class="time_slot_order">
            <h3>Time Slot Title</h3>
            <p> <?php echo esc_html($time_slot_title) ?> </p>
            <h3>Time Slot Range</h3>
            <p> <?php echo esc_html($time_slot_range) ?> </p>
        </div>
        <?php
        }
    }
    error_log('display_time_slot_on_order() function - END - Order ID: ' . $order_id); // Debugging END - log function end

}
add_action('woocommerce_admin_order_data_after_billing_address', 'display_time_slot_on_order', 10, 1);
add_action('woocommerce_email_customer_details', 'display_time_slot_on_order', 20, 4);
add_action('woocommerce_view_order_details_after_customer_details', 'display_time_slot_on_order', 10, 1);