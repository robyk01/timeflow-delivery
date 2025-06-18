<?php
/**
 * Admin template for managing time slots and settings
 *
 * @package WooCommerce\TimeFlow\Delivery
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'time-slots';
?>

<div class="wrap timeflow-admin">
    <h1><?php echo esc_html__('TimeFlow Delivery', 'woocommerce-timeflow-delivery'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=timeflow-delivery&tab=time-slots')); ?>" 
           class="nav-tab <?php echo $current_tab === 'time-slots' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__('Time Slots', 'woocommerce-timeflow-delivery'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=timeflow-delivery&tab=settings')); ?>" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__('Settings', 'woocommerce-timeflow-delivery'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($current_tab === 'time-slots'): ?>
            <div id="time-slots-tab" class="timeflow-admin-card">
                <h2><?php echo esc_html__('Time Slots', 'woocommerce-timeflow-delivery'); ?></h2>
                
                <div class="timeflow-admin-card">
                    <h3><?php echo esc_html__('Add New Time Slot', 'woocommerce-timeflow-delivery'); ?></h3>
                    <form id="add-time-slot-form" class="timeflow-form">
                        <?php wp_nonce_field('timeflow-admin-nonce', 'timeflow_admin_nonce'); ?>
                        
                        <div class="form-row">
                            <label for="start_time"><?php echo esc_html__('Start Time', 'woocommerce-timeflow-delivery'); ?></label>
                            <input type="time" id="start_time" name="start_time" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="end_time"><?php echo esc_html__('End Time', 'woocommerce-timeflow-delivery'); ?></label>
                            <input type="time" id="end_time" name="end_time" required>
                        </div>
                        
                        <div class="form-row">
                            <label><?php echo esc_html__('Available Days', 'woocommerce-timeflow-delivery'); ?></label>
                            <div class="checkbox-group">
                                <?php
                                $days = array(
                                    'monday' => __('Monday', 'woocommerce-timeflow-delivery'),
                                    'tuesday' => __('Tuesday', 'woocommerce-timeflow-delivery'),
                                    'wednesday' => __('Wednesday', 'woocommerce-timeflow-delivery'),
                                    'thursday' => __('Thursday', 'woocommerce-timeflow-delivery'),
                                    'friday' => __('Friday', 'woocommerce-timeflow-delivery'),
                                    'saturday' => __('Saturday', 'woocommerce-timeflow-delivery'),
                                    'sunday' => __('Sunday', 'woocommerce-timeflow-delivery')
                                );
                                
                                foreach ($days as $value => $label) {
                                    printf(
                                        '<label><input type="checkbox" name="available_days[]" value="%s"> %s</label>',
                                        esc_attr($value),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <label for="fee"><?php echo esc_html__('Delivery Fee', 'woocommerce-timeflow-delivery'); ?></label>
                            <input type="number" id="fee" name="fee" step="0.01" min="0">
                        </div>
                        
                        <div class="form-row">
                            <label><?php echo esc_html__('Delivery Method', 'woocommerce-timeflow-delivery'); ?></label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="delivery_method[]" value="shipping" checked> <?php echo esc_html__('Shipping', 'woocommerce-timeflow-delivery'); ?></label>
                                <label><input type="checkbox" name="delivery_method[]" value="pickup" checked> <?php echo esc_html__('Pickup', 'woocommerce-timeflow-delivery'); ?></label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Add Time Slot', 'woocommerce-timeflow-delivery'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="timeflow-admin-card">
                    <h3><?php echo esc_html__('Existing Time Slots', 'woocommerce-timeflow-delivery'); ?></h3>
                    
                    <?php
                    $time_slots = get_posts(array(
                        'post_type' => 'delivery_time_slot',
                        'posts_per_page' => -1,
                        'orderby' => 'meta_value',
                        'meta_key' => '_time_slot_start_time',
                        'order' => 'ASC'
                    ));
                    
                    if (!empty($time_slots)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Time Range', 'woocommerce-timeflow-delivery'); ?></th>
                                    <th><?php echo esc_html__('Available Days', 'woocommerce-timeflow-delivery'); ?></th>
                                    <th><?php echo esc_html__('Fee', 'woocommerce-timeflow-delivery'); ?></th>
                                    <th><?php echo esc_html__('Delivery Method', 'woocommerce-timeflow-delivery'); ?></th>
                                    <th><?php echo esc_html__('Actions', 'woocommerce-timeflow-delivery'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_slots as $slot):
                                    $start_time = get_post_meta($slot->ID, '_time_slot_start_time', true);
                                    $end_time = get_post_meta($slot->ID, '_time_slot_end_time', true);
                                    $available_days = get_post_meta($slot->ID, '_time_slot_available_days', true);
                                    $fee = get_post_meta($slot->ID, '_time_slot_fee', true);
                                    $delivery_methods = get_post_meta($slot->ID, '_time_slot_delivery_methods', true);
                                    
                                    if (!is_array($available_days)) {
                                        $available_days = array();
                                    }
                                    
                                    if (!is_array($delivery_methods)) {
                                        $delivery_methods = array('shipping', 'pickup');
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($start_time . ' - ' . $end_time); ?></td>
                                        <td>
                                            <?php
                                            $day_labels = array();
                                            foreach ($available_days as $day) {
                                                $day_labels[] = $days[$day];
                                            }
                                            echo esc_html(implode(', ', $day_labels));
                                            ?>
                                        </td>
                                        <td><?php echo !empty($fee) ? wc_price($fee) : '-'; ?></td>
                                        <td>
                                            <?php
                                            $method_labels = array();
                                            if (in_array('shipping', $delivery_methods)) {
                                                $method_labels[] = __('Shipping', 'woocommerce-timeflow-delivery');
                                            }
                                            if (in_array('pickup', $delivery_methods)) {
                                                $method_labels[] = __('Pickup', 'woocommerce-timeflow-delivery');
                                            }
                                            echo esc_html(implode(', ', $method_labels));
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button quick-edit" 
                                                    data-post-id="<?php echo esc_attr($slot->ID); ?>">
                                                <?php echo esc_html__('Quick Edit', 'woocommerce-timeflow-delivery'); ?>
                                            </button>
                                            <button type="button" class="button delete-slot" 
                                                    data-post-id="<?php echo esc_attr($slot->ID); ?>">
                                                <?php echo esc_html__('Delete', 'woocommerce-timeflow-delivery'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php echo esc_html__('No time slots found.', 'woocommerce-timeflow-delivery'); ?></p>
                    <?php endif; ?>
                </div>
                
            </div>
            
        <?php elseif ($current_tab === 'settings'): ?>
            <div id="settings-tab" class="timeflow-admin-card">
                <h2><?php echo esc_html__('Plugin Settings', 'woocommerce-timeflow-delivery'); ?></h2>
                
                <?php // Display admin notices (like settings saved)
                if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
                    add_settings_error('timeflow_settings_messages', 'timeflow_message', __('Settings Saved', 'woocommerce-timeflow-delivery'), 'updated');
                }
                settings_errors('timeflow_settings_messages'); 
                ?>
                
                <?php // Manual form targeting admin-post.php - Add novalidate ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate>
                    <input type="hidden" name="action" value="timeflow_save_settings">
                    <?php wp_nonce_field('timeflow_save_settings_nonce', '_timeflownonce'); ?>

                    <?php 
                    // We need to manually render the fields now 
                    // Get existing options to populate fields
                    $options = get_option('timeflow_delivery_settings', array());
                    
                    // Default values (reuse from original registration)
                    $defaults = array(
                        'timeflow_delivery_date_range' => 14,
                        'timeflow_delivery_require_selection' => true,
                        'timeflow_delivery_default_fee' => '',
                        'timeflow_delivery_unavailable_dates' => array()
                    );
                    // Ensure options are merged with defaults
                    $options = wp_parse_args($options, $defaults);
                    // Ensure boolean is treated correctly for checked() - using 1/0 might be safer
                    $require_selection_value = !empty($options['timeflow_delivery_require_selection']) ? 1 : 0; 
                    ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <?php // Date Range ?>
                            <tr>
                                <th scope="row">
                                    <label for="timeflow_delivery_date_range"><?php esc_html_e('Date Range', 'woocommerce-timeflow-delivery'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="timeflow_delivery_date_range"
                                           name="timeflow_delivery_date_range" 
                                           value="<?php echo esc_attr($options['timeflow_delivery_date_range']); ?>"
                                           class="regular-text">
                                    <p class="description"><?php esc_html_e('Number of days in advance customers can book delivery slots', 'woocommerce-timeflow-delivery'); ?></p>
                                </td>
                            </tr>
                            
                            <?php // Require Selection Checkbox ?>
                             <tr>
                                <th scope="row">
                                    <?php esc_html_e('Require Time Slot Selection', 'woocommerce-timeflow-delivery'); ?>
                                </th>
                                <td>
                                    <?php // Add hidden input to ensure value (0) is sent when unchecked ?>
                                    <input type="hidden" name="timeflow_delivery_require_selection" value="0">
                                    <input type="checkbox" 
                                           id="timeflow_delivery_require_selection"
                                           name="timeflow_delivery_require_selection"
                                           value="1"
                                           <?php checked($require_selection_value, 1); // Compare against 1 ?>
                                    >
                                     <label for="timeflow_delivery_require_selection">
                                        <span class="description"><?php esc_html_e('Require customers to select a delivery time slot', 'woocommerce-timeflow-delivery'); ?></span>
                                     </label>
                                </td>
                            </tr>
                            
                            <?php // Default Fee ?>
                             <tr>
                                <th scope="row">
                                    <label for="timeflow_delivery_default_fee"><?php esc_html_e('Default Delivery Fee', 'woocommerce-timeflow-delivery'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="timeflow_delivery_default_fee"
                                           name="timeflow_delivery_default_fee"
                                           value="<?php echo esc_attr($options['timeflow_delivery_default_fee']); ?>"
                                           step="0.01" 
                                           min="0"
                                           <?php // Use number_format_i18n for plain number placeholder ?>
                                           placeholder="<?php echo esc_attr(number_format_i18n(0, wc_get_price_decimals())); ?>"
                                           class="regular-text">
                                    <p class="description"><?php esc_html_e('Default delivery fee for time slots (leave empty for no fee)', 'woocommerce-timeflow-delivery'); ?></p>
                                </td>
                            </tr>
                            
                             <?php // Unavailable Dates - Reuse logic but render manually ?>
                             <tr>
                                <th scope="row">
                                    <?php esc_html_e('Unavailable Dates', 'woocommerce-timeflow-delivery'); ?>
                                </th>
                                <td>
                                    <?php 
                                    // --- Unavailable Dates UI Rendering (adapted from callback) --- 
                                    $unavailable_dates = $options['timeflow_delivery_unavailable_dates']; 
                                    // Ensure it's an array before implode
                                    if (!is_array($unavailable_dates)) { $unavailable_dates = array(); } 
                                    $unavailable_dates_string = implode(',', $unavailable_dates);
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
                                        <?php // Single hidden input for the string ?>
                                        <input type="hidden" 
                                               id="timeflow_unavailable_dates_string" 
                                               name="timeflow_unavailable_dates_string"
                                               value="<?php echo esc_attr($unavailable_dates_string); ?>">
                                    </div>
                                    <p class="description"><?php esc_html_e('Select dates when delivery is unavailable (e.g., holidays, store closures)', 'woocommerce-timeflow-delivery'); ?></p>
                                    
                                    <?php // Keep the JS inline here for simplicity, could be moved later ?>
                                    <script type="text/javascript">
                                        jQuery(document).ready(function($) {
                                            function updateHiddenInputString() {
                                                var dates = [];
                                                $('.timeflow-date-list .timeflow-date-item').each(function() { dates.push($(this).data('date')); });
                                                $('#timeflow_unavailable_dates_string').val(dates.join(','));
                                                // console.log('Updated hidden string:', $('#timeflow_unavailable_dates_string').val());
                                            }
                                            $('.timeflow-add-date .add-date').on('click', function(event) {
                                                event.stopPropagation();
                                                var dateInput = $('#new-unavailable-date'); var dateValue = dateInput.val();
                                                if (!dateValue) { alert('<?php echo esc_js(__('Please select a date', 'woocommerce-timeflow-delivery')); ?>'); return; }
                                                if ($('.timeflow-date-item[data-date="' + dateValue + '"]').length > 0) { alert('<?php echo esc_js(__('This date has already been added.', 'woocommerce-timeflow-delivery')); ?>'); return; }
                                                var dateItem = $('<div class="timeflow-date-item" data-date="' + dateValue + '"><span class="date-display">' + dateValue + '</span><button type="button" class="button remove-date"><?php echo esc_js(__('Remove', 'woocommerce-timeflow-delivery')); ?></button></div>');
                                                $('.timeflow-date-list .no-dates').remove();
                                                $('.timeflow-date-list').append(dateItem);
                                                dateInput.val('');
                                                updateHiddenInputString();
                                            });
                                            $(document).on('click', '.timeflow-date-item .remove-date', function(event) {
                                                event.stopPropagation(); $(this).closest('.timeflow-date-item').remove();
                                                if ($('.timeflow-date-item').length === 0) { $('.timeflow-date-list').append('<p class="no-dates"><?php echo esc_js(__('No unavailable dates set.', 'woocommerce-timeflow-delivery')); ?></p>'); }
                                                updateHiddenInputString();
                                            });
                                            // Initial update on page load in case there are existing dates
                                            updateHiddenInputString(); 
                                        });
                                    </script>
                                     <?php // --- End Unavailable Dates UI --- ?>
                                </td>
                            </tr>
                            
                        </tbody>
                    </table>

                    <?php submit_button(); // Standard save button ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div> 