<?php

function display_time_slot_checkout_field(){

    

};

add_action('woocommerce_checkout_before_order_review', 'display_time_slot_checkout_field');