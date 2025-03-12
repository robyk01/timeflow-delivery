<?php

function display_admin_order_delivery_details($order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return;
    }

    $order_id = $order->get_id();
    $delivery_type = get_post_meta($order_id, '_delivery_type', true);
    $selected_date = get_post_meta($order_id, '_delivery_date_slot', true);
    $time_slot_id = get_post_meta($order_id, '_delivery_time_slot_id', true);

    echo '<div class="order_data_column" style="width: 100%; clear: both; margin-top: 15px;">';
    echo '<h4>' . __('Delivery Information', 'timeflow') . '</h4>';
    echo '<table class="woocommerce-table woocommerce-table--delivery-details" style="width: 100%;">';

    // Delivery Type Row
    echo '<tr>';
    echo '<th style="text-align: left; padding: 8px;">' . __('Delivery Method:', 'timeflow') . '</th>';
    if ($delivery_type) {
        $delivery_label = ($delivery_type === 'shipping') ? __('Shipping', 'timeflow') : __('Pickup', 'timeflow');
        echo '<td style="padding: 8px;">' . esc_html($delivery_label) . '</td>';
    } else {
        echo '<td style="padding: 8px;">' . __('Not Selected', 'timeflow') . '</td>'; 
    }
    echo '</tr>';

    // Delivery Date Row
    echo '<tr>';
    echo '<th style="text-align: left; padding: 8px;">' . __('Delivery Date:', 'timeflow') . '</th>';
    if ($selected_date) {
        $formatted_date = date_i18n(get_option('date_format'), strtotime($selected_date));
        echo '<td style="padding: 8px;">' . esc_html($formatted_date) . '</td>';
    } else {
        echo '<td style="padding: 8px;">' . __('Not Selected', 'timeflow') . '</td>'; 
    }
    echo '</tr>';

    echo '<tr>';
    echo '<th style="text-align: left; padding: 8px;">' . __('Time Slot:', 'timeflow') . '</th>';
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
        echo '<td style="padding: 8px;">' . __('Not Selected', 'timeflow') . '</td>'; 
    }
    echo '</tr>';

    echo '</table>';
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_billing_address', 'display_admin_order_delivery_details', 20, 1);
add_action('woocommerce_email_customer_details', 'display_admin_order_delivery_details', 20, 4); 
add_action('woocommerce_view_order_details_after_customer_details', 'display_admin_order_delivery_details', 10, 1);