<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    LDDFW
 * @subpackage LDDFW/public
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Public {
    /**
     * The ID of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in LDDFW_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The LDDFW_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in LDDFW_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The LDDFW_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
    }

    /**
     * Show content in customer order
     *
     * @since 1.3.0
     */
    public function lddfw_action_order_details_before_order_table(
        $order,
        $sent_to_admin = '',
        $plain_text = '',
        $email = ''
    ) {
        // Only on "My Account" > "Order View".
        if ( is_wc_endpoint_url( 'view-order' ) ) {
            // Order Status.
            $order_status = $order->get_status();
            // Driver id.
            $lddfw_driver_id = $order->get_meta( 'lddfw_driverid' );
            if ( '' !== $lddfw_driver_id ) {
                /* driver note */
                $lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
                if ( '' !== $lddfw_driver_note ) {
                    echo '<p><b>' . esc_html( __( 'Driver note', 'lddfw' ) ) . ':</b><br> ' . esc_html( $lddfw_driver_note ) . '</p>';
                }
            }
        }
    }

    /**
     * Set the driver page.
     *
     * @since 1.0.0
     */
    public function lddfw_page_template( $page_template ) {
        global $post;
        if ( !empty( $post ) ) {
            if ( $post->ID === intval( get_option( 'lddfw_delivery_drivers_page', '' ) ) ) {
                $page_template = WP_PLUGIN_DIR . '/' . LDDFW_FOLDER . '/index.php';
            }
            if ( $post->ID === intval( get_option( 'lddfw_tracking_page', '' ) ) ) {
                $page_template = WP_PLUGIN_DIR . '/' . LDDFW_FOLDER . '/tracking.php';
            }
        }
        return $page_template;
    }

    /**
     * Initialize panel data globals.
     *
     * @since 1.0.0
     */
    public function lddfw_initialize_panel_data_globals() {
        global $lddfw_driver_assigned_status_name;
        global $lddfw_out_for_delivery_status_name;
        global $lddfw_failed_attempt_status_name;
        global $lddfw_driver_id;
        global $lddfw_out_for_delivery_counter;
        global $lddfw_failed_attempt_counter;
        global $lddfw_delivered_counter;
        global $lddfw_assign_to_driver_counter;
        global $lddfw_claim_orders_counter;
        global $lddfw_driver_name;
        global $lddfw_driver_availability;
        global $lddfw_drivers_tracking_timing;
        global $lddfw_screen;
        global $lddfw_order_id;
        global $lddfw_reset_key;
        global $lddfw_page;
        global $lddfw_reset_login;
        global $lddfw_dates;
        global $lddfw_wpnonce;
        global $lddfw_user;
        $lddfw_driver_id = '';
        $lddfw_wpnonce = wp_create_nonce( 'lddfw-nonce' );
        $lddfw_drivers_tracking_timing = '';
        /**
         * Get WordPress query_var.
         */
        $lddfw_screen = ( '' !== get_query_var( 'lddfw_screen' ) ? get_query_var( 'lddfw_screen' ) : 'dashboard' );
        $lddfw_order_id = get_query_var( 'lddfw_orderid' );
        $lddfw_reset_key = get_query_var( 'lddfw_reset_key' );
        $lddfw_page = get_query_var( 'lddfw_page' );
        $lddfw_reset_login = get_query_var( 'lddfw_reset_login' );
        $lddfw_dates = get_query_var( 'lddfw_dates' );
        // Check if user is a delivery driver.
        $lddfw_user = wp_get_current_user();
        $lddfw_driver_id = $lddfw_user->ID;
        $lddfw_driver_name = $lddfw_user->display_name;
        $lddfw_driver_availability = get_user_meta( $lddfw_driver_id, 'lddfw_driver_availability', true );
        /**
         * Set current status names
         */
        $lddfw_driver_assigned_status_name = esc_html( __( 'Driver assigned', 'lddfw' ) );
        $lddfw_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'lddfw' ) );
        $lddfw_failed_attempt_status_name = esc_html( __( 'Failed delivery', 'lddfw' ) );
        if ( function_exists( 'wc_get_order_statuses' ) ) {
            $result = wc_get_order_statuses();
            if ( !empty( $result ) ) {
                foreach ( $result as $key => $status ) {
                    switch ( $key ) {
                        case get_option( 'lddfw_out_for_delivery_status' ):
                            if ( $status !== $lddfw_out_for_delivery_status_name ) {
                                $lddfw_out_for_delivery_status_name = $status;
                            }
                            break;
                        case get_option( 'lddfw_failed_attempt_status' ):
                            if ( $status !== esc_html( __( 'Failed Delivery Attempt', 'lddfw' ) ) ) {
                                $lddfw_failed_attempt_status_name = $status;
                            }
                            break;
                        case get_option( 'lddfw_driver_assigned_status' ):
                            if ( $status !== $lddfw_driver_assigned_status_name ) {
                                $lddfw_driver_assigned_status_name = $status;
                            }
                            break;
                    }
                }
            }
        }
        // Get the number of orders in each status.
        $lddfw_orders = new LDDFW_Orders();
        $lddfw_array = $lddfw_orders->lddfw_orders_count_query( $lddfw_driver_id );
        $lddfw_out_for_delivery_counter = 0;
        $lddfw_failed_attempt_counter = 0;
        $lddfw_delivered_counter = 0;
        $lddfw_assign_to_driver_counter = 0;
        $lddfw_claim_orders_counter = 0;
        foreach ( $lddfw_array as $row ) {
            switch ( $row->post_status ) {
                case get_option( 'lddfw_out_for_delivery_status' ):
                    $lddfw_out_for_delivery_counter = $row->orders;
                    break;
                case get_option( 'lddfw_failed_attempt_status' ):
                    $lddfw_failed_attempt_counter = $row->orders;
                    break;
                case get_option( 'lddfw_delivered_status' ):
                    $lddfw_delivered_counter = $row->orders;
                    break;
                case get_option( 'lddfw_driver_assigned_status' ):
                    $lddfw_assign_to_driver_counter = $row->orders;
                    break;
            }
        }
    }

    /**
     * Set the driver page or tracking page template using template_redirect.
     *
     * @since 1.0.0
     */
    public function lddfw_page_template_redirect() {
        // Removed $page_template argument
        global $post;
        if ( !empty( $post ) ) {
            $template_path = '';
            if ( $post->ID === intval( get_option( 'lddfw_delivery_drivers_page', '' ) ) ) {
                $this->lddfw_initialize_panel_data_globals();
                $template_path = WP_PLUGIN_DIR . '/' . LDDFW_FOLDER . '/index.php';
            } elseif ( $post->ID === intval( get_option( 'lddfw_tracking_page', '' ) ) ) {
                $template_path = WP_PLUGIN_DIR . '/' . LDDFW_FOLDER . '/tracking.php';
            }
            if ( !empty( $template_path ) && file_exists( $template_path ) ) {
                include $template_path;
                exit;
            }
        }
        // No return needed as we exit if a template is found and included.
    }

}
