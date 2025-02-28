<?php
/**
 * Registers the 'delivery_time_slot' Custom Post Type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class WooCommerce_TimeFlow_Delivery_Time_Slot_CPT {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_custom_post_type' ) ); 
		add_action( 'add_meta_boxes', array( $this, 'add_time_slot_meta_boxes' ) ); 
		add_action( 'save_post_delivery_time_slot', array( $this, 'save_time_slot_meta_data' ) ); 
	}


		/**
 		* time slots custom post type
 		*/
	public function register_custom_post_type() {

        $labels = array(
            'name'                  => _x( 'Time Slots', 'Post Type General Name', 'woocommerce-timeflow-delivery' ), 
            'singular_name'         => _x( 'Time Slot', 'Post Type Singular Name', 'woocommerce-timeflow-delivery' ), 
            'menu_name'             => __( 'Time Slots', 'woocommerce-timeflow-delivery' ), 
            'name_admin_bar'        => __( 'Time Slot', 'woocommerce-timeflow-delivery' ), 
            'archives'              => __( 'Time Slot Archives', 'woocommerce-timeflow-delivery' ),
            'attributes'            => __( 'Time Slot Attributes', 'woocommerce-timeflow-delivery' ),
            'parent_item_colon'     => __( 'Parent Time Slot:', 'woocommerce-timeflow-delivery' ),
            'all_items'             => __( 'All Time Slots', 'woocommerce-timeflow-delivery' ), 
            'add_new_item'          => __( 'Add New Time Slot', 'woocommerce-timeflow-delivery' ), 
            'add_new'               => __( 'Add New', 'woocommerce-timeflow-delivery' ), 
            'new_item'              => __( 'New Time Slot', 'woocommerce-timeflow-delivery' ),
            'edit_item'             => __( 'Edit Time Slot', 'woocommerce-timeflow-delivery' ), 
            'update_item'           => __( 'Update Time Slot', 'woocommerce-timeflow-delivery' ),
            'view_item'             => __( 'View Time Slot', 'woocommerce-timeflow-delivery' ),
            'view_items'            => __( 'View Time Slots', 'woocommerce-timeflow-delivery' ),
            'search_items'          => __( 'Search Time Slots', 'woocommerce-timeflow-delivery' ),
            'not_found'             => __( 'Not found', 'woocommerce-timeflow-delivery' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'woocommerce-timeflow-delivery' ),
            'featured_image'        => __( 'Featured Image', 'woocommerce-timeflow-delivery' ),
            'set_featured_image'    => __( 'Set featured image', 'woocommerce-timeflow-delivery' ),
            'remove_featured_image' => __( 'Remove featured image', 'woocommerce-timeflow-delivery' ),
            'use_featured_image'    => __( 'Use as featured image', 'woocommerce-timeflow-delivery' ),
            'insert_into_item'      => __( 'Insert into time slot', 'woocommerce-timeflow-delivery' ),
            'uploaded_to_this_item' => __( 'Uploaded to this time slot', 'woocommerce-timeflow-delivery' ),
            'items_list'            => __( 'Time slots list', 'woocommerce-timeflow-delivery' ),
            'items_list_navigation'   => __( 'Time slots list navigation', 'woocommerce-timeflow-delivery' ),
            'filter_items_list'     => __( 'Filter time slots list', 'woocommerce-timeflow-delivery' ),
        );

        $args = array(
            'label'                 => __( 'Time Slot', 'woocommerce-timeflow-delivery' ), 
            'labels'                => $labels, 
            'supports'              => array( 'title' ), 
            'hierarchical'          => false, 
            'public'                => false, 
            'show_ui'               => true, 
            'show_in_menu'          => true, 
            'menu_position'         => 5, 
            'menu_icon'             => 'dashicons-clock', 
            'show_in_admin_bar'     => true, 
            'show_in_nav_menus'     => false, 
            'can_export'            => true, 
            'has_archive'           => false, 
            'exclude_from_search'   => true, 
            'publicly_queryable'    => false, 
            'capability_type'       => 'post', 
            'show_in_rest'          => false,
        );

        register_post_type( 'delivery_time_slot', $args ); 
    }
	

	/**
	 * create metaboxes
	 */
	public function add_time_slot_meta_boxes() {
		add_meta_box(
            $id = 'time_slot_range_metabox',
            $title = 'Time Range',
            $callback = array($this, 'time_slot_range_metabox_callback'),
            $screen = 'delivery_time_slot',
            $context = 'normal',
            $priority = 'default',
            $callback_args = null,
        );
	}

    /**
     * callback function to output code
     */
    public function time_slot_range_metabox_callback( $post ){
        $start_time = get_post_meta($post->ID, '_time_slot_start_time', true);
        $end_time = get_post_meta($post->ID, '_time_slot_end_time', true);
        $available_days = get_post_meta($post->ID, '_time_slot_available_days', true);
        $weekdays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $fee = get_post_meta($post->ID, '_time_slot_fee', true);
        $shipping_zones = WC_Shipping_Zones::get_zones();
        $shipping_instances = array();
        $available_shipping = get_post_meta($post->ID, '_time_slot_available_shipping', true);

        foreach ($shipping_zones as $zone_data){
            $zone = new WC_Shipping_Zone($zone_data['id']);
            $zone_methods = $zone->get_shipping_methods();

            foreach ($zone_methods as $method_instance_id => $method_instance){
                $shipping_instances[$method_instance_id] = $method_instance;
            }
        }


        ?>
            <label for="time_slot_start_time">Start Time:</label>
            <input id="time_slot_start_time" type="time" name="time_slot_start_time" value="<?php echo esc_attr($start_time); ?>">

            <label for="time_slot_end_time">End Time:</label>
            <input id="time_slot_end_time" type="time" name="time_slot_end_time" value="<?php echo esc_attr($end_time); ?>">

            <p> Saved start time:
        <?php echo esc_html($start_time); ?>
            Saved end time:
        <?php echo esc_html($end_time); ?>
            </p>

            <div class="available_days">
                <p><strong>Available Days</strong></p>
                <?php
                foreach ($weekdays as $day){
                    if (in_array($day, $available_days)){
                        ?> <input type="checkbox" name="time_slot_available_days[]" value="<?php echo esc_attr($day); ?>" id="time_slot_<?php echo esc_attr($day); ?>" checked>
                        <label for="time_slot_<?php echo $day ?>"><?php echo $day ?></label><br><br>
                        <?php
                    }
                    else{
                        ?> <input type="checkbox" name="time_slot_available_days[]" value="<?php echo esc_attr($day); ?>" id="time_slot_<?php echo esc_attr($day); ?>">
                        <label for="time_slot_<?php echo $day ?>"><?php echo $day ?></label><br><br>
                        <?php
                    }
                }
                ?>
            </div>


            <div class="fee-discount">
                <p><strong>Add fee</strong></p>
                <input type="text" name="time_slot_fee" id="time_slot_fee" value="<?php echo esc_attr($fee); ?>">
                <p>Saved fee: <?php echo esc_html($fee) ?></p>
            </div>


            <div class="shipping">
                <p><strong>Available Shipping</strong></p>
                <?php
                foreach ($shipping_instances as $instance_id => $method_instance){
                    if (in_array($instance_id, $available_shipping)){
                        $method_title = isset($method_instance->title) ? esc_html($method_instance->title) : '';
                                ?>
                                <input type="checkbox" name="time_slot_available_shipping[]" value="<?php echo esc_attr($instance_id); ?>" id="<?php echo esc_attr($instance_id); ?>" checked>
                                <label for="<?php echo esc_attr($instance_id); ?>"><?php echo $method_title; ?></label><br><br>
                                <?php
                        }
                        else{
                            $method_title = isset($method_instance->title) ? esc_html($method_instance->title) : '';
                                    ?>
                                    <input type="checkbox" name="time_slot_available_shipping[]" value="<?php echo esc_attr($instance_id); ?>" id="<?php echo esc_attr($instance_id); ?>">
                                    <label for="<?php echo esc_attr($instance_id); ?>"><?php echo $method_title; ?></label><br><br>
                                    <?php
                        }
                    }
                ?>
            </div>
        <?php
    }


	/**
	 * Saves the meta data for 'delivery_time_slot' posts.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_time_slot_meta_data( $post_id ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        /** getting fields */

        if (isset($_POST['time_slot_start_time'])){
            $start_time = sanitize_text_field($_POST['time_slot_start_time']);
        }

        if (isset($_POST['time_slot_end_time'])){
            $end_time = sanitize_text_field($_POST['time_slot_end_time']);
        }

        $available_days = array();
        if (isset($_POST['time_slot_available_days']) && is_array($_POST['time_slot_available_days'])){
            foreach($_POST['time_slot_available_days'] as $day){
                $available_days[] = sanitize_text_field($day);
            }
        }

        if (isset($_POST['time_slot_fee'])){
            $fee = sanitize_text_field($_POST['time_slot_fee']);
        }

        $available_shipping = array();
        if (isset($_POST['time_slot_available_shipping']) && is_array($_POST['time_slot_available_shipping'])){
            foreach($_POST['time_slot_available_shipping'] as $shipping){
                $available_shipping[] = sanitize_text_field($shipping);
            }
        }

        /** updating meta */

        if (isset($start_time)){
            update_post_meta($post_id, '_time_slot_start_time', $start_time);
        }

        if (isset($end_time)){
            update_post_meta($post_id, '_time_slot_end_time', $end_time);
        }

        update_post_meta($post_id, '_time_slot_available_days', $available_days);

        if (isset($fee))
            update_post_meta($post_id, '_time_slot_fee', $fee);

        if (isset($available_shipping)){
            update_post_meta($post_id, '_time_slot_available_shipping', $available_shipping);
        }
        
	}

}

new WooCommerce_TimeFlow_Delivery_Time_Slot_CPT();