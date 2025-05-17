<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LDDFW
 * @subpackage LDDFW/admin
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Admin {
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
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
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
        $page = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
        if ( 'lddfw-reports' === $page ) {
            wp_enqueue_style(
                'lddfw-jquery-ui',
                plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css',
                array(),
                $this->version,
                'all'
            );
        }
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/lddfw-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
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
        $script_array = array('jquery');
        $page = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
        if ( 'lddfw-reports' === $page ) {
            // Add date picker script.
            $script_array = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker');
        }
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/lddfw-admin.js',
            $script_array,
            $this->version,
            false
        );
        wp_localize_script( $this->plugin_name, 'lddfw_ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
        wp_localize_script( $this->plugin_name, 'lddfw_nonce', array(
            'nonce' => esc_js( wp_create_nonce( 'lddfw-nonce' ) ),
        ) );
    }

    /**
     * Service that update order status to out for delivery.
     *
     * @since 1.0.0
     * @param int $driver_id ID of the user.
     * @return json
     */
    public function lddfw_out_for_delivery_service( $driver_id ) {
        $result = 0;
        $error = __( 'An error occurred.', 'lddfw' );
        $user = new WP_User($driver_id, '', get_current_blog_id());
        if ( in_array( 'driver', (array) $user->roles, true ) ) {
            // Security check.
            if ( isset( $_POST['lddfw_wpnonce'] ) ) {
                // Get list of orders.
                $orders_list = ( isset( $_POST['lddfw_orders_list'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orders_list'] ) ) : '' );
                if ( '' !== $orders_list ) {
                    $orders_list_array = explode( ',', $orders_list );
                    foreach ( $orders_list_array as $order_id ) {
                        if ( '' !== $order_id ) {
                            $order = wc_get_order( $order_id );
                            $order_driverid = $order->get_meta( 'lddfw_driverid' );
                            $out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
                            $driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
                            $current_order_status = 'wc-' . $order->get_status();
                            // Check if order belongs to driver and status is processing.
                            if ( intval( $order_driverid ) === intval( $driver_id ) && $current_order_status === $driver_assigned_status ) {
                                // Update order status.
                                $order->update_status( $out_for_delivery_status, __( 'The delivery driver changed the order status.', 'lddfw' ) );
                                $order->save();
                                $result = 1;
                                $error = '<div class=\'alert alert-success alert-dismissible fade show\'>' . __( 'Orders successfully marked as out for delivery.', 'lddfw' ) . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div> <a id=\'view_out_of_delivery_orders_button\' href=\'' . lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) . '\'  class=\'btn btn-lg lddfw_loader btn-block btn-primary\'>' . __( 'View out for delivery orders', 'lddfw' ) . '</a>';
                            }
                        }
                    }
                } else {
                    $error = __( 'Please choose the orders.', 'lddfw' );
                }
            }
        } else {
            $error = __( 'User is not a delivery driver', 'lddfw' );
        }
        return "{\"result\":\"{$result}\",\"error\":\"{$error}\"}";
    }

    /**
     * The function that handles ajax requests.
     *
     * @since 1.0.0
     * @return void
     */
    public function lddfw_ajax() {
        $lddfw_data_type = ( isset( $_POST['lddfw_data_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_data_type'] ) ) : '' );
        $lddfw_obj_id = ( isset( $_POST['lddfw_obj_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_obj_id'] ) ) : '' );
        $lddfw_service = ( isset( $_POST['lddfw_service'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_service'] ) ) : '' );
        $lddfw_driver_id = ( isset( $_POST['lddfw_driver_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_id'] ) ) : '' );
        $result = 0;
        /**
         * Security check.
         */
        if ( !isset( $_POST['lddfw_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lddfw_wpnonce'] ) ), 'lddfw-nonce' ) ) {
            $error = esc_js( __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'lddfw' ) );
            if ( 'json' === $lddfw_data_type ) {
                echo "{\"result\":\"{$result}\",\"error\":\"{$error}\"}";
            } else {
                echo '<div class=\'alert alert-danger alert-dismissible fade show\'>' . $error . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div>';
            }
            exit;
        }
        /*
        	Edit driver service.
        */
        if ( 'lddfw_edit_driver' === $lddfw_service ) {
            $driver = new LDDFW_Driver();
            echo $driver->lddfw_edit_driver_service();
        }
        /* login driver service */
        if ( 'lddfw_login' === $lddfw_service ) {
            $login = new LDDFW_Login();
            echo $login->lddfw_login_driver();
        }
        /* send reset password link */
        if ( 'lddfw_forgot_password' === $lddfw_service ) {
            $password = new LDDFW_Password();
            echo $password->lddfw_reset_password();
        }
        /* Create a new password*/
        if ( 'lddfw_newpassword' === $lddfw_service ) {
            $password = new LDDFW_Password();
            echo $password->lddfw_new_password();
        }
        /*
        Log out driver.
        */
        if ( 'lddfw_logout' === $lddfw_service ) {
            LDDFW_Login::lddfw_logout();
        }
        /*
        	Check google keys service.
        */
        if ( 'lddfw_check_google_keys' === $lddfw_service ) {
            $user = wp_get_current_user();
            // Check if user is admin.
            if ( in_array( 'administrator', (array) $user->roles, true ) ) {
                echo lddfw_check_server_google_keys( $lddfw_obj_id );
            }
        }
        /*
        Set driver account status.
        */
        if ( 'lddfw_account_status' === $lddfw_service ) {
            $user = wp_get_current_user();
            $user_id = $user->ID;
            $user = new WP_User($user_id, '', get_current_blog_id());
            // Switch to driver user if administrator is logged in.
            if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
                $user = new WP_User($lddfw_driver_id, '', get_current_blog_id());
            }
            // Check if user has a driver role.
            if ( in_array( 'driver', (array) $user->roles, true ) ) {
                $driver_id = $user->ID;
                $account_status = ( isset( $_POST['lddfw_account_status'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_account_status'] ) ) : '' );
                update_user_meta( $driver_id, 'lddfw_driver_account', $account_status );
                $result = 1;
            }
            echo esc_html( $result );
        }
        /*
        Set driver availability.
        */
        if ( 'lddfw_availability' === $lddfw_service ) {
            $user = wp_get_current_user();
            $user_id = $user->ID;
            $user = new WP_User($user_id, '', get_current_blog_id());
            // Switch to driver user if administrator is logged in.
            if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
                $user = new WP_User($lddfw_driver_id, '', get_current_blog_id());
            }
            // Check if user has a driver role.
            if ( in_array( 'driver', (array) $user->roles, true ) ) {
                $driver_id = $user->ID;
                $availability = ( isset( $_POST['lddfw_availability'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_availability'] ) ) : '' );
                update_user_meta( $driver_id, 'lddfw_driver_availability', $availability );
                $result = 1;
            }
            echo esc_html( $result );
        }
        /* out for delivery service */
        if ( 'lddfw_out_for_delivery' === $lddfw_service ) {
            $user = wp_get_current_user();
            $user_id = $user->ID;
            $user = new WP_User($user_id, '', get_current_blog_id());
            // Switch to driver user if administrator is logged in.
            if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
                $user = new WP_User($lddfw_driver_id, '', get_current_blog_id());
            }
            // Check if user has a driver role.
            if ( in_array( 'driver', (array) $user->roles, true ) ) {
                $driver_id = $user->ID;
                echo $this->lddfw_out_for_delivery_service( $driver_id );
            }
        }
        if ( 'lddfw_status' === $lddfw_service ) {
            $user = wp_get_current_user();
            $user_id = $user->ID;
            $user = new WP_User($user_id, '', get_current_blog_id());
            // Switch to driver user if administrator is logged in.
            if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
                $user = new WP_User($lddfw_driver_id, '', get_current_blog_id());
            }
            // Check if user has a driver role.
            if ( in_array( 'driver', (array) $user->roles, true ) ) {
                $order_id = ( isset( $_POST['lddfw_order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_order_id'] ) ) : '' );
                $order_status = ( isset( $_POST['lddfw_order_status'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_order_status'] ) ) : '' );
                $driver_id = ( isset( $_POST['lddfw_driver_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_id'] ) ) : '' );
                $note = ( isset( $_POST['lddfw_note'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_note'] ) ) : '' );
                $signature = ( isset( $_POST['lddfw_signature'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_signature'] ) ) : '' );
                /* Check if the variables are not empty */
                if ( '' !== $order_id && '' !== $order_status && '' !== $driver_id ) {
                    $order = wc_get_order( $order_id );
                    $order_driverid = $order->get_meta( 'lddfw_driverid' );
                    $out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
                    $failed_attempt_status = get_option( 'lddfw_failed_attempt_status', '' );
                    $delivered_status = get_option( 'lddfw_delivered_status', '' );
                    $current_order_status = 'wc-' . $order->get_status();
                    /* Check if order belongs to driver and status is out for delivery */
                    if ( intval( $order_driverid ) === intval( $driver_id ) && ($current_order_status === $out_for_delivery_status || $current_order_status === $failed_attempt_status) ) {
                        /* Update order status */
                        $status_note = esc_html__( 'Driver changed the order status.', 'lddfw' );
                        if ( '' !== $note ) {
                            $driver_note = __( 'Driver note', 'lddfw' ) . ': ' . $note;
                            $order->update_meta_data( 'lddfw_driver_note', $note );
                            $order->add_order_note( $driver_note );
                        }
                        $order->save();
                        $order = wc_get_order( $order_id );
                        $order->update_status( $order_status, $status_note );
                        $result = 1;
                    }
                }
            }
            echo esc_html( $result );
        }
        exit;
    }

    /**
     * Changed status hook.
     *
     * @since 1.0.0
     * @param int    $order_id order number.
     * @param string $status_from order status from.
     * @param string $status_to order status to.
     * @param object $order order object.
     * @return void
     */
    public function lddfw_status_changed(
        $order_id,
        $status_from,
        $status_to,
        $order
    ) {
        // Insert order_id to sync table if not exist.
        if ( !lddfw_is_order_already_exists( $order_id ) ) {
            lddfw_insert_orderid_to_sync_order( $order_id );
        }
        // Update sync table.
        lddfw_update_all_sync_order( $order );
        // Get order delivery driver.
        $order_driverid = $order->get_meta( 'lddfw_driverid' );
        // Delete driver cache.
        lddfw_delete_cache( 'driver', $order_driverid );
        // Delete orders cache.
        lddfw_delete_cache( 'orders', '' );
        if ( get_option( 'lddfw_processing_status', true ) === 'wc-' . $status_to ) {
        }
        if ( get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $status_to ) {
        }
        if ( get_option( 'lddfw_delivered_status', '' ) === 'wc-' . $status_to ) {
            // Update delivered date.
            $order->update_meta_data( 'lddfw_delivered_date', date_i18n( 'Y-m-d H:i:s' ) );
            lddfw_update_sync_order( $order_id, 'lddfw_delivered_date', date_i18n( 'Y-m-d H:i:s' ) );
            if ( '' !== $order_driverid ) {
                // Delete route meta.
                $order->delete_meta_data( 'lddfw_order_origin' );
                $order->delete_meta_data( 'lddfw_order_sort' );
                lddfw_update_sync_order( $order_id, 'lddfw_order_sort', '0' );
            }
            $order->save();
        }
        if ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $status_to ) {
            // Update failed attempt date.
            $order->update_meta_data( 'lddfw_failed_attempt_date', date_i18n( 'Y-m-d H:i:s' ) );
            // Delete route meta.
            $order->delete_meta_data( 'lddfw_order_origin' );
            $order->delete_meta_data( 'lddfw_order_sort' );
            lddfw_update_sync_order( $order_id, 'lddfw_order_sort', '0' );
            $order->save();
        }
    }

    /**
     * Plugin status.
     *
     * @since 1.0.0
     * @param array $statuses_array status array.
     * @return array
     */
    public function lddfw_order_statuses( $statuses_array ) {
        $lddfw_statuses = array();
        foreach ( $statuses_array as $key => $status ) {
            $lddfw_statuses[$key] = $status;
            if ( 'wc-processing' === $key ) {
                $lddfw_statuses['wc-driver-assigned'] = __( 'Driver Assigned', 'lddfw' );
                $lddfw_statuses['wc-out-for-delivery'] = __( 'Out for Delivery', 'lddfw' );
                $lddfw_statuses['wc-failed-delivery'] = __( 'Failed Delivery Attempt', 'lddfw' );
            }
        }
        return $lddfw_statuses;
    }

    /**
     * Register new post status.
     *
     * @since 1.0.0
     * @return void
     */
    public function lddfw_order_statuses_init() {
        register_post_status( 'wc-out-for-delivery', array(
            'label'                     => __( 'Out for Delivery', 'lddfw' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'lddfw' ),
        ) );
        register_post_status( 'wc-failed-delivery', array(
            'label'                     => __( 'Failed Delivery Attempt', 'lddfw' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Failed Delivery Attempt <span class="count">(%s)</span>', 'Failed Delivery Attempt <span class="count">(%s)</span>', 'lddfw' ),
        ) );
        register_post_status( 'wc-driver-assigned', array(
            'label'                     => __( 'Driver Assigned', 'lddfw' ),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop( 'Driver Assigned <span class="count">(%s)</span>', 'Driver Assigned <span class="count">(%s)</span>', 'lddfw' ),
        ) );
    }

    function lddfw_sanitize_variables( $input ) {
        $new_input = array();
        if ( isset( $input['lddfw_proof_of_delivery_max_images'] ) ) {
            $new_input['lddfw_proof_of_delivery_max_images'] = absint( $input['lddfw_proof_of_delivery_max_images'] );
        }
        // Ensure the input is an array
        if ( !is_array( $input ) ) {
            return [];
        }
        // Sanitize each variable
        $sanitized = array_map( 'sanitize_text_field', $input );
        return $sanitized;
    }

    /**
     * Plugin register settings.
     *
     * @since 1.0.0
     * @return void
     */
    public function lddfw_settings_init() {
        // Get settings tab.
        $tab = ( isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '' );
        register_setting( 'lddfw', 'lddfw_google_api_key' );
        register_setting( 'lddfw', 'lddfw_google_api_key_server' );
        register_setting( 'lddfw', 'lddfw_dispatch_phone_number' );
        register_setting( 'lddfw', 'lddfw_status_section' );
        register_setting( 'lddfw', 'lddfw_driver_assigned_status' );
        register_setting( 'lddfw', 'lddfw_out_for_delivery_status' );
        register_setting( 'lddfw', 'lddfw_delivered_status' );
        register_setting( 'lddfw', 'lddfw_failed_attempt_status' );
        register_setting( 'lddfw', 'lddfw_processing_status' );
        register_setting( 'lddfw', 'lddfw_delivery_drivers_page' );
        register_setting( 'lddfw-drivers-settings', 'lddfw_failed_delivery_reason_1' );
        register_setting( 'lddfw-sms-settings', 'lddfw_sms_api_auth_token' );
        register_setting( 'lddfw-whatsapp-settings', 'lddfw_whatsapp_api_auth_token' );
        register_setting( 'lddfw-branding', 'lddfw_branding_logo' );
        register_setting( 'lddfw-tracking', 'lddfw_tracking_page' );
        register_setting( 'lddfw', 'lddfw_store_address_longitude' );
        register_setting( 'lddfw', 'lddfw_store_address_latitude' );
        /**
         * Update driver_assigned status if empty.
         * This update will be removed in the future versions.
         */
        $lddfw_driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
        if ( '' === $lddfw_driver_assigned_status ) {
            update_option( 'lddfw_driver_assigned_status', 'wc-driver-assigned' );
        }
        if ( 'lddfw-tracking' === $tab ) {
            // Tracking Settings.
            add_settings_section(
                'lddfw_tracking_section',
                '',
                '',
                'lddfw-tracking'
            );
            add_settings_field(
                'lddfw_tracking_page',
                __( 'Tracking page', 'lddfw' ),
                array($this, 'lddfw_tracking_page'),
                'lddfw-tracking',
                'lddfw_tracking_section'
            );
            add_settings_field(
                'lddfw_driver_info_permission',
                __( 'Customer permissions', 'lddfw' ),
                array($this, 'lddfw_driver_info_permission'),
                'lddfw-tracking',
                'lddfw_tracking_section'
            );
            add_settings_field(
                'lddfw_drivers_tracking_timing',
                __( 'Driver tracking', 'lddfw' ),
                array($this, 'lddfw_drivers_tracking_timing'),
                'lddfw-tracking',
                'lddfw_tracking_section'
            );
            add_settings_field(
                'lddfw_drivers_tracking_interval',
                __( 'Driver tracking interval', 'lddfw' ),
                array($this, 'lddfw_drivers_tracking_interval'),
                'lddfw-tracking',
                'lddfw_tracking_section'
            );
            add_settings_field(
                'lddfw_add_time_to_eta',
                __( 'Add minutes to the ETA', 'lddfw' ),
                array($this, 'lddfw_add_time_to_eta'),
                'lddfw-tracking',
                'lddfw_tracking_section'
            );
        }
        if ( '' === $tab ) {
            // General Settings.
            add_settings_section(
                'lddfw_setting_section',
                '',
                '',
                'lddfw'
            );
            add_settings_field(
                'lddfw_delivery_drivers_page',
                __( 'Delivery drivers page', 'lddfw' ),
                array($this, 'lddfw_delivery_drivers_page'),
                'lddfw',
                'lddfw_setting_section'
            );
            add_settings_field(
                'lddfw_google_api_key',
                __( 'Google API key', 'lddfw' ),
                array($this, 'lddfw_google_api_key'),
                'lddfw',
                'lddfw_setting_section'
            );
            add_settings_section(
                'lddfw_status_section',
                __( 'Delivery statuses', 'lddfw' ),
                '',
                'lddfw'
            );
            add_settings_field(
                'lddfw_driver_assigned_status',
                __( 'Driver assigned status', 'lddfw' ),
                array($this, 'lddfw_driver_assigned_status'),
                'lddfw',
                'lddfw_status_section'
            );
            add_settings_field(
                'lddfw_out_for_delivery_status',
                __( 'Out for delivery status', 'lddfw' ),
                array($this, 'lddfw_out_for_delivery_status'),
                'lddfw',
                'lddfw_status_section'
            );
            add_settings_field(
                'lddfw_delivered_status',
                __( 'Delivered status', 'lddfw' ),
                array($this, 'lddfw_delivered_status'),
                'lddfw',
                'lddfw_status_section'
            );
            add_settings_field(
                'lddfw_failed_attempt_status',
                __( 'Failed delivery attempt status', 'lddfw' ),
                array($this, 'lddfw_failed_attempt_status'),
                'lddfw',
                'lddfw_status_section'
            );
            add_settings_field(
                'lddfw_processing_status',
                __( 'Order processing status', 'lddfw' ),
                array($this, 'lddfw_processing_status'),
                'lddfw',
                'lddfw_status_section'
            );
            add_settings_section(
                'lddfw_pickup_section',
                __( 'Store address coordinates', 'lddfw' ),
                array($this, 'lddfw_pickup_section'),
                'lddfw'
            );
            add_settings_field(
                'lddfw_store_address_latitude',
                __( 'Latitude', 'lddfw' ),
                array($this, 'lddfw_store_address_latitude'),
                'lddfw',
                'lddfw_pickup_section'
            );
            add_settings_field(
                'lddfw_store_address_longitude',
                __( 'Longitude', 'lddfw' ),
                array($this, 'lddfw_store_address_longitude'),
                'lddfw',
                'lddfw_pickup_section'
            );
            add_settings_field(
                'lddfw_dispatch_phone_number',
                __( 'Dispatch phone number', 'lddfw' ),
                array($this, 'lddfw_dispatch_phone_number'),
                'lddfw',
                'lddfw_pickup_section'
            );
        }
        if ( 'lddfw-drivers-settings' === $tab ) {
            add_settings_section(
                'lddfw_delivery_panel_section',
                __( 'Drivers Panel', 'lddfw' ),
                '',
                'lddfw-drivers-settings'
            );
            add_settings_field(
                'lddfw_app_mode',
                __( 'Theme', 'lddfw' ),
                array($this, 'lddfw_app_mode'),
                'lddfw-drivers-settings',
                'lddfw_delivery_panel_section'
            );
            add_settings_field(
                'lddfw_navigation_app',
                __( 'Navigation APP', 'lddfw' ),
                array($this, 'lddfw_navigation_app'),
                'lddfw-drivers-settings',
                'lddfw_delivery_panel_section'
            );
            add_settings_field(
                'lddfw_driver_feature_permission',
                __( 'Driver permissions', 'lddfw' ),
                array($this, 'lddfw_driver_feature_permission'),
                'lddfw-drivers-settings',
                'lddfw_delivery_panel_section'
            );
            add_settings_section(
                'lddfw_proof_of_delivery',
                __( 'Proof of delivery', 'lddfw' ),
                '',
                'lddfw-drivers-settings'
            );
            add_settings_field(
                'lddfw_proof_of_delivery_signature_photo',
                __( 'Signature & Photo', 'lddfw' ),
                array($this, 'lddfw_proof_of_delivery_signature_photo'),
                'lddfw-drivers-settings',
                'lddfw_proof_of_delivery'
            );
            add_settings_field(
                'lddfw_proof_of_delivery_max_images',
                __( 'Maximum Images', 'lddfw' ),
                array($this, 'lddfw_proof_of_delivery_max_images'),
                'lddfw-drivers-settings',
                'lddfw_proof_of_delivery'
            );
            add_settings_section(
                'lddfw_delivery_notes',
                __( 'Ready notes for the drivers', 'lddfw' ),
                array($this, 'lddfw_delivery_notes_section'),
                'lddfw-drivers-settings'
            );
            add_settings_field(
                'lddfw_failed_delivery_reason_1',
                __( 'Failed delivery', 'lddfw' ),
                array($this, 'lddfw_failed_delivery_reason_1'),
                'lddfw-drivers-settings',
                'lddfw_delivery_notes'
            );
            add_settings_field(
                'lddfw_delivery_dropoff_1',
                __( 'Successful delivery', 'lddfw' ),
                array($this, 'lddfw_delivery_dropoff_1'),
                'lddfw-drivers-settings',
                'lddfw_delivery_notes'
            );
            add_settings_section(
                'lddfw_delivery_assign_section',
                __( 'Assign Drivers to Orders', 'lddfw' ),
                '',
                'lddfw-drivers-settings'
            );
            add_settings_field(
                'lddfw_self_assign_delivery_drivers',
                __( 'Drivers can claim orders', 'lddfw' ),
                array($this, 'lddfw_self_assign_delivery_drivers'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_field(
                'lddfw_self_assign_limitation',
                __( 'Claim orders limitation', 'lddfw' ),
                array($this, 'lddfw_self_assign_limitation'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_field(
                'lddfw_auto_assign_delivery_drivers',
                __( 'Auto-assign delivery drivers', 'lddfw' ),
                array($this, 'lddfw_auto_assign_delivery_drivers'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_field(
                'lddfw_auto_assign_method',
                __( 'Auto-assign method', 'lddfw' ),
                array($this, 'lddfw_auto_assign_method'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_field(
                'lddfw_auto_assign_suborders',
                __( 'Auto-assign drivers to suborders', 'lddfw' ),
                array($this, 'lddfw_auto_assign_suborders'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_field(
                'lddfw_driver_application',
                __( 'New drivers application form', 'lddfw' ),
                array($this, 'lddfw_driver_application'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_field(
                'lddfw_enable_virtual_items',
                __( 'Virtual items', 'lddfw' ),
                array($this, 'lddfw_enable_virtual_items'),
                'lddfw-drivers-settings',
                'lddfw_delivery_assign_section'
            );
            add_settings_section(
                'lddfw_delivery_commissions_section',
                __( 'Commissions', 'lddfw' ),
                '',
                'lddfw-drivers-settings'
            );
            add_settings_field(
                'lddfw_driver_commission_type',
                __( 'Driver commissions', 'lddfw' ),
                array($this, 'lddfw_driver_commission_type'),
                'lddfw-drivers-settings',
                'lddfw_delivery_commissions_section'
            );
        }
        if ( 'lddfw-whatsapp-settings' === $tab ) {
            add_settings_section(
                'lddfw_whatsapp_settings',
                __( 'WhatsApp Settings', 'lddfw' ),
                '',
                'lddfw-whatsapp-settings'
            );
            add_settings_field(
                'lddfw_whatsapp_provider',
                __( 'WhatsApp provider', 'lddfw' ),
                array($this, 'lddfw_whatsapp_provider'),
                'lddfw-whatsapp-settings',
                'lddfw_whatsapp_settings'
            );
            add_settings_field(
                'lddfw_whatsapp_api_sid',
                __( 'API SID', 'lddfw' ),
                array($this, 'lddfw_whatsapp_api_sid'),
                'lddfw-whatsapp-settings',
                'lddfw_whatsapp_settings'
            );
            add_settings_field(
                'lddfw_whatsapp_api_auth_token',
                __( 'API AUTH TOKEN', 'lddfw' ),
                array($this, 'lddfw_whatsapp_api_auth_token'),
                'lddfw-whatsapp-settings',
                'lddfw_whatsapp_settings'
            );
            add_settings_field(
                'lddfw_whatsapp_api_phone',
                __( 'WhatsApp phone number', 'lddfw' ),
                array($this, 'lddfw_whatsapp_api_phone'),
                'lddfw-whatsapp-settings',
                'lddfw_whatsapp_settings'
            );
            add_settings_field(
                'lddfw_whatsapp_assign_to_driver',
                __( 'WhatsApp to the driver', 'lddfw' ),
                array($this, 'lddfw_whatsapp_assign_to_driver'),
                'lddfw-whatsapp-settings',
                'lddfw_whatsapp_settings'
            );
            add_settings_field(
                'lddfw_whatsapp_out_for_delivery',
                __( 'WhatsApp to the customer', 'lddfw' ),
                array($this, 'lddfw_whatsapp_out_for_delivery'),
                'lddfw-whatsapp-settings',
                'lddfw_whatsapp_settings'
            );
        }
        if ( 'lddfw-sms-settings' === $tab ) {
            add_settings_section(
                'lddfw_sms_settings',
                __( 'SMS Settings', 'lddfw' ),
                '',
                'lddfw-sms-settings'
            );
            add_settings_field(
                'lddfw_sms_provider',
                __( 'SMS provider', 'lddfw' ),
                array($this, 'lddfw_sms_provider'),
                'lddfw-sms-settings',
                'lddfw_sms_settings'
            );
            add_settings_field(
                'lddfw_sms_api_sid',
                __( 'API SID', 'lddfw' ),
                array($this, 'lddfw_sms_api_sid'),
                'lddfw-sms-settings',
                'lddfw_sms_settings'
            );
            add_settings_field(
                'lddfw_sms_api_auth_token',
                __( 'API AUTH TOKEN', 'lddfw' ),
                array($this, 'lddfw_sms_api_auth_token'),
                'lddfw-sms-settings',
                'lddfw_sms_settings'
            );
            add_settings_field(
                'lddfw_sms_api_phone',
                __( 'SMS phone number', 'lddfw' ),
                array($this, 'lddfw_sms_api_phone'),
                'lddfw-sms-settings',
                'lddfw_sms_settings'
            );
            add_settings_field(
                'lddfw_sms_assign_to_driver',
                __( 'SMS to the driver', 'lddfw' ),
                array($this, 'lddfw_sms_assign_to_driver'),
                'lddfw-sms-settings',
                'lddfw_sms_settings'
            );
            add_settings_field(
                'lddfw_customer_sms',
                __( 'SMS to the customer', 'lddfw' ),
                array($this, 'lddfw_customer_sms'),
                'lddfw-sms-settings',
                'lddfw_sms_settings'
            );
        }
        if ( 'lddfw-branding' === $tab ) {
            add_settings_section(
                'lddfw_branding',
                __( 'Drivers initial screen', 'lddfw' ),
                '',
                'lddfw-branding'
            );
            add_settings_field(
                'lddfw_branding_logo',
                __( 'Logo', 'lddfw' ),
                array($this, 'lddfw_branding_logo'),
                'lddfw-branding',
                'lddfw_branding'
            );
            add_settings_field(
                'lddfw_branding_title',
                __( 'Title', 'lddfw' ),
                array($this, 'lddfw_branding_title'),
                'lddfw-branding',
                'lddfw_branding'
            );
            add_settings_field(
                'lddfw_branding_subtitle',
                __( 'Subtitle', 'lddfw' ),
                array($this, 'lddfw_branding_subtitle'),
                'lddfw-branding',
                'lddfw_branding'
            );
            add_settings_field(
                'lddfw_branding_background',
                __( 'Page background', 'lddfw' ),
                array($this, 'lddfw_branding_background'),
                'lddfw-branding',
                'lddfw_branding'
            );
            add_settings_field(
                'lddfw_branding_text_color',
                __( 'Text color', 'lddfw' ),
                array($this, 'lddfw_branding_text_color'),
                'lddfw-branding',
                'lddfw_branding'
            );
            add_settings_field(
                'lddfw_branding_button_color',
                __( 'Button text color', 'lddfw' ),
                array($this, 'lddfw_branding_button_color'),
                'lddfw-branding',
                'lddfw_branding'
            );
            add_settings_field(
                'lddfw_branding_button_background',
                __( 'Button background', 'lddfw' ),
                array($this, 'lddfw_branding_button_background'),
                'lddfw-branding',
                'lddfw_branding'
            );
        }
        do_action( 'lddfw_settings' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_pickup_section() {
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_store_address_latitude() {
        ?>
				<input type='text' class='regular-text' name='lddfw_store_address_latitude' value='<?php 
        echo esc_attr( get_option( 'lddfw_store_address_latitude', '' ) );
        ?>'>
				<p class="lddfw_description" id="lddfw_store_address_latitude-description">
					<?php 
        echo esc_html( __( 'e.g. 37.819722', 'lddfw' ) );
        ?>
				</p>
				<?php 
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_store_address_longitude() {
        ?>
			<input type='text' class='regular-text' name='lddfw_store_address_longitude' value='<?php 
        echo esc_attr( get_option( 'lddfw_store_address_longitude', '' ) );
        ?>'>
			<p class="lddfw_description" id="lddfw_store_address_longitude-description">
				<?php 
        echo esc_html( __( 'e.g. -122.478611', 'lddfw' ) );
        ?>
			</p>
		<?php 
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_title() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_subtitle() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_logo() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_background() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_text_color() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_button_color() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.1.2
     */
    public function lddfw_branding_button_background() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.0.0
     */
    public function lddfw_sms_api_sid() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings for customer SMS notifications.
     *
     * This function generates the settings sections for various SMS notifications sent to customers,
     * such as when the order is out for delivery, when delivery has started, upon delivery confirmation,
     * and upon notification of non-delivery. It utilizes the lddfw_generate_sms_settings method
     * to generate each settings section.
     *
     * @since 1.0.0
     */
    public function lddfw_customer_sms() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_self_assign_limitation() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_self_assign_delivery_drivers() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.1.2
     */
    public function lddfw_drivers_tracking_timing() {
        echo lddfw_admin_premium_feature( '' );
        ?>
		<p><?php 
        echo sprintf( esc_html( __( 'For more information about the tracking page, please %1$sclick here%2$s.', 'lddfw' ) ), '<a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/getting-started/tracking/" target="_blank">', '</a>' );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     */
    public function lddfw_drivers_tracking_interval() {
        echo lddfw_admin_premium_feature( '' );
        ?>
		<?php 
    }

    /**
     * Plugin settings.
     */
    public function lddfw_add_time_to_eta() {
        echo lddfw_admin_premium_feature( '' );
        ?>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.1.2
     */
    public function lddfw_driver_commission_type() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     */
    public function lddfw_app_mode() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_navigation_app() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.6.0
     */
    public function lddfw_driver_info_permission() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.6.0
     */
    public function lddfw_driver_feature_permission() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_auto_assign_suborders() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_driver_application() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.5.0
     */
    public function lddfw_auto_assign_method() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_auto_assign_delivery_drivers() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_enable_virtual_items() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings for SMS to assign order to driver.
     *
     * @since 1.0.0
     */
    public function lddfw_sms_assign_to_driver() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_sms_api_phone() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_sms_api_auth_token() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_sms_provider() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_assign_to_driver() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_api_phone() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_api_auth_token() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings input.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_api_sid() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_provider() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin whatsapp settings.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_out_for_delivery() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin whatsapp settings.
     *
     * @since 1.0.0
     */
    public function lddfw_whatsapp_start_delivery() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_delivery_notes_section() {
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_proof_of_delivery_signature_photo() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_failed_delivery_reason_1() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_delivery_dropoff_1() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_dispatch_phone_number() {
        ?>
		<input type='text' class='regular-text' name='lddfw_dispatch_phone_number' value='<?php 
        echo esc_attr( get_option( 'lddfw_dispatch_phone_number', '' ) );
        ?>'>
		<p class="description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'Drivers can call this number if they have questions about orders.', 'lddfw' ) );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_google_api_key() {
        ?>
		<p class="description" id="lddfw-gooogle-api-key-description"><?php 
        echo sprintf(
            esc_html( __( 'In order to use the Google Maps API, we need to create two keys for application restrictions purposes.%1$s For more information about how to create the Google API key %2$sclick here%3$s.', 'lddfw' ) ),
            '<br>',
            '<a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/getting-started/how-to-generate-and-set-google-maps-api-keys/" target="_blank">',
            '</a>'
        );
        ?></p>
		<p style="margin-top:20px">
			<input type='text' class='regular-text' name='lddfw_google_api_key' id='lddfw_google_api_key' value='<?php 
        echo esc_attr( get_option( 'lddfw_google_api_key', '' ) );
        ?>'><br>
			<span class="description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'Key for Maps Embed API, Maps JavaScript API, Directions API and Geocoding API. ( Application restrictions: HTTP referrers )', 'lddfw' ) );
        ?></span>
		</p>
		<p style="margin-top:20px">
			<input type='text' class='regular-text' name='lddfw_google_api_key_server' id='lddfw_google_api_key_server' value='<?php 
        echo esc_attr( get_option( 'lddfw_google_api_key_server', '' ) );
        ?>'><br>
			<span class="description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'Key for Maps Directions API, Distance Matrix API and Geocoding API. ( Application restrictions: IP addresses )', 'lddfw' ) );
        ?></span>
		</p>
		<p style="margin-top:20px">
			<a href="#" class="button button-secondary" data-loading="<?php 
        echo esc_attr( __( 'Loading...', 'lddfw' ) );
        ?>" data-title="<?php 
        echo esc_attr( __( 'Test results for Key', 'lddfw' ) );
        ?>" data-alert="<?php 
        echo esc_attr( __( 'Please enter both Google keys.', 'lddfw' ) );
        ?>" id="lddfw_check_google_keys"><?php 
        echo esc_html( __( 'Test Your Google Keys', 'lddfw' ) );
        ?></a>
		</p>
		<div id="lddfw_check_google_keys_wrap" style="display:none;"></div>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_processing_status() {
        $result = '';
        if ( function_exists( 'wc_get_order_statuses' ) ) {
            $result = wc_get_order_statuses();
        }
        ?>
		<select name='lddfw_processing_status'>
			<?php 
        if ( !empty( $result ) ) {
            foreach ( $result as $key => $status ) {
                ?>
					<option value="<?php 
                echo esc_attr( $key );
                ?>" <?php 
                selected( esc_attr( get_option( 'lddfw_processing_status', '' ) ), $key );
                ?>><?php 
                echo esc_html( $status );
                ?></option>
					<?php 
            }
        }
        ?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'The orders are ready for delivery and drivers are able to claim.', 'lddfw' ) );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_failed_attempt_status() {
        $result = '';
        if ( function_exists( 'wc_get_order_statuses' ) ) {
            $result = wc_get_order_statuses();
        }
        ?>
		<select name='lddfw_failed_attempt_status'>
			<?php 
        if ( !empty( $result ) ) {
            foreach ( $result as $key => $status ) {
                ?>
					<option value="<?php 
                echo esc_attr( $key );
                ?>" <?php 
                selected( esc_attr( get_option( 'lddfw_failed_attempt_status', '' ) ), $key );
                ?>><?php 
                echo esc_html( $status );
                ?></option>
					<?php 
            }
        }
        ?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'The delivery driver attempted to deliver but failed.', 'lddfw' ) );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_delivered_status() {
        $result = '';
        if ( function_exists( 'wc_get_order_statuses' ) ) {
            $result = wc_get_order_statuses();
        }
        ?>
		<select name='lddfw_delivered_status'>
			<?php 
        if ( !empty( $result ) ) {
            foreach ( $result as $key => $status ) {
                ?>
					<option value="<?php 
                echo esc_attr( $key );
                ?>" <?php 
                selected( esc_attr( get_option( 'lddfw_delivered_status', '' ) ), $key );
                ?>><?php 
                echo esc_attr( $status );
                ?></option>
					<?php 
            }
        }
        ?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'The shipment was delivered successfully.', 'lddfw' ) );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_driver_assigned_status() {
        $result = '';
        if ( function_exists( 'wc_get_order_statuses' ) ) {
            $result = wc_get_order_statuses();
        }
        ?>
		<select name='lddfw_driver_assigned_status'>
			<?php 
        if ( !empty( $result ) ) {
            foreach ( $result as $key => $status ) {
                ?>
					<option value="<?php 
                echo esc_attr( $key );
                ?>" <?php 
                selected( esc_attr( get_option( 'lddfw_driver_assigned_status', '' ) ), $key );
                ?>><?php 
                echo esc_html( $status );
                ?></option>
					<?php 
            }
        }
        ?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'The delivery driver was assigned to order.', 'lddfw' ) );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_out_for_delivery_status() {
        $result = '';
        if ( function_exists( 'wc_get_order_statuses' ) ) {
            $result = wc_get_order_statuses();
        }
        ?>
		<select name='lddfw_out_for_delivery_status'>
			<?php 
        if ( !empty( $result ) ) {
            foreach ( $result as $key => $status ) {
                ?>
					<option value="<?php 
                echo esc_attr( $key );
                ?>" <?php 
                selected( esc_attr( get_option( 'lddfw_out_for_delivery_status', '' ) ), $key );
                ?>><?php 
                echo esc_html( $status );
                ?></option>
					<?php 
            }
        }
        ?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php 
        echo esc_html( __( 'The delivery driver is about to deliver the shipment.', 'lddfw' ) );
        ?></p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_settings_section_callback() {
        echo esc_html( __( 'This Section Description', 'lddfw' ) );
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_settings() {
        // Default variables.
        $settings_title = esc_html( __( 'General Settings', 'lddfw' ) );
        // Get the current tab from the $_GET param.
        $current_tab = ( isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '' );
        // Tabs array.
        $tabs = array(array(
            'slug'  => '',
            'label' => esc_html( __( 'General settings', 'lddfw' ) ),
            'title' => esc_html( __( 'General settings', 'lddfw' ) ),
            'url'   => '?page=lddfw-settings',
        ));
        $premium_tabs = array(
            array(
                'slug'  => 'lddfw-drivers-settings',
                'label' => esc_html( __( 'Drivers settings', 'lddfw' ) ),
                'title' => esc_html( __( 'Drivers settings', 'lddfw' ) ),
                'url'   => '?page=lddfw-settings&tab=lddfw-drivers-settings',
            ),
            array(
                'slug'  => 'lddfw-sms-settings',
                'label' => esc_html( __( 'SMS settings', 'lddfw' ) ),
                'title' => esc_html( __( 'SMS settings', 'lddfw' ) ),
                'url'   => '?page=lddfw-settings&tab=lddfw-sms-settings',
            ),
            array(
                'slug'  => 'lddfw-whatsapp-settings',
                'label' => esc_html( __( 'WhatsApp settings', 'lddfw' ) ),
                'title' => esc_html( __( 'WhatsApp settings', 'lddfw' ) ),
                'url'   => '?page=lddfw-settings&tab=lddfw-whatsapp-settings',
            ),
            array(
                'slug'  => 'lddfw-branding',
                'label' => esc_html( __( 'Branding', 'lddfw' ) ),
                'title' => esc_html( __( 'Branding', 'lddfw' ) ),
                'url'   => '?page=lddfw-settings&tab=lddfw-branding',
            ),
            array(
                'slug'  => 'lddfw-tracking',
                'label' => esc_html( __( 'Tracking', 'lddfw' ) ),
                'title' => esc_html( __( 'Tracking', 'lddfw' ) ),
                'url'   => '?page=lddfw-settings&tab=lddfw-tracking',
            )
        );
        $tabs = array_merge( $tabs, $premium_tabs );
        // Tabs filter.
        if ( has_filter( 'lddfw_settings_tabs' ) ) {
            $tabs = apply_filters( 'lddfw_settings_tabs', $tabs );
        }
        foreach ( $tabs as $tab ) {
            if ( $current_tab === $tab['slug'] ) {
                $settings_title = $tab['title'];
                break;
            }
        }
        ?>
		<div class="wrap">
		<form action='options.php' method='post'>
			<h1 class="wp-heading-inline"><?php 
        echo $settings_title;
        ?></h1>
			<?php 
        echo self::lddfw_admin_plugin_bar();
        if ( 1 < count( $tabs ) ) {
            ?>
							<nav class="nav-tab-wrapper">
						<?php 
            foreach ( $tabs as $tab ) {
                $url = ( '' !== $tab['slug'] ? 'admin.php?page=lddfw-settings&tab=' . esc_attr( $tab['slug'] ) : 'admin.php?page=lddfw-settings' );
                echo '<a href="' . esc_html( admin_url( $url ) ) . '" class="nav-tab ' . (( $current_tab === $tab['slug'] ? 'nav-tab-active' : '' )) . '">' . esc_html( $tab['label'] ) . '</a>';
            }
            ?>
							</nav>
						<?php 
        }
        echo '<hr class="wp-header-end">';
        foreach ( $tabs as $tab ) {
            if ( '' === $current_tab ) {
                settings_fields( 'lddfw' );
                do_settings_sections( 'lddfw' );
                break;
            } elseif ( $current_tab === $tab['slug'] ) {
                settings_fields( $tab['slug'] );
                do_settings_sections( $tab['slug'] );
                break;
            }
        }
        submit_button();
        ?>
		</form>
	</div>
		<?php 
    }

    /**
     * Plugin submenu.
     *
     * @since 1.0.0
     * @return void
     */
    public function lddfw_admin_menu() {
        // add menu to main menu.
        add_menu_page(
            esc_html( __( 'Delivery Drivers Settings', 'lddfw' ) ),
            esc_html( __( 'Delivery Drivers', 'lddfw' ) ),
            'edit_pages',
            'lddfw-dashboard',
            array(&$this, 'lddfw_dashboard'),
            'dashicons-location',
            56
        );
        add_submenu_page(
            'lddfw-dashboard',
            esc_html( __( 'Dashboard', 'lddfw' ) ),
            esc_html( __( 'Dashboard', 'lddfw' ) ),
            'edit_pages',
            'lddfw-dashboard',
            array(&$this, 'lddfw_dashboard')
        );
        add_submenu_page(
            'lddfw-dashboard',
            esc_html( __( 'Routes', 'lddfw' ) ),
            esc_html( __( 'Routes', 'lddfw' ) ),
            'edit_pages',
            'lddfw-routes',
            array(&$this, 'lddfw_routes')
        );
        add_submenu_page(
            'lddfw-dashboard',
            esc_html( __( 'Reports', 'lddfw' ) ),
            esc_html( __( 'Reports', 'lddfw' ) ),
            'edit_pages',
            'lddfw-reports',
            array(&$this, 'lddfw_reports')
        );
        add_submenu_page(
            'lddfw-dashboard',
            esc_html( __( 'Settings', 'lddfw' ) ),
            esc_html( __( 'Settings', 'lddfw' ) ),
            'edit_pages',
            'lddfw-settings',
            array(&$this, 'lddfw_settings')
        );
        add_submenu_page(
            'lddfw-dashboard',
            esc_html( __( 'App', 'lddfw' ) ),
            esc_html( __( 'App', 'lddfw' ) ),
            'edit_pages',
            'lddfw-app',
            array(&$this, 'app')
        );
    }

    /**
     * Admin plugin bar.
     *
     * @since 1.1.0
     * @return html
     */
    public static function lddfw_admin_plugin_bar() {
        return '<div class="lddfw_admin_bar">' . esc_html( __( 'Developed by', 'lddfw' ) ) . ' <a href="https://powerfulwp.com/" target="_blank">PowerfulWP</a> | <a href="https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium/" target="_blank" >' . esc_html( __( 'Premium', 'lddfw' ) ) . '</a> | <a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/" target="_blank" >' . esc_html( __( 'Documents', 'lddfw' ) ) . '</a></div>';
    }

    /**
     * Plugin dashboard.
     *
     * @since 1.0.0
     */
    public function lddfw_dashboard() {
        $dashboard = new LDDFW_Reports();
        echo $dashboard->screen_dashboard();
    }

    /**
     * Plugin reports.
     *
     * @since 1.0.0
     */
    public function lddfw_reports() {
        $reports = new LDDFW_Reports();
        echo $reports->screen_reports();
    }

    /**
     * Admin routes screen.
     *
     * @since 1.0.0
     */
    public function lddfw_routes() {
        if ( lddfw_is_free() ) {
            $content = lddfw_admin_premium_feature( '' ) . ' ' . esc_html( __( "View drivers' routes on a map.", 'lddfw' ) ) . '
					<hr>' . lddfw_admin_premium_feature( '' ) . ' ' . esc_html( __( "View routes' duration and distance.", 'lddfw' ) ) . '
					<hr>
					' . esc_html( __( 'Upgrading to Premium will unlock it.', 'lddfw' ) ) . '
					<br><a target="_blank" href="https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium#pricing" class="lddfw_premium_buynow">' . esc_html( __( 'UNLOCK PREMIUM', 'lddfw' ) ) . '</a>
					<br>
					<img style="max-width:100%" src="' . plugins_url() . '/' . LDDFW_FOLDER . '/public/images/routes-preview.png?ver=' . LDDFW_VERSION . '">
					';
            echo lddfw_premium_feature_notice_content( $content );
        }
    }

    /**
     * Users list columns.
     *
     * @param array $column column.
     * @return array
     */
    public function lddfw_users_list_columns( $column ) {
        if ( isset( $_GET['role'] ) && 'driver' === $_GET['role'] ) {
            $column['lddfw_driver_availability'] = 'Availability';
            $column['lddfw_driver_claim'] = 'Claim orders';
            $column['lddfw_driver_account'] = 'Account';
        }
        return $column;
    }

    /**
     * Users list columns.
     *
     * @param string $val value.
     * @param string $column_name column name.
     * @param int    $user_id user id.
     * @since 1.1.2
     * @return html
     */
    public function lddfw_users_list_columns_raw( $val, $column_name, $user_id ) {
        $availability_icon = '';
        $driver_claim_icon = '';
        $driver_account_icon = '';
        switch ( $column_name ) {
            case 'lddfw_driver_availability':
                return lddfw_admin_premium_feature( $availability_icon );
            case 'lddfw_driver_claim':
                return lddfw_admin_premium_feature( $driver_claim_icon );
            case 'lddfw_driver_account':
                return lddfw_admin_premium_feature( $driver_account_icon );
            default:
        }
        return $val;
    }

    /**
     * Print driver name in column
     *
     * @param string $column column name.
     * @param int    $post_id post number.
     * @since 1.0.0
     */
    public function lddfw_orders_list_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'Driver':
                $order = wc_get_order( $post_id );
                $lddfw_driverid = $order->get_meta( 'lddfw_driverid' );
                $user = get_user_by( 'id', $lddfw_driverid );
                if ( !empty( $user ) ) {
                    echo esc_html( $user->display_name );
                }
                break;
        }
    }

    /**
     * Columns order
     *
     * @param array $columns columns array.
     * @since 1.0.0
     * @return array
     */
    public function lddfw_orders_list_columns_order( $columns ) {
        $reordered_columns = array();
        // Inserting columns to a specific location.
        foreach ( $columns as $key => $column ) {
            $reordered_columns[$key] = $column;
            if ( 'order_status' === $key ) {
                // Inserting after "Status" column.
                $reordered_columns['Driver'] = __( 'Driver', 'lddfw' );
            }
        }
        return $reordered_columns;
    }

    /**
     * Sortable columns
     *
     * @param array $columns columns array.
     * @since 1.0.0
     * @return array
     */
    public function lddfw_orders_list_sortable_columns( $columns ) {
        $columns['Driver'] = 'Driver';
        return $columns;
    }

    /**
     * Save user fields
     *
     * @since 1.0.0
     * @param int $user_id user id.
     */
    public function lddfw_user_fields_save( $user_id ) {
        // Capability Check: Ensure the current user can edit the target user.
        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return;
            // Use return; for action hooks.
        }
        // Verify User Existence: Ensure the user being edited actually exists.
        $user_meta = get_userdata( $user_id );
        if ( !$user_meta ) {
            // User doesn't exist, nothing to do.
            return;
        }
        $user_roles = (array) $user_meta->roles;
        // Determine Relevant Roles: Check if the user is a Driver or a configured Vendor.
        $is_driver = in_array( 'driver', $user_roles, true );
        $is_vendor = false;
        // Check if relevant roles exist. If not, no fields managed by this function need saving.
        if ( !$is_driver && !$is_vendor ) {
            return;
        }
        // Nonce Verification: Protect against CSRF attacks.
        // IMPORTANT: This assumes the nonce field 'lddfw_nonce_user' is added in lddfw_user_fields()
        // if the user being edited has *either* the 'driver' or 'vendor' role.
        // If the nonce is only added for 'driver', this check will incorrectly fail for vendor-only edits.
        $nonce_key = 'lddfw_nonce_user';
        if ( !isset( $_REQUEST[$nonce_key] ) ) {
            // Nonce field is missing. This could indicate an issue with the form generation
            // or an attempt to bypass security. Consider logging or adding an admin notice.
            return;
        }
        $retrieved_nonce = sanitize_text_field( wp_unslash( $_REQUEST[$nonce_key] ) );
        if ( !wp_verify_nonce( $retrieved_nonce, basename( __FILE__ ) ) ) {
            // Invalid nonce. This could be a CSRF attempt or an expired form.
            // Consider logging or adding an admin notice.
            return;
        }
        // Save driver settings.
        if ( $is_driver ) {
            $lddfw_driver_account = ( isset( $_POST['lddfw_driver_account'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_account'] ) ) : '' );
            $lddfw_driver_availability = ( isset( $_POST['lddfw_driver_availability'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_availability'] ) ) : '' );
            update_user_meta( $user_id, 'lddfw_driver_account', $lddfw_driver_account );
            update_user_meta( $user_id, 'lddfw_driver_availability', $lddfw_driver_availability );
        }
    }

    /**
     * Get user fields
     *
     * @since 1.0.0
     * @param object $user user data object.
     */
    public function lddfw_user_fields( $user ) {
        if ( in_array( 'driver', (array) $user->roles, true ) ) {
            wp_nonce_field( basename( __FILE__ ), 'lddfw_nonce_user' );
            ?>
			<h3><?php 
            echo esc_html( __( 'Delivery Driver Info', 'lddfw' ) );
            ?></h3>
			<table class="form-table">
			<tr>
					<th><label for="lddfw_driver_account"><?php 
            echo esc_html( __( 'Driver account status', 'lddfw' ) );
            ?></label></th>
					<td>
						<select name="lddfw_driver_account" id="lddfw_driver_account">
							<option value="0"><?php 
            echo esc_html( __( 'Not active', 'lddfw' ) );
            ?></option>
							<?php 
            $selected = ( get_user_meta( $user->ID, 'lddfw_driver_account', true ) === '1' ? 'selected' : '' );
            ?>
							<option <?php 
            echo esc_attr( $selected );
            ?> value="1"><?php 
            echo esc_html( __( 'Active', 'lddfw' ) );
            ?></option>
						</select>
						<p class="lddfw_description"><?php 
            echo esc_html( __( 'Only drivers with active accounts can access the drivers\' panel.', 'lddfw' ) );
            ?></p>
					</td>
			</tr>
			<tr>
					<th><label for="lddfw_driver_availability"><?php 
            echo esc_html( __( 'Driver availability', 'lddfw' ) );
            ?></label></th>
					<td>
						<select name="lddfw_driver_availability" id="lddfw_driver_availability">
							<option value="0"><?php 
            echo esc_html( __( 'Unavailable', 'lddfw' ) );
            ?></option>
							<?php 
            $selected = ( get_user_meta( $user->ID, 'lddfw_driver_availability', true ) === '1' ? 'selected' : '' );
            ?>
							<option <?php 
            echo esc_attr( $selected );
            ?> value="1"><?php 
            echo esc_html( __( 'Available', 'lddfw' ) );
            ?></option>
						</select>
						<p class="lddfw_description"><?php 
            echo esc_html( __( 'The delivery driver availability for work today.', 'lddfw' ) );
            ?></p>
					</td>
			</tr>
			<tr>
					<th><label for="lddfw_driver_app_mode"><?php 
            echo esc_html( __( 'Driver panel theme', 'lddfw' ) );
            ?></label></th>
					<td>
							<?php 
            $html = '';
            echo lddfw_admin_premium_feature( $html );
            ?>
					</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_travel_mode"><?php 
            echo esc_html( __( 'Transportation Mode', 'lddfw' ) );
            ?></label></th>
				<td>
					<?php 
            $html = '';
            echo lddfw_admin_premium_feature( $html );
            ?>
				</td>
			</tr>
			 
			<tr>
					<th><label for="lddfw_driver_claim"><?php 
            echo esc_html( __( 'Driver can claim orders', 'lddfw' ) );
            ?></label></th>
					<td>
					<?php 
            $html = '';
            echo lddfw_admin_premium_feature( $html );
            ?>

					</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_image"><?php 
            echo esc_html( __( 'Driver Photo', 'lddfw' ) );
            ?></label></th>
				<td>
					<?php 
            $html = '';
            echo lddfw_admin_premium_feature( $html );
            ?>
				</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_vehicle"><?php 
            echo esc_html( __( 'Vehicle type', 'lddfw' ) );
            ?></label></th>
				<td>
					<?php 
            $html = '';
            echo lddfw_admin_premium_feature( $html );
            ?>
				</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_licence_plate"><?php 
            echo esc_html( __( 'License Plate', 'lddfw' ) );
            ?></label></th>
				<td>
				<?php 
            $html = '';
            echo lddfw_admin_premium_feature( $html );
            ?>
				</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_cities"><?php 
            echo esc_html( __( 'Driver Cities', 'lddfw' ) );
            ?></label></th>
				<td>
					<?php 
            $html = '';
            // Use the premium feature output function
            echo lddfw_admin_premium_feature( $html );
            ?>
				</td>
			</tr>
			</table>

			<?php 
            // Add action.
            do_action( 'lddfw_driver_fields', $user );
            ?>
			<?php 
        }
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_delivery_drivers_page() {
        $args = array(
            'sort_order'   => 'asc',
            'sort_column'  => 'post_title',
            'hierarchical' => 1,
            'exclude'      => '',
            'include'      => '',
            'meta_key'     => '',
            'meta_value'   => '',
            'authors'      => '',
            'child_of'     => 0,
            'parent'       => -1,
            'exclude_tree' => '',
            'number'       => '',
            'offset'       => 0,
            'post_type'    => 'page',
            'post_status'  => 'publish',
        );
        $pages = get_pages( $args );
        ?>
		<select name='lddfw_delivery_drivers_page'>
			<?php 
        if ( !empty( $pages ) ) {
            foreach ( $pages as $page ) {
                $page_id = $page->ID;
                $page_title = $page->post_title;
                ?>
					<option value="<?php 
                echo esc_attr( $page_id );
                ?>" <?php 
                selected( esc_attr( get_option( 'lddfw_delivery_drivers_page', '' ) ), $page_id );
                ?>><?php 
                echo esc_html( $page_title );
                ?></option>
					<?php 
            }
        }
        ?>
		</select>
		<p class="lddfw_description" id="lddfw-driver_app-description">
		<?php 
        echo '<div class="driver_app">
				<img alt="' . esc_attr__( 'Drivers app', 'lddfw' ) . '" title="' . esc_attr__( 'Drivers app', 'lddfw' ) . '" src="' . esc_attr( plugins_url() . '/' . LDDFW_FOLDER . '/public/images/drivers_app.png?ver=' . LDDFW_VERSION ) . '">
				<p>
					<b><a target="_blank" href="' . lddfw_drivers_page_url( '' ) . '">' . lddfw_drivers_page_url( '' ) . '</a></b><br>' . sprintf( esc_html( __( 'The link above is the delivery driver\'s Mobile-Friendly panel URL. %1$s The delivery drivers can access it from their mobile phones. %2$s', 'lddfw' ) ), '<br>', '<br>' ) . sprintf(
            esc_html( __( 'Notice: If you want to be logged in as an administrator and to check the drivers\' panel on the same device, %1$s %2$syou must work with two different browsers otherwise you will log out from the admin panel and the drivers\' panel won\'t function correctly.%3$s', 'lddfw' ) ),
            '<br>',
            '<b>',
            '</b>'
        ) . '
				</p>
			</div>';
        ?>
		</p>
		<?php 
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_tracking_page() {
        echo lddfw_admin_premium_feature( '' );
    }

    /**
     * Exclude custom fields.
     *
     * @param array  $protected fields array.
     * @param string $meta_key meta key.
     * @since 1.3.0
     * @return array
     */
    public function lddfw_exclude_custom_fields( $protected, $meta_key ) {
        if ( in_array( $meta_key, lddfw_allow_protected_order_custom_fields(), true ) ) {
            return false;
        }
        if ( in_array( $meta_key, array(
            'lddfw_driver_commission',
            'lddfw_order_route',
            'lddfw_order_last_delivery_image',
            'lddfw_order_delivery_image',
            'lddfw_order_last_signature',
            'lddfw_order_signature',
            'lddfw_failed_attempt_date',
            'lddfw_delivered_date',
            'lddfw_driverid'
        ), true ) ) {
            return true;
        }
        return $protected;
    }

    /**
     * Displays the app promotion banner or modified content if the add-on plugin is active.
     *
     * This function checks whether the "App for Delivery Drivers Premium" plugin is active.
     * If the plugin is active and a filter `lddfw_app_settings_content` is available, it applies the filter 
     * to allow modification of the content. Otherwise, it displays a default promotional banner linking to the app page.
     *
     * @since 1.9.7
     * @return void Outputs the promotional content or modified content.
     */
    public function app() {
        $addon_plugin_path = 'app-for-delivery-drivers-premium/app-for-delivery-drivers.php';
        $content = '<br><a target="_blank" href="https://powerfulwp.com/app-for-delivery-drivers"><img style="max-width:100%" src="' . plugins_url() . '/' . LDDFW_FOLDER . '/public/images/app.gif?ver=' . LDDFW_VERSION . '"></a>';
        if ( is_plugin_active( $addon_plugin_path ) && has_filter( 'lddfw_app_settings_content' ) ) {
            echo apply_filters( 'lddfw_app_settings_content', $content );
        } else {
            echo $content;
        }
    }

    /**
     * Plugin settings.
     *
     * @since 1.0.0
     */
    public function lddfw_proof_of_delivery_max_images() {
        echo lddfw_admin_premium_feature( '' );
    }

}
