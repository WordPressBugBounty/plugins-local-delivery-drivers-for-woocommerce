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
class LDDFW_Driver {
    /**
     * Drivers query
     *
     * @since 1.0.0
     * @return array
     */
    public static function lddfw_get_drivers() {
        $args = array(
            'role'           => 'driver',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'lddfw_driver_availability',
                    'compare' => 'NOT EXISTS',
                    'value'   => '',
                ),
                array(
                    'key'     => 'lddfw_driver_availability',
                    'compare' => 'EXISTS',
                ),
            ),
            'orderby'        => 'meta_value ASC,display_name ASC',
            'posts_per_page' => -1,
        );
        return get_users( $args );
    }

    /**
     *  Get driver driving mode
     *
     * @param int    $driver_id The driver ID.
     * @param string $type mode type.
     * @return string
     */
    public static function get_driver_driving_mode( $driver_id, $type ) {
        $driving_mode = 'DRIVING';
        $driving_mode = ( 'lowercase' === $type ? strtolower( $driving_mode ) : $driving_mode );
        return $driving_mode;
    }

    /**
     *  Assign delivery order
     *
     * @param int    $order_id The order ID.
     * @param int    $driver_id The driver ID.
     * @param string $operator The type.
     * @return void
     */
    public static function assign_delivery_driver( $order_id, $driver_id, $operator ) {
        $order = wc_get_order( $order_id );
        if ( false !== $order ) {
            $order_driverid = $order->get_meta( 'lddfw_driverid' );
            // Delete driver cache.
            lddfw_delete_cache( 'driver', $order_driverid );
            // Delete orders cache.
            lddfw_delete_cache( 'orders', '' );
            $driver = get_userdata( $driver_id );
            if ( !empty( $driver ) && $driver_id !== $order_driverid && '-1' !== $driver_id && '' !== $driver_id ) {
                // Delete driver cache.
                lddfw_delete_cache( 'driver', $driver_id );
                $driver_name = $driver->display_name;
                $note = __( 'Delivery driver has been assigned to order.', 'lddfw' );
                $user_note = '';
                // Update order driver.
                $order->update_meta_data( 'lddfw_driverid', $driver_id );
                $order->save();
                lddfw_update_sync_order( $order_id, 'lddfw_driverid', $driver_id );
                // Update assigned date.
                update_user_meta( $driver_id, 'lddfw_assigned_date', date_i18n( 'Y-m-d H:i:s' ) );
                /**
                 * Update order status to driver assigned.
                 */
                $lddfw_driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
                $lddfw_processing_status = get_option( 'lddfw_processing_status', '' );
                $current_order_status = 'wc-' . $order->get_status();
                if ( '' !== $lddfw_driver_assigned_status && $current_order_status === $lddfw_processing_status ) {
                    $order->update_status( $lddfw_driver_assigned_status, '' );
                }
                /**
                 * Fires after a delivery driver has been assigned to an order.
                 *
                 * This action allows developers to perform additional tasks when a delivery driver is assigned.
                 *
                 * @param int      $order_id   The ID of the order to which the driver is assigned.
                 * @param WC_Order $order      The order object.
                 * @param string   $operator   Indicates who assigned the driver to the order.
                 *                             Possible values are 'store' or 'driver'.
                 * @param int      $driver_id  The ID of the assigned delivery driver.
                 */
                do_action(
                    'lddfw_assigned_delivery_driver_to_order',
                    $order_id,
                    $order,
                    $operator,
                    $driver_id
                );
                $order->add_order_note( $note );
            }
        }
    }

    /**
     * Edit driver form
     *
     * @since 1.5.0
     * @param int $driver_id The driver_id.
     * @return array
     */
    public function lddfw_edit_driver_form( $driver_id ) {
        global $lddfw_wpnonce;
        $user_meta = get_userdata( $driver_id );
        $first_name = $user_meta->first_name;
        $last_name = $user_meta->last_name;
        $email = $user_meta->user_email;
        $billing_country = $user_meta->billing_country;
        $phone = $user_meta->billing_phone;
        $city = $user_meta->billing_city;
        $company = $user_meta->billing_company;
        $address_1 = $user_meta->billing_address_1;
        $address_2 = $user_meta->billing_address_2;
        $postcode = $user_meta->billing_postcode;
        $billing_state = $user_meta->billing_state;
        $html = '<form service="lddfw_edit_driver" class="lddfw_form" id="driver_form" ><div class="container">
			<div class="row">
				<div class="col-12">';
        $driver_id = $user_meta->ID;
        $html .= '
									<input type="hidden" name="lddfw_driverid" value="' . $driver_id . '">
									<input type="hidden" name="lddfw_wpnonce" id="lddfw_wpnonce" value="' . $lddfw_wpnonce . '">
									<div class="lddfw_alert_wrap"></div>
									<div class="lddfw_wrap">
								';
        $html .= '<div class="lddfw_box">
								  <h3 class="lddfw_title">' . esc_html( __( 'Delivery Settings', 'lddfw' ) ) . '</h3>';
        // Availability.
        $html .= '<div class=" form-group row   availability">
								<label class="col-9 availability-text col-form-label">' . esc_html( __( 'I am', 'lddfw' ) );
        $lddfw_driver_availability = get_user_meta( $driver_id, 'lddfw_driver_availability', true );
        if ( '1' === $lddfw_driver_availability ) {
            $html .= '
										<span id="lddfw_availability_status" available="' . esc_attr( __( 'Available', 'lddfw' ) ) . '" unavailable="' . esc_attr( __( 'Unavailable', 'lddfw' ) ) . '">' . esc_html( __( 'Available', 'lddfw' ) ) . '</span>
										</label>
										<div class="col-3 text-right">
											<a id="lddfw_availability" class="lddfw_active" title="' . esc_attr( __( 'Availability status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
											<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg></a>
										</div>
										';
        } else {
            $html .= '
										<span id="lddfw_availability_status" available="' . esc_attr( __( 'Available', 'lddfw' ) ) . '" unavailable="' . esc_attr( __( 'Unavailable', 'lddfw' ) ) . '">' . esc_html( __( 'Unavailable', 'lddfw' ) ) . '</span>
										</label>
										<div class="col-3 text-right">
											<a id="lddfw_availability" class="" title="' . esc_attr( __( 'Availability status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
											<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg></a>
										</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="lddfw_box">
						<h3 class="lddfw_title">' . esc_html( __( 'Contact Info', 'lddfw' ) ) . '</h3>';
        $html .= '<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="lddfw_first_name">' . esc_html( __( 'First name', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="lddfw_first_name" value="' . $first_name . '" class="form-control reqi" id="lddfw_first_name"  placeholder="' . esc_html( __( 'First name', 'lddfw' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="lddfw_last_name">' . esc_html( __( 'Last name', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="lddfw_last_name" value="' . $last_name . '" class="form-control" id="lddfw_last_name"  placeholder="' . esc_html( __( 'Last name', 'lddfw' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="lddfw_company">' . esc_html( __( 'Company', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="lddfw_company" value="' . $company . '" class="form-control" id="lddfw_company"  placeholder="' . esc_html( __( 'Company', 'lddfw' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="lddfw_address_1">' . esc_html( __( 'Address line 1', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="lddfw_address_1" value="' . $address_1 . '" class="form-control" id="lddfw_address_1"  placeholder="' . esc_html( __( 'Address line 1', 'lddfw' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
							<label  class="col-sm-2 col-form-label" for="lddfw_address_2">' . esc_html( __( 'Address line 2', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
							<input type="text" name="lddfw_address_2" value="' . $address_2 . '" class="form-control" id="lddfw_address_2"  placeholder="' . esc_html( __( 'Address line 2', 'lddfw' ) ) . '">
							</div>
						</div>
						<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="lddfw_city">' . esc_html( __( 'City', 'lddfw' ) ) . '</label>
						<div class="col-sm-10">
						<input type="text" name="lddfw_city" value="' . $city . '" class="form-control" id="lddfw_city"  placeholder="' . esc_html( __( 'City', 'lddfw' ) ) . '">
						</div>
					</div>
					<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="lddfw_postcode">' . esc_html( __( 'Postcode / ZIP', 'lddfw' ) ) . '</label>
						<div class="col-sm-10">
						<input type="text" name="lddfw_postcode" value="' . $postcode . '" class="form-control" id="lddfw_postcode"  placeholder="' . esc_html( __( 'Postcode / ZIP', 'lddfw' ) ) . '">
						</div>
					</div>

						';
        global $woocommerce;
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->__get( 'countries' );
        $default_country = $countries_obj->get_base_country();
        $default_county_states = $countries_obj->get_states( 'US' );
        $html .= '<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="lddfw_country">' . esc_html( __( 'Country / Region', 'lddfw' ) ) . '</label>';
        $html .= '<div class="col-sm-10"><select id="billing_country" name="lddfw_country" class="form-control">
							<option value="">' . esc_html( __( 'Select Country / Region', 'lddfw' ) ) . '</option>';
        foreach ( $countries as $key => $country ) {
            $html .= '<option value="' . $key . '" ' . selected( $billing_country, $key, false ) . ' >' . $country . '</option>';
        }
        $html .= '</select>';
        $html .= '</div></div>';
        $html .= '<div class="form-group row">
								  <label class="col-sm-2 col-form-label" for="billing_state_select">' . esc_html( __( 'State / County', 'lddfw' ) ) . '</label>';
        $html .= '<div class="col-sm-10"><select style="display:none" id="billing_state_select" name="billing_state_select" class="form-control">
									<option value="">' . esc_html( __( 'Select State / County', 'lddfw' ) ) . '</option>';
        foreach ( $default_county_states as $key => $state ) {
            $html .= '<option value="' . $key . '" ' . selected( $billing_state, $key, false ) . ' >' . $state . '</option>';
        }
        $html .= '</select>
								  <input type="text" style="display:none" class="form-control" id="billing_state_input"  placeholder="' . esc_html( __( 'State / County', 'lddfw' ) ) . '" value="' . esc_attr( $billing_state ) . '" name="billing_state">';
        $html .= '</div></div>';
        $html .= '<div class="form-group row">
						<label  class="col-sm-2 col-form-label" for="lddfw_phone">' . esc_html( __( 'Phone number', 'lddfw' ) ) . '</label>
						<div class="col-sm-10">
						<input type="text" name="lddfw_phone" value="' . $phone . '" class="form-control" id="lddfw_phone" placeholder="' . esc_html( __( 'Phone number', 'lddfw' ) ) . '">
						</div>
					</div>
					</div>
					';
        $html .= '<div class="lddfw_box">
					<h3 class="lddfw_title">' . esc_html( __( 'Account', 'lddfw' ) ) . '</h3>';
        // Email.
        $html .= '<div class="form-group row">
							<label class="col-sm-2 col-form-label"  for="lddfw_email">' . esc_html( __( 'Email address', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
							<input type="email" name="lddfw_email"  value="' . $email . '"  class="form-control" id="lddfw_email" placeholder="' . esc_html( __( 'Enter email', 'lddfw' ) ) . '">
							</div>
						</div>';
        // Password.
        $html .= '<div class="form-group row">
							<label class="col-sm-2 col-form-label"  for="lddfw_password">' . esc_html( __( 'Password', 'lddfw' ) ) . '</label>
							<div class="col-sm-10">
								<button type="button" id="new_password_button" class="btn btn-secondary">' . esc_html( __( 'Set New Password', 'lddfw' ) ) . '</button>
								<div class = "row" id = "lddfw_password_holder" style = "display:none" >
									<div class="col-6">
										<input type="text" name="lddfw_password" id="lddfw_password"  value="" class="form-control" id="lddfw_password" placeholder="' . esc_html( __( 'Enter password', 'lddfw' ) ) . '">
									</div>
									<div class="col-6">
										<button type="button" id="cancel_password_button" class="btn btn-secondary">' . esc_html( __( 'Cancel', 'lddfw' ) ) . '</button>
									</div>
								</div>
							</div>
						</div>';
        $html .= '</div>';
        $html .= '
						</div></div></div></div>';
        // Buttons.
        $html .= '<div class="lddfw_footer_buttons">
							<div class="container">
								<div class="row">
									<div class="col-12">
							<button class="lddfw_submit_btn btn btn-lg btn-primary btn-block" type="submit">
							' . esc_html( __( 'Update', 'lddfw' ) ) . '
							</button>
							<button style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
							<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
							' . esc_html( __( 'Loading', 'lddfw' ) ) . '
							</button>
							</div>
							</div>
							</div>
							</div>
					</form>';
        $html .= '
		 ';
        return $html;
    }

    /**
     * Edit driver
     *
     * @since 1.5.0
     * @return array
     */
    public function lddfw_edit_driver_service() {
        $error = '';
        $result = '0';
        $new_nonce = '';
        // Security check.
        if ( isset( $_POST['lddfw_wpnonce'] ) ) {
            $email = ( isset( $_POST['lddfw_email'] ) ? sanitize_email( wp_unslash( $_POST['lddfw_email'] ) ) : '' );
            $first_name = ( isset( $_POST['lddfw_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_first_name'] ) ) : '' );
            $last_name = ( isset( $_POST['lddfw_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_last_name'] ) ) : '' );
            $phone = ( isset( $_POST['lddfw_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_phone'] ) ) : '' );
            $country = ( isset( $_POST['lddfw_country'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_country'] ) ) : '' );
            $company = ( isset( $_POST['lddfw_company'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_company'] ) ) : '' );
            $address_1 = ( isset( $_POST['lddfw_address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_address_1'] ) ) : '' );
            $address_2 = ( isset( $_POST['lddfw_address_2'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_address_2'] ) ) : '' );
            $city = ( isset( $_POST['lddfw_city'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_city'] ) ) : '' );
            $postcode = ( isset( $_POST['lddfw_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_postcode'] ) ) : '' );
            $password = ( isset( $_POST['lddfw_password'] ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_password'] ) ) : '' );
            $state = ( isset( $_POST['billing_state'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_state'] ) ) : '' );
            // Get the current logged-in user's ID
            $driver_id = get_current_user_id();
            if ( empty( $driver_id ) || !user_can( $driver_id, 'driver' ) ) {
                $error = __( 'User is not a driver.', 'lddfw' );
            } else {
                // Check for empty fields.
                if ( '' === $email ) {
                    // No email.
                    $error = __( 'The email field is empty.', 'lddfw' );
                } else {
                    if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                        // Invalid Email.
                        $error = __( 'The email is invalid.', 'lddfw' );
                    } else {
                        // Email exist for another user.
                        $user = get_user_by( 'email', $email );
                        $user_id = $user->data->ID;
                        if ( $user && (string) $user_id !== (string) $driver_id ) {
                            $error = __( 'Email exist for another user.', 'lddfw' );
                        } else {
                            if ( '' === $first_name ) {
                                $error = __( 'First name is empty.', 'lddfw' );
                            } else {
                                if ( '' === $last_name ) {
                                    $error = __( 'Last name is empty.', 'lddfw' );
                                } else {
                                    if ( '' === $phone ) {
                                        $error = __( 'Phone is empty.', 'lddfw' );
                                    } else {
                                        if ( '' === $address_1 ) {
                                            $error = __( 'Address 1 is empty.', 'lddfw' );
                                        } else {
                                            if ( '' === $city ) {
                                                $error = __( 'City is empty.', 'lddfw' );
                                            } else {
                                                if ( '' === $country ) {
                                                    $error = __( 'Country is empty.', 'lddfw' );
                                                } else {
                                                    wp_update_user( array(
                                                        'ID'         => $driver_id,
                                                        'first_name' => $first_name,
                                                        'last_name'  => $last_name,
                                                        'user_email' => $email,
                                                        'nickname'   => $first_name . ' ' . $last_name,
                                                    ) );
                                                    update_user_meta( $driver_id, 'billing_first_name', $first_name );
                                                    update_user_meta( $driver_id, 'billing_last_name', $last_name );
                                                    update_user_meta( $driver_id, 'billing_company', $company );
                                                    update_user_meta( $driver_id, 'billing_address_1', $address_1 );
                                                    update_user_meta( $driver_id, 'billing_address_2', $address_2 );
                                                    update_user_meta( $driver_id, 'billing_postcode', $postcode );
                                                    update_user_meta( $driver_id, 'billing_city', $city );
                                                    update_user_meta( $driver_id, 'billing_state', $state );
                                                    update_user_meta( $driver_id, 'billing_phone', $phone );
                                                    update_user_meta( $driver_id, 'billing_country', $country );
                                                    wp_update_user( array(
                                                        'ID'           => $driver_id,
                                                        'display_name' => "{$first_name} {$last_name}",
                                                    ) );
                                                    if ( '' !== $password ) {
                                                        // Change password.
                                                        wp_set_password( $password, $driver_id );
                                                        // Log user again.
                                                        LDDFW_Login::lddfw_user_login( $user, $password );
                                                        $_set_cookies = true;
                                                        // for the closures.
                                                        // Set the (secure) auth cookie immediately.
                                                        add_action(
                                                            'set_auth_cookie',
                                                            function (
                                                                $auth_cookie,
                                                                $a,
                                                                $b,
                                                                $c,
                                                                $scheme
                                                            ) use($_set_cookies) {
                                                                if ( $_set_cookies ) {
                                                                    $_COOKIE[( 'secure_auth' === $scheme ? SECURE_AUTH_COOKIE : AUTH_COOKIE )] = $auth_cookie;
                                                                }
                                                            },
                                                            10,
                                                            5
                                                        );
                                                        // Set the logged-in cookie immediately.
                                                        add_action( 'set_logged_in_cookie', function ( $logged_in_cookie ) use($_set_cookies) {
                                                            if ( $_set_cookies ) {
                                                                $_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
                                                            }
                                                        } );
                                                        // Set cookies.
                                                        wp_set_auth_cookie( $driver_id );
                                                        $_set_cookies = false;
                                                        // Create nounce.
                                                        $new_nonce = wp_create_nonce( 'lddfw-nonce' );
                                                    }
                                                    $result = 1;
                                                    $error = __( 'Successfully updated.', 'lddfw' );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return "{\"result\":\"{$result}\",\"error\":\"{$error}\",\"nonce\":\"{$new_nonce}\"}";
    }

    /**
     * Drivers selectbox.
     *
     * @param object $drivers drivers object.
     * @param int    $driver_id user id number.
     * @param int    $order_id order number.
     * @param string $type type.
     * @return void
     */
    public static function lddfw_driver_drivers_selectbox(
        $drivers,
        $driver_id,
        $order_id,
        $type
    ) {
        if ( 'bulk' === $type ) {
            echo "<select name='lddfw_driverid_" . esc_attr( $order_id ) . "' id='lddfw_driverid_" . esc_attr( $order_id ) . "'>";
        } else {
            echo "<select name='lddfw_driverid' id='lddfw_driverid_" . esc_attr( $order_id ) . "' order='" . esc_attr( $order_id ) . "' class='widefat'>";
        }
        echo "<option value=''>" . esc_html( __( 'Assign a driver', 'lddfw' ) ) . '</option>
    ';
        $last_availability = '';
        foreach ( $drivers as $driver ) {
            $driver_name = $driver->display_name;
            $availability = get_user_meta( $driver->ID, 'lddfw_driver_availability', true );
            $driver_account = get_user_meta( $driver->ID, 'lddfw_driver_account', true );
            $availability = ( '1' === $availability ? esc_attr( __( 'Available', 'lddfw' ) ) : esc_attr( __( 'Unavailable', 'lddfw' ) ) );
            $selected = '';
            if ( intval( $driver_id ) === $driver->ID ) {
                $selected = 'selected';
            }
            if ( $last_availability !== $availability ) {
                if ( '' !== $last_availability ) {
                    echo '</optgroup>';
                }
                echo '<optgroup label="' . esc_attr( $availability . ' ' . __( 'drivers', 'lddfw' ) ) . '">';
                $last_availability = $availability;
            }
            if ( '1' === $driver_account || '1' !== $driver_account && intval( $driver_id ) === $driver->ID ) {
                echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $driver->ID ) . '">' . esc_html( $driver_name ) . '</option>';
            }
        }
        echo '</optgroup></select>';
    }

}
