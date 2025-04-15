<?php
/**
 * Template for the delivery and time slot selection form
 *
 * @package WooCommerce\TimeFlow\Delivery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="timeflow-delivery-form" class="timeflow-delivery-form">
    <div class="delivery-selection">
        <h3 class="delivery-section-title"><?php esc_html_e('Select Delivery Method', 'woocommerce-timeflow-delivery'); ?></h3>
        <div class="delivery-buttons-container">
            <button type="button" id="shipping" data-delivery-type="shipping" class="delivery-buttons">
                <i class="fas fa-truck"></i>
                <?php esc_html_e('Shipping', 'woocommerce-timeflow-delivery'); ?>
            </button>
            <button type="button" id="pickup" data-delivery-type="pickup" class="delivery-buttons">
                <i class="fas fa-store"></i>
                <?php esc_html_e('Pickup', 'woocommerce-timeflow-delivery'); ?>
            </button>
        </div>
        <!-- Remove name attribute from visible field -->
        <input type="hidden" id="delivery-type" value="">
    </div>

    <div class="timeflow_container">
        <div class="timeflow_title">
            <h3><?php esc_html_e('Select Date and Time', 'woocommerce-timeflow-delivery'); ?></h3>
        </div>
        <div class="timeflow_date_slot">
            <label for="date_slot_selection"><?php esc_html_e('Select Date', 'woocommerce-timeflow-delivery'); ?></label>
            <?php
                // Keep this logic, but we don't need $max_date here anymore
                $date_range_days = WooCommerce_TimeFlow_Delivery_Settings::get_setting('timeflow_delivery_date_range', 14);
                $date_range_days = max(1, intval($date_range_days)); 
                // $max_date = date('Y-m-d', strtotime("+" . $date_range_days . " days"));
            ?>
            <?php // Change to text input for Flatpickr ?>
            <input type="text"
                   id="date_slot_selection"
                   name="date_slot_selection"
                   class="timeflow-date-picker" <?php // Add class for targeting ?>
                   placeholder="<?php esc_attr_e('Select a date', 'woocommerce-timeflow-delivery'); ?>" 
                   required
                   <?php // Remove min/max attributes - Flatpickr handles this ?>
            >
        </div>
        <div class="timeflow_time_slot">
            <label for="time_slot_selection"><?php esc_html_e('Select Time', 'woocommerce-timeflow-delivery'); ?></label>
            <select id="time_slot_selection" required>
                <option value=""><?php esc_html_e('Choose a time slot', 'woocommerce-timeflow-delivery'); ?></option>
            </select>
        </div>
    </div>
</div>

<?php if (function_exists('is_checkout') && is_checkout()): ?>
    <!-- Hidden fields inside the checkout form -->
    <input type="hidden" name="delivery-type" id="delivery-type-hidden" value="">
    <input type="hidden" name="date_slot_selection" id="date-slot-selection-hidden" value="">
    <input type="hidden" name="time_slot_selection" id="time-slot-selection-hidden" value="">
    <script>
    (function($){
        // Set delivery-type when button is clicked
        $('.delivery-buttons').on('click', function() {
            var type = $(this).data('delivery-type');
            $('#delivery-type').val(type).trigger('change');
            // Optionally, visually highlight the selected button
            $('.delivery-buttons').removeClass('selected');
            $(this).addClass('selected');
        });

        function syncTimeflowFields() {
            $('#delivery-type-hidden').val($('#delivery-type').val());
            $('#date-slot-selection-hidden').val($('#date_slot_selection').val());
            $('#time-slot-selection-hidden').val($('#time_slot_selection').val());
        }
        // Sync on change
        $('#delivery-type, #date_slot_selection, #time_slot_selection').on('change input', syncTimeflowFields);
        // Sync before checkout submit (WooCommerce event)
        $('form.checkout').on('checkout_place_order', syncTimeflowFields);
        // Also sync on submit as fallback
        $('form.checkout').on('submit', syncTimeflowFields);
        // Initial sync
        $(document).ready(syncTimeflowFields);
    })(jQuery);
    </script>
<?php endif; ?>