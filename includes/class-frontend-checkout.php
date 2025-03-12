<?php
function display_delivery_and_time_slot_form() {
    ?>
    <div id="timeflow-delivery-form" class="timeflow-delivery-form">
        <div class="delivery-selection">
            <h3 class="delivery-section-title"><?php _e('Choose Delivery Method', 'timeflow'); ?></h3>
            <div class="delivery-buttons-container">
                <button type="button" id="shipping" data-delivery-type="shipping" class="delivery-buttons">
                    <i class="fas fa-truck"></i>
                    <?php _e('Shipping', 'timeflow'); ?>
                </button>
                <button type="button" id="pickup" data-delivery-type="pickup" class="delivery-buttons">
                    <i class="fas fa-store"></i>
                    <?php _e('Pickup', 'timeflow'); ?>
                </button>
            </div>
            <input type="hidden" id="delivery-type" name="delivery-type" value="">
        </div>

        <div class="timeflow_container">
            <div class="timeflow_title">
                <h3><?php _e('Select Delivery Time', 'timeflow'); ?></h3>
            </div>
            <div class="timeflow_date_slot">
                <label for="date_slot_selection"><?php _e('Select Date', 'timeflow'); ?></label>
                <input type="date"
                       name="date_slot_selection"
                       id="date_slot_selection"
                       min="<?php echo date('Y-m-d'); ?>"
                       max="<?php echo date('Y-m-d', strtotime('+2 weeks')); ?>"
                >
            </div>
            <div class="timeflow_time_slot">
                <label for="time_slot_selection"><?php _e('Select Time', 'timeflow'); ?></label>
                <select name="time_slot_selection" id="time_slot_selection">
                    <option value=""><?php _e('Choose a time slot', 'timeflow'); ?></option>
                </select>
            </div>
        </div>
    </div>
    <?php
}
add_action('woocommerce_review_order_before_shipping', 'display_delivery_and_time_slot_form');

function display_custom_delivery_type() {
    $delivery_type = WC()->session->get('timeflow_delivery_type');
    if ( $delivery_type ) {
        $label = $delivery_type === 'shipping' ? 'Shipping' : 'Pickup';
        echo '<tr class="delivery-type">
                <th>Delivery Type</th>
                <td>' . esc_html($label) . '</td>
              </tr>';
    }
}
add_action('woocommerce_review_order_before_order_total', 'display_custom_delivery_type');



function save_time_slot_checkout($order_id){
    $selected_time_slot = '';
    $selected_date_slot = '';
    $delivery_type = '';


    $selected_date_slot = WC()->session->get('time_slot_selection_date'); 
    $selected_time_slot = WC()->session->get('time_slot_selection_id');
    $delivery_type = WC()->session->get('timeflow_delivery_type'); 


    update_post_meta($order_id, '_delivery_date_slot', $selected_date_slot);
    update_post_meta($order_id, '_delivery_time_slot_id', $selected_time_slot);

    if ($delivery_type) {
        update_post_meta($order_id, '_delivery_type', $delivery_type);
    }
    error_log("Selected date slot is: " . $selected_date_slot);
    error_log("Selected time slot is: " . $selected_time_slot);
    error_log("Delivery type is: " . $delivery_type);
}
add_action('woocommerce_checkout_update_order_meta', 'save_time_slot_checkout');


add_filter('woocommerce_cart_needs_shipping', '__return_false');

function display_time_slot_in_order_details($order) {
    if (is_numeric($order)) {
        $order = wc_get_order($order);
    }

    if (!$order || !is_a($order, 'WC_Order')) {
        return;
    }

    $order_id = $order->get_id();
    $delivery_type = get_post_meta($order_id, '_delivery_type', true);
    $selected_date = get_post_meta($order_id, '_delivery_date_slot', true);
    $time_slot_id = get_post_meta($order_id, '_delivery_time_slot_id', true);

    echo '<h2>' . __('Delivery Details', 'timeflow') . '</h2>'; 
    echo '<table class="woocommerce-table woocommerce-table--delivery-details">';

    // Delivery Method Row
    echo '<tr>';
    echo '<th>' . __('Delivery Method:', 'timeflow') . '</th>';
    if ($delivery_type) {
        $delivery_label = ($delivery_type === 'shipping') ? __('Shipping', 'timeflow') : __('Pickup', 'timeflow');
        echo '<td>' . esc_html($delivery_label) . '</td>';
    } else {
        echo '<td>' . __('Not Selected', 'timeflow') . '</td>';
    }
    echo '</tr>';

    // Delivery Date Row
    echo '<tr>';
    echo '<th>' . __('Delivery Date:', 'timeflow') . '</th>';
    if ($selected_date) {
        $formatted_date = date_i18n(get_option('date_format'), strtotime($selected_date));
        echo '<td>' . esc_html($formatted_date) . '</td>';
    } else {
        echo '<td>' . __('Not Selected', 'timeflow') . '</td>';
    }
    echo '</tr>';

    // Time Slot Row
    echo '<tr>';
    echo '<th>' . __('Time Slot:', 'timeflow') . '</th>';
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
        echo '<td>' . $time_slot_display . '</td>';
    } else {
        echo '<td>' . __('Not Selected', 'timeflow') . '</td>';
    }
    echo '</tr>';

    echo '</table>';
}
add_action('woocommerce_order_details_after_order_table', 'display_time_slot_in_order_details', 10, 1);
?>