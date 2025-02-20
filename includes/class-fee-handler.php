<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Fee_Handler {

    public function __construct() {
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_delivery_fee'));
    }

    public function add_delivery_fee() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        if (WC()->session->get('fee_added')) {
            return;
        }

        $selected_time_slot_id = WC()->session->get('time_slot_selection_id');
        $fee = $this->get_time_slot_fee($selected_time_slot_id);

        if ($chosen_shipping === 'flat_rate:1' && $fee > 0) {
            WC()->cart->add_fee(__('Time Slot Fee', 'woocommerce'), $fee);
            WC()->session->set('fee_added', true);
        }
    }

    private function get_time_slot_fee($time_slot_id) {
        $fee = get_post_meta($time_slot_id, '_time_slot_fee', true);
        return is_numeric($fee) ? (float) $fee : 0;
    }
}

new Fee_Handler();