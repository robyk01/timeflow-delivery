<?php
namespace WooCommerce\TimeFlow\Delivery;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the frontend checkout functionality for TimeFlow Delivery
 */
class Frontend_Checkout {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('woocommerce_checkout_before_order_review', array($this, 'display_delivery_form'));

        // Add shortcode for the delivery form
        //add_shortcode('timeflow_delivery_form', array($this, 'shortcode_delivery_form'));
 
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_time_slot_checkout'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_time_slot_in_order_details'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_assets'));
        
        // Add shipping details directly to order review
        add_action('woocommerce_review_order_after_shipping', array($this, 'display_shipping_details_on_review'));
        
        // Add shipping fee to cart
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_delivery_fee_to_cart'), 20, 1);
        
        // Reset TimeFlow session variables on checkout page load (not during AJAX)
        add_action('woocommerce_checkout_init', array($this, 'reset_timeflow_session_on_load'), 5); // Run early

        add_action('woocommerce_checkout_process', array($this, 'validate_timeflow_fields'));
    }

    /**
     * Enqueue checkout assets (styles and scripts)
     */
    public function enqueue_checkout_assets() {
        // Only load on the WooCommerce checkout page
        if ( ! function_exists('is_checkout') || ! is_checkout() ) { 
            error_log('TimeFlow Delivery: Not enqueueing assets - not on checkout page');
            return;
        }
        
        error_log('TimeFlow Delivery: Enqueueing checkout assets');
        
        $plugin_version = defined('WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_VERSION') ? WOOCOMMERCE_TIMEFLOW_DELIVERY_PLUGIN_VERSION : '1.0.0';
        $assets_url = plugin_dir_url( __DIR__ ) . 'assets/'; // More robust path

        // --- Styles --- 
        wp_enqueue_style(
            'timeflow-delivery-buttons',
            $assets_url . 'css/delivery-buttons.css',
            array(),
            $plugin_version,
            'all'
        );

        wp_enqueue_style(
            'timeflow-time-slots',
            $assets_url . 'css/time-slots.css',
            array(),
            $plugin_version,
            'all'
        );
        
        wp_enqueue_style(
            'timeflow-frontend',
            $assets_url . 'css/frontend.css',
            array('timeflow-delivery-buttons', 'timeflow-time-slots'),
            $plugin_version,
            'all'
        );

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );
        
        // Enqueue Flatpickr CSS
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            array(),
            '4.6.13' // Specify version
        );
        
        // --- Scripts --- 
        // Enqueue Flatpickr JS
        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            array(), // No dependencies
            '4.6.13', // Specify version
            true // Load in footer
        );

        wp_enqueue_script(
            'timeflow-update-time-slots', 
            $assets_url . 'js/update_time_slots.js',
            array( 'jquery' ), 
            $plugin_version, 
            true 
        );

        wp_localize_script(
            'timeflow-update-time-slots',
            'timeflowCheckoutParams', 
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'getSlotsNonce' => wp_create_nonce('timeflow_get_slots_nonce'), 
                'deliveryNonce' => wp_create_nonce('timeflow_delivery_nonce'), 
                'unavailableDates' => \WooCommerce_TimeFlow_Delivery_Settings::get_setting('timeflow_delivery_unavailable_dates', array()),
                // Add date range for Flatpickr maxDate
                'dateRangeDays' => \WooCommerce_TimeFlow_Delivery_Settings::get_setting('timeflow_delivery_date_range', 14),
                'i18n' => array(
                    'selectDatePrompt' => __('Please select a date first.', 'woocommerce-timeflow-delivery'),
                    'selectTimePrompt' => __('Select a time slot', 'woocommerce-timeflow-delivery'),
                    'noSlotsAvailable' => __('No time slots available for this date.', 'woocommerce-timeflow-delivery'),
                    'errorFetchingSlots' => __('Error fetching time slots. Please try again.', 'woocommerce-timeflow-delivery'),
                    'errorGeneral' => __('An unexpected error occurred.', 'woocommerce-timeflow-delivery'),
                    'selectDeliveryPrompt' => __('Please select a delivery method first.', 'woocommerce-timeflow-delivery')
                )
            )
        );
        
        error_log('TimeFlow Delivery: Assets enqueued successfully');
    }

    /**
     * Shortcode handler for the delivery/time slot form
     */
    public function shortcode_delivery_form($atts) {
        // Only show on checkout page, not on thank you/order received
        if (
            !function_exists('is_checkout') || !is_checkout() ||
            (function_exists('is_order_received_page') && is_order_received_page())
        ) {
            return '';
        }
        ob_start();
        $this->render_delivery_form();
        return ob_get_clean();
    }
    
    public function display_delivery_form() {
    	$this->render_delivery_form();
	}

    /**
     * Display the delivery and time slot selection form (for hooks or shortcode)
     */
    public function render_delivery_form() {
        // Only show on checkout page
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/checkout-form.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            wc_print_notice('TimeFlow Delivery checkout template is missing.', 'error');
        }
    }

    function display_delivery_and_time_slot_form() {
        ?>
        <div id="timeflow-delivery-form" class="timeflow-delivery-form">
            <div class="delivery-selection">
                <h3 class="delivery-section-title"><?php _e('Alege metoda de livrare', 'timeflow'); ?></h3>
                <div class="delivery-buttons-container">
                    <button type="button" id="shipping" data-delivery-type="shipping" class="delivery-buttons">
                        <i class="fas fa-truck"></i>
                        <?php _e('Livrare', 'timeflow'); ?>
                    </button>
                    <button type="button" id="pickup" data-delivery-type="pickup" class="delivery-buttons">
                        <i class="fas fa-store"></i>
                        <?php _e('Ridicare', 'timeflow'); ?>
                    </button>
                </div>
                <input type="hidden" id="delivery-type" name="delivery-type" value="">
            </div>

            <div class="timeflow_container">
                <div class="timeflow_title">
                    <h3><?php _e('Alege data și ora', 'timeflow'); ?></h3>
                </div>
                <div class="timeflow_date_slot">
                    <label for="date_slot_selection"><?php _e('Selectează data', 'timeflow'); ?></label>
                    <input type="date"
                           name="date_slot_selection"
                           id="date_slot_selection"
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+2 weeks')); ?>"
                    >
                </div>
                <div class="timeflow_time_slot">
                    <label for="time_slot_selection"><?php _e('Selectează ora', 'timeflow'); ?></label>
                    <select name="time_slot_selection" id="time_slot_selection">
                        <option value=""><?php _e('Alege un interval orar', 'timeflow'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

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

    /**
     * Save the selected details to the order
     */
    public function save_time_slot_checkout($order_id) {
        // Save Delivery Type
         $delivery_type = isset($_POST['delivery-type']) && !empty($_POST['delivery-type'])
            ? sanitize_text_field($_POST['delivery-type'])
            : (function_exists('WC') && WC()->session ? WC()->session->get('timeflow_delivery_type') : '');

        if (!empty($delivery_type)) {
            update_post_meta($order_id, '_delivery_type', $delivery_type);
        }

        // Save Delivery Date
        $delivery_date = isset($_POST['date_slot_selection']) && !empty($_POST['date_slot_selection'])
            ? sanitize_text_field($_POST['date_slot_selection'])
            : (function_exists('WC') && WC()->session ? WC()->session->get('timeflow_date_slot') : '');
 
        if (!empty($delivery_date)) {
            update_post_meta($order_id, '_delivery_date_slot', $delivery_date);
        }

        // Save Time Slot ID
        $time_slot_id = isset($_POST['time_slot_selection']) && !empty($_POST['time_slot_selection']) && $_POST['time_slot_selection'] !== '-'
            ? sanitize_text_field($_POST['time_slot_selection'])
            : (function_exists('WC') && WC()->session ? WC()->session->get('timeflow_time_slot_id') : '');

        if (!empty($time_slot_id)) {
            update_post_meta($order_id, '_delivery_time_slot_id', $time_slot_id);
            // Deprecated - keep for potential backward compatibility or remove later
            update_post_meta($order_id, '_time_slot_id', $time_slot_id); 
        } else {
            // Ensure old meta is cleared if no slot is selected
            delete_post_meta($order_id, '_delivery_time_slot_id');
            delete_post_meta($order_id, '_time_slot_id');
        }
    }

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

        $text_domain = 'woocommerce-timeflow-delivery'; 

        echo '<h2>' . __('Delivery Details', $text_domain) . '</h2>'; 
        echo '<table class="woocommerce-table woocommerce-table--delivery-details">';

        // Delivery Method Row
        echo '<tr>';
        echo '<th>' . __('Delivery Method:', $text_domain) . '</th>';
        if ($delivery_type) {
            $delivery_label = ($delivery_type === 'shipping') ? __('Shipping', $text_domain) : __('Pickup', $text_domain);
            echo '<td>' . esc_html($delivery_label) . '</td>';
        } else {
            echo '<td>' . __('Not Selected', $text_domain) . '</td>';
        }
        echo '</tr>';

        // Delivery Date Row
        echo '<tr>';
        echo '<th>' . __('Delivery Date:', $text_domain) . '</th>';
        if ($selected_date) {
            $formatted_date = date_i18n(get_option('date_format'), strtotime($selected_date));
            echo '<td>' . esc_html($formatted_date) . '</td>';
        } else {
            echo '<td>' . __('Not Selected', $text_domain) . '</td>';
        }
        echo '</tr>';

        // Time Slot Row
        echo '<tr>';
        echo '<th>' . __('Time Slot:', $text_domain) . '</th>';
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
            echo '<td>' . __('Not Selected', $text_domain) . '</td>';
        }
        echo '</tr>';

        echo '</table>';
    }

    /**
     * Reset TimeFlow session variables on initial checkout page load.
     * This prevents selections from persisting across full page reloads.
     */
    public function reset_timeflow_session_on_load($checkout) { 
        // Only run on checkout page load, *not* during AJAX calls that update fragments
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            error_log('[TimeFlow Debug] Resetting TimeFlow session on page load.');
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('timeflow_delivery_type', null);
                WC()->session->set('timeflow_date_slot', null);
                WC()->session->set('timeflow_time_slot_id', null);
                WC()->session->set('timeflow_time_slot_fee', null);
                WC()->session->set('timeflow_delivery_fee', null); // Clear old one too
                
                // Optional: recalculate cart if session was cleared (might remove fees)
                // if (WC()->cart) {
                //     WC()->cart->calculate_totals();
                // }
            } else {
                 error_log('[TimeFlow Debug] reset_timeflow_session_on_load: WC or session not available.');
            }
        } else {
              error_log('[TimeFlow Debug] reset_timeflow_session_on_load: Skipping reset during AJAX request.');
        }
    }

    /**
     * Add or remove delivery fee based on selection
     */
    public function add_delivery_fee_to_cart($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!function_exists('WC') || !WC()->session || !$cart || !method_exists($cart, 'fees_api')) {
            return;
        }

        $delivery_type = WC()->session->get('timeflow_delivery_type');
        $time_slot_id = WC()->session->get('timeflow_time_slot_id');
        $time_slot_fee = WC()->session->get('timeflow_time_slot_fee');

        $fees_api = $cart->fees_api();
        $existing_fees = $fees_api->get_fees();

        // --- Logica pentru REDUCEREA de Ridicare Personala (Pickup) ---
        $pickup_discount_id = 'timeflow_pickup_discount'; // AM SCHIMBAT ID-ul
        $nume_reducere_pickup = __('Reducere pentru ridicare personală', 'woocommerce-timeflow-delivery'); // AM SCHIMBAT NUMELE
        $valoare_reducere_pickup = -20; // AM PUS VALOAREA NEGATIVĂ

        if ('pickup' === $delivery_type) {
            $fees_api->add_fee(array(
                'id'        => $pickup_discount_id,
                'name'      => $nume_reducere_pickup,
                'amount'    => (float) $valoare_reducere_pickup,
                'taxable'   => false,
                'tax_class' => ''
            ));
        } else {
            if (isset($existing_fees[$pickup_discount_id])) {
                $fees_api->remove_fee($pickup_discount_id);
            }
        }

        // --- Logica originală pentru Taxa de Interval Orar (Time Slot) ---
        // Această parte rămâne neschimbată
        $timeslot_fee_id = 'timeflow_delivery_fee';
        $fee_name = __('Taxă de livrare', 'woocommerce-timeflow-delivery');
        $should_add_timeslot_fee = false;

        if (!empty($time_slot_id) && $time_slot_fee !== '' && is_numeric($time_slot_fee) && (float)$time_slot_fee > 0) {
            if ($delivery_type === 'shipping' || $delivery_type === 'pickup') {
                $should_add_timeslot_fee = true;
            }
        }

        if ($should_add_timeslot_fee) {
            $fees_api->add_fee(array(
                'id'        => $timeslot_fee_id,
                'name'      => $fee_name,
                'amount'    => (float) $time_slot_fee,
                'taxable'   => false,
                'tax_class' => ''
            ));
        } else {
            if (isset($existing_fees[$timeslot_fee_id])) {
                $fees_api->remove_fee($timeslot_fee_id);
            }
        }
    }

    /**
     * Display shipping details on the order review section after shipping options
     */
    public function display_shipping_details_on_review() {
        // error_log('[TimeFlow Debug][Display Details] Running display_shipping_details_on_review.'); 

        // Check if WC() and session are available
        if (!function_exists('WC') || !WC()->session) {
            // error_log('[TimeFlow Debug][Display Details] Exiting: WC or session not available.');
            return;
        }

        $delivery_type = WC()->session->get('timeflow_delivery_type');
        // We no longer need date_slot or time_slot_id for this specific display function
        // $date_slot = WC()->session->get('timeflow_date_slot');
        // $time_slot_id = WC()->session->get('timeflow_time_slot_id');

        // error_log('[TimeFlow Debug][Display Details] Session Data - Type: ' . print_r($delivery_type, true) ); 
        
        $details_html = '';
        $text_domain = 'woocommerce-timeflow-delivery'; // Define text domain
        $label = ''; // Initialize label variable

        if ($delivery_type === 'shipping') {
            // error_log('[TimeFlow Debug][Display Details] Delivery type is shipping.');
            $label = __('Transport', $text_domain); // Simple label for shipping
            
        } elseif ($delivery_type === 'pickup') {
             // error_log('[TimeFlow Debug][Display Details] Delivery type is pickup.');
             $label = __('Ridicare personală', $text_domain); // Changed text
            
        } else { 
            // error_log('[TimeFlow Debug][Display Details] Delivery type is neither shipping nor pickup, or empty: ' . print_r($delivery_type, true));
            // Don't output anything if no valid type is selected
            return; 
        }
        
        // Construct the HTML row if a label was set
        if (!empty($label)) {
             // error_log('[TimeFlow Debug][Display Details] Preparing HTML for label: ' . $label);
             $details_html .= '<tr class="timeflow-shipping-method-row">'; // Changed class name for clarity
             $details_html .= '<th scope="row">' . __('Livrare:', $text_domain) . '</th>'; 
             $details_html .= '<td><small>' . esc_html($label) . '</small></td>'; // Display the simple label
             $details_html .= '</tr>';
        }
        
        // Only echo if we have generated HTML
        if (!empty($details_html)) {
             // error_log('[TimeFlow Debug][Display Details] Echoing details HTML.');
             echo $details_html;
        } else {
             // error_log('[TimeFlow Debug][Display Details] No details HTML to echo.');
        }
    }

    /**
     * Validate required TimeFlow fields before order is processed
     */
    public function validate_timeflow_fields() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        $delivery_type = isset($_POST['delivery-type']) ? sanitize_text_field($_POST['delivery-type']) : '';
        $delivery_date = isset($_POST['date_slot_selection']) ? sanitize_text_field($_POST['date_slot_selection']) : '';
        $time_slot = isset($_POST['time_slot_selection']) ? sanitize_text_field($_POST['time_slot_selection']) : '';

        if (empty($delivery_type)) {
            wc_add_notice(__('Please select a delivery method.', 'woocommerce-timeflow-delivery'), 'error');
        }
        if (empty($delivery_date)) {
            wc_add_notice(__('Please select a delivery date.', 'woocommerce-timeflow-delivery'), 'error');
        }
        if (empty($time_slot)) {
            wc_add_notice(__('Please select a delivery time slot.', 'woocommerce-timeflow-delivery'), 'error');
        }
    }
}