<?php

/**
 * Fired during plugin activation
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Activator {
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public function lddfw_set_options() {
        // Create sync table.
        lddfw_create_sync_table();
        if ( !get_option( 'lddfw_delivery_drivers_page' ) ) {
            update_option( 'lddfw_sync_table', '4' );
        } else {
            // Sync data to table.
            lddfw_sync_table();
        }
        // Create a delivery driver role.
        add_role( 'driver', esc_html( __( 'Delivery driver', 'lddfw' ) ), array(
            'read'         => true,
            'edit_posts'   => false,
            'delete_posts' => false,
        ) );
        // Create the drivers panel page for the first activation.
        lddfw_create_drivers_panel_page();
        // Set default settings options.
        add_option( 'lddfw_out_for_delivery_status', 'wc-out-for-delivery' );
        add_option( 'lddfw_delivered_status', 'wc-completed' );
        add_option( 'lddfw_failed_attempt_status', 'wc-failed-delivery' );
        add_option( 'lddfw_driver_assigned_status', 'wc-driver-assigned' );
        add_option( 'lddfw_processing_status', 'wc-processing' );
        add_option( 'lddfw_sms_assign_to_driver_template', 'Hello [delivery_driver_first_name], order #[order_id] with [store_name] has been assigned to you. [delivery_driver_page]' );
        add_option( 'lddfw_sms_out_for_delivery_template', 'Hello [billing_first_name], status of your order #[order_id] with [store_name] has been changed to [order_status].' );
        add_option( 'lddfw_sms_start_delivery_template', 'Hello [billing_first_name], the delivery for order #[order_id] with [store_name] has been started. [estimated_time_of_arrival] [tracking_url]' );
        add_option( 'lddfw_sms_delivered_template', 'Hello [billing_first_name], your order #[order_id] from [store_name] has been successfully delivered.' );
        add_option( 'lddfw_sms_not_delivered_template', 'Hello [billing_first_name], we apologize, but your order #[order_id] from [store_name] could not be delivered as scheduled.' );
        // Remove unused settings during plugin activation from version 1.9.6
        delete_option( 'lddfw_whatsapp_assign_to_driver_template' );
        delete_option( 'lddfw_whatsapp_out_for_delivery_template' );
        delete_option( 'lddfw_whatsapp_start_delivery_template' );
        delete_option( 'lddfw_whatsapp_delivered_template' );
        delete_option( 'lddfw_whatsapp_not_delivered_template' );
        add_option( 'lddfw_failed_delivery_reason_1', __( 'Refused by the recipient.', 'lddfw' ) );
        add_option( 'lddfw_failed_delivery_reason_2', __( 'Incorrect address.', 'lddfw' ) );
        add_option( 'lddfw_failed_delivery_reason_3', __( 'Failed delivery attempt.', 'lddfw' ) );
        add_option( 'lddfw_failed_delivery_reason_4', __( 'Item Lost.', 'lddfw' ) );
        add_option( 'lddfw_failed_delivery_reason_5', __( 'Item damaged.', 'lddfw' ) );
        add_option( 'lddfw_delivery_dropoff_1', __( 'Delivered to the customer.', 'lddfw' ) );
        add_option( 'lddfw_delivery_dropoff_2', __( 'Left at the front door.', 'lddfw' ) );
        add_option( 'lddfw_delivery_dropoff_3', __( 'Left with the neighbor.', 'lddfw' ) );
    }

    /**
     * Short Description. (use period)
     *
     * @param array $network_wide network_wide array.
     * @since 1.0.0
     */
    public function activate( $network_wide ) {
        if ( is_multisite() && $network_wide ) {
            // Run the code for all sites in a Multisite network.
            foreach ( get_sites( array(
                'fields' => 'ids',
            ) ) as $blog_id ) {
                switch_to_blog( $blog_id );
                $this->lddfw_set_options();
            }
            restore_current_blog();
        } else {
            $this->lddfw_set_options();
        }
    }

}
