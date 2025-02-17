<?php

function display_time_slot_checkout_field(){
    $query = new WP_Query( array('post_type' => 'delivery_time_slot') );

    if ($query->have_posts()){
        ?>
        <div class="timeflow_time_slot">
        <label for="time_slot_selection">Alege ora:</label>
        <?php
        ?>
        <select name="time_slot_selection" id="time_slot_selection">
        <option>-</option>
        <?php
        foreach ($query->posts as $time_slot){
            $time_slot_start = get_post_meta($time_slot->ID, '_time_slot_start_time', true);
            $time_slot_end = get_post_meta($time_slot->ID, '_time_slot_end_time', true);
            $time_slot_range = esc_html($time_slot_start) . '-' . esc_html($time_slot_end);
            ?>
            <option value="<?php echo esc_attr($time_slot->ID) ?>"> <?php echo esc_html($time_slot_range) ?></option>
            <?php
        }
        ?>
        </select>
        </div>
        <?php
    }
    else{
        ?>
        <p>Nu există time slot-uri</p>
        <?php
    }

};
add_action('woocommerce_checkout_before_order_review', 'display_time_slot_checkout_field', 20); 


function save_time_slot_checkout($order_id){
    error_log('save_time_slot_checkout() function - START - Order ID: ' . $order_id); // Debugging START - log function start

    $selected_time_slot = '';
    error_log('Initial value of $selected_time_slot: ' . $selected_time_slot); // Log initial value

    if (isset($_POST['time_slot_selection'])){
        error_log('$_POST["time_slot_selection"] is SET'); // Log if $_POST is set
        $selected_time_slot = sanitize_text_field($_POST['time_slot_selection']);
        error_log('Value of $_POST["time_slot_selection"] after sanitization: ' . $selected_time_slot); // Log sanitized value
    } else {
        error_log('$_POST["time_slot_selection"] is NOT SET'); // Log if $_POST is NOT set
    }

    update_post_meta($order_id, '_delivery_time_slot_id', $selected_time_slot);
    error_log('update_post_meta() called with value: ' . $selected_time_slot); // Log value being saved
    error_log('save_time_slot_checkout() function - END - Order ID: ' . $order_id); // Debugging END - log function end
}
add_action('woocommerce_new_order', 'save_time_slot_checkout', 20, 1);