<?php
/**
 * Settings Handler for WooCommerce TimeFlow Delivery
 *
 * @package WooCommerce\TimeFlow\Delivery
 */

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce_TimeFlow_Delivery_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        // Remove admin_init hook for settings registration
        // add_action('admin_init', array($this, 'register_settings')); 
        
        // Add hook for our manual save handler
        add_action('admin_post_timeflow_save_settings', array($this, 'handle_save_settings'));
    }

    /**
     * Register plugin settings - NO LONGER USED FOR SAVING
     * Keep for potential future use or reference, but comment out registration.
     */
    public function register_settings() {
        /* // --- Commented out --- 
        register_setting(
            'timeflow_delivery_options',
            'timeflow_delivery_settings',
            array($this, 'sanitize_settings') // Sanitize callback no longer needed here
        );

        add_settings_section(
            'timeflow_delivery_settings',
            __('General Settings', 'woocommerce-timeflow-delivery'),
            array($this, 'general_settings_section_callback'),
            'timeflow_delivery_options'
        );

        // Add settings fields - these just register the display callbacks now
        add_settings_field(
            'timeflow_delivery_date_range',
            __('Date Range', 'woocommerce-timeflow-delivery'),
            array($this, 'number_field_callback'),
            'timeflow_delivery_options',
            'timeflow_delivery_settings',
            array(
                'label_for' => 'timeflow_delivery_date_range',
                'description' => __('Number of days in advance customers can book delivery slots', 'woocommerce-timeflow-delivery'),
                'default' => 14
            )
        );

        add_settings_field(
            'timeflow_delivery_require_selection',
            __('Require Time Slot Selection', 'woocommerce-timeflow-delivery'),
            array($this, 'checkbox_field_callback'),
            'timeflow_delivery_options',
            'timeflow_delivery_settings',
            array(
                'label_for' => 'timeflow_delivery_require_selection',
                'description' => __('Require customers to select a delivery time slot', 'woocommerce-timeflow-delivery'),
                'default' => true
            )
        );

        add_settings_field(
            'timeflow_delivery_default_fee',
            __('Default Delivery Fee', 'woocommerce-timeflow-delivery'),
            array($this, 'number_field_callback'),
            'timeflow_delivery_options',
            'timeflow_delivery_settings',
            array(
                'label_for' => 'timeflow_delivery_default_fee',
                'description' => __('Default delivery fee for time slots (leave empty for no fee)', 'woocommerce-timeflow-delivery'),
                'default' => ''
            )
        );
        
        add_settings_field(
            'timeflow_delivery_unavailable_dates',
            __('Unavailable Dates', 'woocommerce-timeflow-delivery'),
            array($this, 'unavailable_dates_field_callback'),
            'timeflow_delivery_options',
            'timeflow_delivery_settings',
            array(
                'label_for' => 'timeflow_delivery_unavailable_dates',
                'description' => __('Select dates when delivery is unavailable.', 'woocommerce-timeflow-delivery')
            )
        );
        */ // --- End Commented Out --- 
    }

    /**
     * General settings section callback
     */
    public function general_settings_section_callback() {
        echo '<p>' . esc_html__('Configure general delivery settings.', 'woocommerce-timeflow-delivery') . '</p>';
    }

    /**
     * Number field callback
     */
    public function number_field_callback($args) {
        $options = get_option('timeflow_delivery_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <input type="number" 
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="timeflow_delivery_settings[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text">
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        // Restore original checkbox logic
        $options = get_option('timeflow_delivery_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <input type="checkbox" 
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="timeflow_delivery_settings[<?php echo esc_attr($args['label_for']); ?>]" 
               value="1"
               <?php checked($value, true); // Use checked() again ?>
        >
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    /**
     * Unavailable dates field callback
     */
    public function unavailable_dates_field_callback($args) {
        // Retrieve the entire settings array
        $options = get_option('timeflow_delivery_settings'); 
        
        // Get the specific array of unavailable dates using the label_for key
        $field_id = $args['label_for'];
        $unavailable_dates = isset($options[$field_id]) ? $options[$field_id] : array();
        
        // Ensure it's always an array
        if (!is_array($unavailable_dates)) {
            $unavailable_dates = array();
        }
        
        // Prepare comma-separated string for the single hidden input
        $unavailable_dates_string = implode(',', $unavailable_dates);
        
        // Restore the full UI for unavailable dates
        ?>
        <div class="timeflow-unavailable-dates">
            <div class="timeflow-date-list">
                <?php if (!empty($unavailable_dates)): ?>
                    <?php foreach ($unavailable_dates as $date): ?>
                        <div class="timeflow-date-item" data-date="<?php echo esc_attr($date); ?>">
                            <span class="date-display"><?php echo esc_html($date); ?></span>
                            <button type="button" class="button remove-date"><?php echo esc_html__('Remove', 'woocommerce-timeflow-delivery'); ?></button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-dates"><?php echo esc_html__('No unavailable dates set.', 'woocommerce-timeflow-delivery'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="timeflow-add-date">
                <input type="date" id="new-unavailable-date" class="regular-text">
                <button type="button" class="button add-date"><?php echo esc_html__('Add Date', 'woocommerce-timeflow-delivery'); ?></button>
            </div>
            
            <input type="hidden" 
                   id="timeflow_unavailable_dates_string" 
                   name="timeflow_delivery_settings[timeflow_unavailable_dates_string]"
                   value="<?php echo esc_attr($unavailable_dates_string); ?>">
                   
        </div>
        
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                
                function updateHiddenInputString() {
                    var dates = [];
                    $('.timeflow-date-list .timeflow-date-item').each(function() {
                        dates.push($(this).data('date'));
                    });
                    $('#timeflow_unavailable_dates_string').val(dates.join(','));
                    console.log('Updated hidden string:', $('#timeflow_unavailable_dates_string').val());
                }
                
                $('.timeflow-add-date .add-date').on('click', function(event) {
                    event.stopPropagation();
                    var dateInput = $('#new-unavailable-date');
                    var dateValue = dateInput.val();
                    if (!dateValue) { alert('<?php echo esc_js(__('Please select a date', 'woocommerce-timeflow-delivery')); ?>'); return; }
                    if ($('.timeflow-date-item[data-date="' + dateValue + '"]').length > 0) { alert('<?php echo esc_js(__('This date has already been added.', 'woocommerce-timeflow-delivery')); ?>'); return; }
                    var dateDisplay = dateValue; 
                    var dateItem = $('<div class="timeflow-date-item" data-date="' + dateValue + '">' + '<span class="date-display">' + dateDisplay + '</span>' + '<button type="button" class="button remove-date"><?php echo esc_js(__('Remove', 'woocommerce-timeflow-delivery')); ?></button>' + '</div>');
                    $('.timeflow-date-list .no-dates').remove();
                    $('.timeflow-date-list').append(dateItem);
                    dateInput.val('');
                    updateHiddenInputString();
                });
                
                $(document).on('click', '.timeflow-date-item .remove-date', function(event) {
                    event.stopPropagation();
                    $(this).closest('.timeflow-date-item').remove();
                    if ($('.timeflow-date-item').length === 0) { $('.timeflow-date-list').append('<p class="no-dates"><?php echo esc_js(__('No unavailable dates set.', 'woocommerce-timeflow-delivery')); ?></p>'); }
                    updateHiddenInputString();
                });
            });
        </script>
        <?php
        // --- End Restore --- 
    }

    /**
     * Sanitize the settings array before saving. - NO LONGER USED
     *
     * @param array $input The input array from the settings form.
     * @return array The sanitized array to be saved.
     */
    /* // --- Commented out sanitize_settings --- 
    public function sanitize_settings($input) {
        // ... (old sanitization code) ...
    }
    */ // --- End Commented out sanitize_settings --- 

    /**
     * Get setting value
     */
    public static function get_setting($key, $default = '') {
        $options = get_option('timeflow_delivery_settings');
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Handle manual settings save via admin-post.php
     */
    public function handle_save_settings() {
        // Verify nonce
        if (!isset($_POST['_timeflownonce']) || !wp_verify_nonce($_POST['_timeflownonce'], 'timeflow_save_settings_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied');
        }
        
        // Get existing options
        $existing_options = get_option('timeflow_delivery_settings', array());
        $new_options = is_array($existing_options) ? $existing_options : array();
        
        // --- Sanitize and update each setting --- 
        
        // Date Range
        if (isset($_POST['timeflow_delivery_date_range'])) {
            $new_options['timeflow_delivery_date_range'] = max(1, intval($_POST['timeflow_delivery_date_range']));
        }
        
        // Require Selection (expects 1 or 0 from POST due to hidden input)
        if (isset($_POST['timeflow_delivery_require_selection'])) {
            $new_options['timeflow_delivery_require_selection'] = ($_POST['timeflow_delivery_require_selection'] == 1) ? 1 : 0; 
        } else {
             $new_options['timeflow_delivery_require_selection'] = 0; // Should not happen with hidden input, but fallback
        }
        
        // Default Fee
        if (isset($_POST['timeflow_delivery_default_fee'])) {
            $fee = trim($_POST['timeflow_delivery_default_fee']);
            $new_options['timeflow_delivery_default_fee'] = (is_numeric($fee) || $fee === '') ? $fee : '';
        }
        
        // Unavailable Dates (from comma-separated string)
        $unavailable_dates = array();
        if (isset($_POST['timeflow_unavailable_dates_string'])) {
            $dates_string = sanitize_text_field(wp_unslash($_POST['timeflow_unavailable_dates_string']));
            if (!empty($dates_string)) {
                $potential_dates = explode(',', $dates_string);
                $sanitized_dates = array();
                foreach ($potential_dates as $date_string) {
                    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date_string)) {
                        $sanitized_dates[] = $date_string;
                    }
                }
                $unavailable_dates = array_unique($sanitized_dates);
                sort($unavailable_dates);
            }
        }
        $new_options['timeflow_delivery_unavailable_dates'] = $unavailable_dates;
        
        // --- Update the option in the database --- 
        update_option('timeflow_delivery_settings', $new_options);
        
        // --- Redirect back to settings page with success message --- 
        $redirect_url = add_query_arg(
            array(
                'page' => 'timeflow-delivery', 
                'tab' => 'settings', 
                'settings-updated' => 'true' // Add success flag
            ), 
            admin_url('admin.php')
        );
        
        wp_redirect($redirect_url);
        exit;
    }
}

// Initialize the settings
$timeflow_settings_instance = new WooCommerce_TimeFlow_Delivery_Settings();

// Hook the save handler to admin_post
add_action('admin_post_timeflow_save_settings', array($timeflow_settings_instance, 'handle_save_settings')); 