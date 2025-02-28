<?php
function timeflow_add_delivery_fee($cart){
    if (is_admin() && !defined('DOING_AJAX')){
        return;
    }

    $time_slot_id = timeflow_get_selected_time_slot_id();
    $time_slot_fee = timeflow_get_time_slot_fee($time_slot_id);

    
    if ($time_slot_fee > 0){
        $cart->add_fee('Fee', $time_slot_fee);
    }
}
add_action('woocommerce_cart_calculate_fees', 'timeflow_add_delivery_fee');

function timeflow_get_selected_time_slot_id(){
   $time_slot_id = WC()->session->get('time_slot_selection_id');

   if ($time_slot_id === '-' || !is_numeric($time_slot_id)){
        return '';
   }
   return $time_slot_id;
}

function timeflow_get_time_slot_fee($time_slot_id){
    $time_slot_fee = get_post_meta($time_slot_id, '_time_slot_fee', true);
    return floatval($time_slot_fee);
}

function timeflow_clear_time_slot_session() {
    if ( is_checkout() && $_SERVER['REQUEST_METHOD'] === 'GET' ) {
        WC()->session->__unset('time_slot_selection_id');
    }
}
add_action('template_redirect', 'timeflow_clear_time_slot_session');