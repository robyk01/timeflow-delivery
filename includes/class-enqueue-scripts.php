<?php

add_action( 'wp_enqueue_scripts', 'timeflow_enqueue_checkout_script' );

function timeflow_enqueue_checkout_script() {
    if ( is_checkout() ) { 
        wp_enqueue_script(
            'update_time_slots', 
            plugin_dir_url( __FILE__ ) . '../assets/js/update_time_slots.js',
            array( 'jquery' ), 
            '1.0.0', 
            true 
        );

        wp_enqueue_script(
            'update_fees',
            plugin_dir_url(__FILE__) . '../assets/js/update_fees.js',
            '1.0.0',
            true
        );

        wp_localize_script(
            'update_fees',
            'timeflow_ajax_fees_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('timeflow_ajax_fees')
            )
        );

        wp_localize_script(
            'update_time_slots',
            'timeflow_ajax_params', 
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ), 
                'security_nonce' => wp_create_nonce( 'timeflow_ajax_nonce' ),
            )
        );

        wp_enqueue_style(
            'delivery-buttons',
            plugin_dir_url(__FILE__) . '../assets/css/delivery-buttons.css',
            array(),
            '1.0.1',
            'all'
        );
    }
}