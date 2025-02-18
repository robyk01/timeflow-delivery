<?php

add_action( 'wp_enqueue_scripts', 'timeflow_enqueue_checkout_script' );

function timeflow_enqueue_checkout_script() {
    if ( is_checkout() ) { 
        wp_enqueue_script(
            'timeflow-checkout-script', 
            plugin_dir_url( __FILE__ ) . '../assets/js/timeflow-checkout-script.js',
            array( 'jquery' ), 
            '1.0.0', 
            true 
        );

        wp_localize_script(
            'timeflow-checkout-script',
            'timeflow_ajax_params', 
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ), 
                'security_nonce' => wp_create_nonce( 'timeflow_ajax_nonce' ),
            )
        );
    }
}