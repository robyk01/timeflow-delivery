<?php


function display_time_slot_checkout_field(){
    ?>
    <div class="timeflow_date_slot">
        <label for="date_slot_selection">Alege data:</label>
        <input type="date" name="date_slot_selection" id="date_slot_selection">
    </div>
    <div class="timeflow_time_slot">
        <label for="time_slot_selection">Alege ora:</label>
        <select name="time_slot_selection" id="time_slot_selection">
            <option>-</option>
        </select>
    </div>
    <?php
}
add_action('woocommerce_before_order_notes', 'display_time_slot_checkout_field', 20);





function save_time_slot_checkout($order_id){
    $selected_time_slot = '';
    $selected_date_slot = '';

    if (isset($_POST['date_slot_selection'])){
        $selected_date_slot = sanitize_text_field($_POST['date_slot_selection']);
    }

    if (isset($_POST['time_slot_selection'])){
        $selected_time_slot = sanitize_text_field($_POST['time_slot_selection']);
    }

    update_post_meta($order_id, '_delivery_date_slot', $selected_date_slot);
    update_post_meta($order_id, '_delivery_time_slot_id', $selected_time_slot);

}
add_action('woocommerce_checkout_update_order_meta', 'save_time_slot_checkout', 20, 1);





function display_time_slot_in_order_details($order){
    $order_id = $order->get_id();
    $selected_time_slot_id = get_post_meta($order_id, '_delivery_time_slot_id', true);
    $selected_date_slot = get_post_meta($order_id, '_delivery_date_slot', true);

    if ($selected_time_slot_id && $selected_date_slot){
        $time_slot = get_post($selected_time_slot_id);
        if ($time_slot && !is_wp_error($time_slot)){
            $time_slot_title = esc_html(get_the_title($time_slot));
            $time_slot_start = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_start_time', true));
            $time_slot_end = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_end_time', true));
            $time_slot_fee = esc_html(get_post_meta($selected_time_slot_id, '_time_slot_fee', true));
            $time_slot_range = $time_slot_start . '-' . $time_slot_end;

            echo '<p><strong>' . __('Date') . ':</strong>' . $selected_date_slot;
            echo '<p><strong>' . __('Time Slot') . ':</strong> ' . $time_slot_title . ' (' . $time_slot_range . ' ' . 'Fee: ' . $time_slot_fee . ')</p>';
            
        }
    }
}
add_action('woocommerce_order_details_after_order_table', 'display_time_slot_in_order_details', 10, 1);


?>