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
                $note = __( 'A delivery driver has been assigned to the order.', 'lddfw' );
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
                /* Send sms to driver */
                $lddfw_sms_assign_to_driver = get_option( 'lddfw_sms_assign_to_driver', '' );
                if ( '1' === $lddfw_sms_assign_to_driver && 'store' === $operator ) {
                    $sms = new LDDFW_SMS();
                    $result = $sms->lddfw_send_sms_to_driver( $order_id, $order, $driver_id );
                    $note .= ', ' . $result[1];
                }
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
        $driver_id = $user_meta->ID;
        $lddfw_driver_availability = get_user_meta( $driver_id, 'lddfw_driver_availability', true );
        $is_available = '1' === $lddfw_driver_availability;
        // Driver photo URL for the hero avatar.
        $driver_photo_url = '';
        if ( '' === $driver_photo_url ) {
            $driver_photo_url = plugins_url() . '/' . LDDFW_FOLDER . '/public/images/user.png?ver=' . LDDFW_VERSION;
        }
        $display_name = trim( $first_name . ' ' . $last_name );
        if ( '' === $display_name ) {
            $display_name = $user_meta->display_name;
        }
        // Inline SVG helpers for section icons.
        $icon_delivery = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9 1.96 2.5H17V9.5h2.5zM18 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
        $icon_contact = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
        $icon_account = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm3 11c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>';
        $html = '<form service="lddfw_edit_driver" class="lddfw_form lddfw-settings-form" id="driver_form">';
        $html .= '<div class="lddfw-settings container">';
        // Hero header.
        $role_label = esc_html__( 'Driver', 'lddfw' );
        $html .= '<div class="lddfw-settings-hero">';
        $html .= '<div class="lddfw-settings-hero__avatar"><img src="' . esc_url( $driver_photo_url ) . '" alt="' . esc_attr( $display_name ) . '"></div>';
        $html .= '<div class="lddfw-settings-hero__identity">';
        $html .= '<div class="lddfw-settings-hero__eyebrow">' . esc_html__( 'Profile & Settings', 'lddfw' ) . '</div>';
        $html .= '<div class="lddfw-settings-hero__name">' . esc_html( $display_name ) . '</div>';
        $html .= '<div class="lddfw-settings-hero__role">' . $role_label . '</div>';
        $html .= '</div></div>';
        // Two-column layout (sticky TOC + form).
        $html .= '<div class="lddfw-settings-layout">';
        // Sticky TOC nav.
        $html .= '<nav class="lddfw-settings-toc" aria-label="' . esc_attr__( 'Settings sections', 'lddfw' ) . '">';
        $html .= '<a class="lddfw-settings-toc__item is-active" href="#lddfw-section-delivery"><span class="lddfw-settings-toc__icon">' . $icon_delivery . '</span><span>' . esc_html__( 'Delivery', 'lddfw' ) . '</span></a>';
        $html .= '<a class="lddfw-settings-toc__item" href="#lddfw-section-contact"><span class="lddfw-settings-toc__icon">' . $icon_contact . '</span><span>' . esc_html__( 'Contact Info', 'lddfw' ) . '</span></a>';
        $html .= '<a class="lddfw-settings-toc__item" href="#lddfw-section-account"><span class="lddfw-settings-toc__icon">' . $icon_account . '</span><span>' . esc_html__( 'Account', 'lddfw' ) . '</span></a>';
        $html .= '</nav>';
        // Form body.
        $html .= '<div class="lddfw-settings-body">';
        $html .= '<input type="hidden" name="lddfw_driverid" value="' . esc_attr( $driver_id ) . '">';
        $html .= '<input type="hidden" name="lddfw_wpnonce" id="lddfw_wpnonce" value="' . esc_attr( $lddfw_wpnonce ) . '">';
        //$html .= '<div class="lddfw_alert_wrap"></div>';
        /* ===== Delivery section ===== */
        $html .= '<section id="lddfw-section-delivery" class="lddfw-settings-card">';
        $html .= '<header class="lddfw-settings-card__header"><span class="lddfw-settings-card__icon lddfw-settings-card__icon--delivery">' . $icon_delivery . '</span><h3 class="lddfw-settings-card__title">' . esc_html__( 'Delivery Settings', 'lddfw' ) . '</h3></header>';
        $html .= '<div class="lddfw-settings-card__body">';
        // Availability - pill toggle (keeps legacy hooks: #lddfw_availability, #lddfw_availability_status).
        $html .= '<div class="lddfw-settings-switch-row availability">';
        $html .= '<div class="lddfw-settings-switch-row__text">';
        $html .= '<div class="lddfw-settings-switch-row__label">' . esc_html__( 'I am', 'lddfw' ) . ' <span id="lddfw_availability_status" available="' . esc_attr__( 'Available', 'lddfw' ) . '" unavailable="' . esc_attr__( 'Unavailable', 'lddfw' ) . '">' . (( $is_available ? esc_html__( 'Available', 'lddfw' ) : esc_html__( 'Unavailable', 'lddfw' ) )) . '</span></div>';
        $html .= '<div class="lddfw-settings-switch-row__hint">' . esc_html__( 'Turn on to receive new delivery assignments.', 'lddfw' ) . '</div>';
        $html .= '</div>';
        $html .= '<a id="lddfw_availability" class="lddfw-pill-toggle ' . (( $is_available ? 'lddfw_active is-on' : '' )) . '" title="' . esc_attr__( 'Availability status', 'lddfw' ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" role="switch" aria-checked="' . (( $is_available ? 'true' : 'false' )) . '"><span class="lddfw-pill-toggle__knob"></span></a>';
        $html .= '</div>';
        $html .= '</div></section>';
        // Delivery card.
        /* ===== Contact Info section ===== */
        $html .= '<section id="lddfw-section-contact" class="lddfw-settings-card">';
        $html .= '<header class="lddfw-settings-card__header"><span class="lddfw-settings-card__icon lddfw-settings-card__icon--contact">' . $icon_contact . '</span><h3 class="lddfw-settings-card__title">' . esc_html__( 'Contact Info', 'lddfw' ) . '</h3></header>';
        $html .= '<div class="lddfw-settings-card__body">';
        // Name row (2-column on desktop).
        $html .= '<div class="lddfw-field-grid">';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_first_name">' . esc_html__( 'First name', 'lddfw' ) . '</label><input type="text" name="lddfw_first_name" value="' . esc_attr( $first_name ) . '" class="form-control reqi" id="lddfw_first_name" placeholder="' . esc_attr__( 'First name', 'lddfw' ) . '"></div>';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_last_name">' . esc_html__( 'Last name', 'lddfw' ) . '</label><input type="text" name="lddfw_last_name" value="' . esc_attr( $last_name ) . '" class="form-control" id="lddfw_last_name" placeholder="' . esc_attr__( 'Last name', 'lddfw' ) . '"></div>';
        $html .= '</div>';
        // Company (full width).
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_company">' . esc_html__( 'Company', 'lddfw' ) . '</label><input type="text" name="lddfw_company" value="' . esc_attr( $company ) . '" class="form-control" id="lddfw_company" placeholder="' . esc_attr__( 'Company', 'lddfw' ) . '"><small class="lddfw-field__hint">' . esc_html__( 'Optional. Shown on driver listings.', 'lddfw' ) . '</small></div>';
        // Address line 1 + 2.
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_address_1">' . esc_html__( 'Address line 1', 'lddfw' ) . '</label><input type="text" name="lddfw_address_1" value="' . esc_attr( $address_1 ) . '" class="form-control" id="lddfw_address_1" placeholder="' . esc_attr__( 'Street address', 'lddfw' ) . '"></div>';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_address_2">' . esc_html__( 'Address line 2', 'lddfw' ) . '</label><input type="text" name="lddfw_address_2" value="' . esc_attr( $address_2 ) . '" class="form-control" id="lddfw_address_2" placeholder="' . esc_attr__( 'Apartment, suite, etc. (optional)', 'lddfw' ) . '"></div>';
        // City + Postcode (2-column).
        $html .= '<div class="lddfw-field-grid">';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_city">' . esc_html__( 'City', 'lddfw' ) . '</label><input type="text" name="lddfw_city" value="' . esc_attr( $city ) . '" class="form-control" id="lddfw_city" placeholder="' . esc_attr__( 'City', 'lddfw' ) . '"></div>';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_postcode">' . esc_html__( 'Postcode / ZIP', 'lddfw' ) . '</label><input type="text" name="lddfw_postcode" value="' . esc_attr( $postcode ) . '" class="form-control" id="lddfw_postcode" placeholder="' . esc_attr__( 'Postcode / ZIP', 'lddfw' ) . '"></div>';
        $html .= '</div>';
        // Country + State.
        global $woocommerce;
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->__get( 'countries' );
        $default_county_states = $countries_obj->get_states( 'US' );
        $html .= '<div class="lddfw-field-grid">';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="billing_country">' . esc_html__( 'Country / Region', 'lddfw' ) . '</label>';
        $html .= '<select id="billing_country" name="lddfw_country" class="form-control"><option value="">' . esc_html__( 'Select Country / Region', 'lddfw' ) . '</option>';
        foreach ( $countries as $key => $country ) {
            $html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $billing_country, $key, false ) . '>' . esc_html( $country ) . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="billing_state_select">' . esc_html__( 'State / County', 'lddfw' ) . '</label>';
        $html .= '<select style="display:none" id="billing_state_select" name="billing_state_select" class="form-control"><option value="">' . esc_html__( 'Select State / County', 'lddfw' ) . '</option>';
        foreach ( $default_county_states as $key => $state ) {
            $html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $billing_state, $key, false ) . '>' . esc_html( $state ) . '</option>';
        }
        $html .= '</select>';
        $html .= '<input type="text" style="display:none" class="form-control" id="billing_state_input" placeholder="' . esc_attr__( 'State / County', 'lddfw' ) . '" value="' . esc_attr( $billing_state ) . '" name="billing_state">';
        $html .= '</div>';
        $html .= '</div>';
        // grid
        // Phone.
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_phone">' . esc_html__( 'Phone number', 'lddfw' ) . '</label><input type="tel" name="lddfw_phone" value="' . esc_attr( $phone ) . '" class="form-control" id="lddfw_phone" placeholder="' . esc_attr__( 'Phone number', 'lddfw' ) . '"><small class="lddfw-field__hint">' . esc_html__( 'Used for delivery-related contact only.', 'lddfw' ) . '</small></div>';
        $html .= '</div></section>';
        // Contact card.
        /* ===== Account section ===== */
        $html .= '<section id="lddfw-section-account" class="lddfw-settings-card">';
        $html .= '<header class="lddfw-settings-card__header"><span class="lddfw-settings-card__icon lddfw-settings-card__icon--account">' . $icon_account . '</span><h3 class="lddfw-settings-card__title">' . esc_html__( 'Account', 'lddfw' ) . '</h3></header>';
        $html .= '<div class="lddfw-settings-card__body">';
        $html .= '<div class="lddfw-field"><label class="lddfw-field__label" for="lddfw_email">' . esc_html__( 'Email address', 'lddfw' ) . '</label><input type="email" name="lddfw_email" value="' . esc_attr( $email ) . '" class="form-control" id="lddfw_email" placeholder="' . esc_attr__( 'Enter email', 'lddfw' ) . '"><small class="lddfw-field__hint">' . esc_html__( 'Used for sign-in and password recovery.', 'lddfw' ) . '</small></div>';
        // Password - with show/hide eye.
        $html .= '<div class="lddfw-field">';
        $html .= '<label class="lddfw-field__label" for="lddfw_password">' . esc_html__( 'Password', 'lddfw' ) . '</label>';
        $html .= '<button type="button" id="new_password_button" class="btn btn-secondary lddfw-btn-ghost">' . esc_html__( 'Set New Password', 'lddfw' ) . '</button>';
        $html .= '<div id="lddfw_password_holder" class="lddfw-password-field" style="display:none">';
        $html .= '<div class="lddfw-password-input">';
        $html .= '<input type="password" name="lddfw_password" id="lddfw_password" value="" class="form-control" placeholder="' . esc_attr__( 'Enter new password', 'lddfw' ) . '" autocomplete="new-password">';
        $html .= '<button type="button" class="lddfw-password-eye" aria-label="' . esc_attr__( 'Show password', 'lddfw' ) . '" aria-pressed="false">';
        $html .= '<svg class="lddfw-password-eye__show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>';
        $html .= '<svg class="lddfw-password-eye__hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" style="display:none"><path fill="currentColor" d="M12 6.5c2.76 0 5 2.24 5 5 0 .51-.1 1-.24 1.46l3.06 3.06c1.39-1.23 2.49-2.77 3.18-4.53-1.73-4.39-6-7.5-11-7.5-1.27 0-2.49.2-3.64.57l2.17 2.17c.47-.14.96-.23 1.47-.23zM2.71 3.16 1.3 4.57l2.4 2.4C2.21 8.13 1.03 9.93.46 11.99 2.19 16.38 6.46 19.49 11.46 19.49c1.55 0 3.03-.3 4.38-.84l2.77 2.77 1.41-1.41L2.71 3.16zM7.53 7.98l1.57 1.57c-.05.23-.07.47-.07.7a3 3 0 0 0 3 3c.23 0 .47-.02.7-.07l1.57 1.57c-.72.36-1.45.57-2.27.57a5 5 0 0 1-5-5c0-.82.2-1.55.56-2.27zM11.84 7.02l3.14 3.14.02-.16a3 3 0 0 0-3-3l-.16.02z"/></svg>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div class="lddfw-password-strength" aria-hidden="true"><span class="lddfw-password-strength__bar"><span class="lddfw-password-strength__fill"></span></span><span class="lddfw-password-strength__label"></span></div>';
        $html .= '<a href="#" id="cancel_password_button" class="lddfw-password-cancel">' . esc_html__( 'Cancel', 'lddfw' ) . '</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div></section>';
        // Account card.
        $html .= '</div>';
        // .lddfw-settings-body
        $html .= '</div>';
        // .lddfw-settings-layout
        $html .= '</div>';
        // .lddfw-settings
        // Sticky footer with submit.
        $html .= '<div class="lddfw_footer_buttons lddfw-settings-footer">
			<div class="container"><div class="row"><div class="col-12">
				<button class="lddfw_submit_btn btn btn-lg btn-primary btn-block" type="submit">' . esc_html__( 'Save changes', 'lddfw' ) . '</button>
				<button style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' . esc_html__( 'Saving...', 'lddfw' ) . '</button>
			</div></div></div>
		</div>';
        $html .= '</form>';
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
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified upstream in the AJAX dispatcher via lddfw_wpnonce / lddfw-nonce.
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
            // phpcs:enable WordPress.Security.NonceVerification.Missing
            // Get the current logged-in user's ID
            $driver_id = get_current_user_id();
            if ( empty( $driver_id ) || !user_can( $driver_id, 'driver' ) ) {
                $error = __( 'This account is not registered as a driver.', 'lddfw' );
            } else {
                // Check for empty fields.
                if ( '' === $email ) {
                    // No email.
                    $error = __( 'The email field is empty.', 'lddfw' );
                } else {
                    if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                        // Invalid Email.
                        $error = __( 'The email address is invalid.', 'lddfw' );
                    } else {
                        // Email exist for another user.
                        $user = get_user_by( 'email', $email );
                        $user_id = $user->data->ID;
                        if ( $user && (string) $user_id !== (string) $driver_id ) {
                            $error = __( 'Email already exists for another user.', 'lddfw' );
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
    /**
     * Admin modal driver form.
     *
     * Produces a compact HTML form used inside a jQuery UI Dialog on the
     * "Drivers & Applications" admin page. Unlike lddfw_edit_driver_form()
     * (which runs on the driver panel and requires Bootstrap), this form uses
     * native WordPress admin classes so it renders cleanly inside the modal.
     *
     * @since 2.3.0
     * @param int $driver_id Driver user ID (0 = new driver).
     * @return string
     */
    public function lddfw_admin_driver_form( $driver_id = 0 ) {
        $driver_id = intval( $driver_id );
        $is_new = 0 === $driver_id;
        $first_name = '';
        $last_name = '';
        $email = '';
        $phone = '';
        $city = '';
        $address_1 = '';
        $address_2 = '';
        $postcode = '';
        $state = '';
        $country = '';
        $vehicle = '';
        $plate = '';
        $availability = '0';
        $account = '1';
        $claim = '';
        $cities = array();
        $lat = '';
        $lng = '';
        $driver_img_id = 0;
        $driver_img_url = '';
        if ( !$is_new ) {
            $u = get_userdata( $driver_id );
            if ( false === $u ) {
                return '<p>' . esc_html__( 'Driver not found.', 'lddfw' ) . '</p>';
            }
            $first_name = (string) $u->first_name;
            $last_name = (string) $u->last_name;
            $email = (string) $u->user_email;
            $phone = (string) get_user_meta( $driver_id, 'billing_phone', true );
            $city = (string) get_user_meta( $driver_id, 'billing_city', true );
            $address_1 = (string) get_user_meta( $driver_id, 'billing_address_1', true );
            $address_2 = (string) get_user_meta( $driver_id, 'billing_address_2', true );
            $postcode = (string) get_user_meta( $driver_id, 'billing_postcode', true );
            $state = (string) get_user_meta( $driver_id, 'billing_state', true );
            $country = (string) get_user_meta( $driver_id, 'billing_country', true );
            $vehicle = (string) get_user_meta( $driver_id, 'lddfw_driver_vehicle', true );
            $plate = (string) get_user_meta( $driver_id, 'lddfw_driver_licence_plate', true );
            $availability = (string) get_user_meta( $driver_id, 'lddfw_driver_availability', true );
            $account = (string) get_user_meta( $driver_id, 'lddfw_driver_account', true );
            $claim = (string) get_user_meta( $driver_id, 'lddfw_driver_claim', true );
            $cities_raw = get_user_meta( $driver_id, 'lddfw_driver_cities', true );
            $cities = ( is_array( $cities_raw ) ? $cities_raw : array() );
            $lat = (string) get_user_meta( $driver_id, 'lddfw_address_latitude', true );
            $lng = (string) get_user_meta( $driver_id, 'lddfw_address_longitude', true );
        } else {
            // Sensible defaults for new drivers: available + account active.
            $availability = '1';
            $account = '1';
        }
        $countries_obj = null;
        $countries = array();
        if ( class_exists( 'WC_Countries' ) ) {
            $countries_obj = new WC_Countries();
            $countries = $countries_obj->__get( 'countries' );
            if ( '' === $country ) {
                $country = $countries_obj->get_base_country();
            }
        }
        ob_start();
        ?>
		<form id="lddfw-admin-driver-form" class="lddfw-driver-form" data-driver-id="<?php 
        echo esc_attr( $driver_id );
        ?>" data-is-new="<?php 
        echo ( $is_new ? '1' : '0' );
        ?>">
			<?php 
        wp_nonce_field( 'lddfw-drivers-page', 'lddfw_drivers_nonce', false );
        ?>
			<input type="hidden" name="driver_id" value="<?php 
        echo esc_attr( $driver_id );
        ?>">
			<div class="lddfw-driver-form-status" aria-live="polite"></div>

			<div class="lddfw-form-section">
				<h3><?php 
        echo esc_html__( 'Profile', 'lddfw' );
        ?></h3>
				<?php 
        ?>

				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th><label><?php 
        echo esc_html__( 'First name', 'lddfw' );
        ?> *</label></th>
						<td><input type="text" class="regular-text" name="first_name" value="<?php 
        echo esc_attr( $first_name );
        ?>" required></td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Last name', 'lddfw' );
        ?> *</label></th>
						<td><input type="text" class="regular-text" name="last_name" value="<?php 
        echo esc_attr( $last_name );
        ?>" required></td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Email', 'lddfw' );
        ?> *</label></th>
						<td><input type="email" class="regular-text" name="email" value="<?php 
        echo esc_attr( $email );
        ?>" required></td>
					</tr>
					<tr>
						<th><label for="lddfw-admin-driver-phone"><?php 
        echo esc_html__( 'Phone', 'lddfw' );
        ?> *</label></th>
						<td>
							<input type="text" class="regular-text" id="lddfw-admin-driver-phone" name="phone" value="<?php 
        echo esc_attr( $phone );
        ?>" required autocomplete="tel" placeholder="<?php 
        echo esc_attr( '+1 555 123 4567' );
        ?>">
							<p class="description"><?php 
        echo esc_html__( 'Required. Use international format with a leading + and country code (e.g. +44, +1). Spaces or dashes are fine.', 'lddfw' );
        ?></p>
						</td>
					</tr>
					<?php 
        if ( $is_new ) {
            ?>
					<tr>
						<th><label><?php 
            echo esc_html__( 'Password', 'lddfw' );
            ?></label></th>
						<td>
							<input type="text" class="regular-text" name="password" value="">
							<p class="description"><?php 
            echo esc_html__( 'Leave empty to auto-generate a password. The driver will receive a set-password email.', 'lddfw' );
            ?></p>
						</td>
					</tr>
					<?php 
        } else {
            ?>
					<tr>
						<th><label><?php 
            echo esc_html__( 'New password', 'lddfw' );
            ?></label></th>
						<td>
							<input type="text" class="regular-text" name="password" value="">
							<p class="description"><?php 
            echo esc_html__( 'Leave empty to keep the current password.', 'lddfw' );
            ?></p>
						</td>
					</tr>
					<?php 
        }
        ?>
				</tbody></table>
			</div>

			<div class="lddfw-form-section">
				<h3><?php 
        echo esc_html__( 'Address', 'lddfw' );
        ?></h3>
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th><label for="lddfw-admin-driver-address-1"><?php 
        echo esc_html__( 'Address line 1', 'lddfw' );
        ?> *</label></th>
						<td><input type="text" class="regular-text" id="lddfw-admin-driver-address-1" name="address_1" value="<?php 
        echo esc_attr( $address_1 );
        ?>" required></td>
					</tr>
					<tr>
						<th><label for="lddfw-admin-driver-address-2"><?php 
        echo esc_html__( 'Address line 2', 'lddfw' );
        ?></label></th>
						<td>
							<input type="text" class="regular-text" id="lddfw-admin-driver-address-2" name="address_2" value="<?php 
        echo esc_attr( $address_2 );
        ?>">
							<p class="description"><?php 
        echo esc_html__( 'Optional (e.g. apartment, suite).', 'lddfw' );
        ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="lddfw-admin-driver-city"><?php 
        echo esc_html__( 'City', 'lddfw' );
        ?> *</label></th>
						<td><input type="text" class="regular-text" id="lddfw-admin-driver-city" name="city" value="<?php 
        echo esc_attr( $city );
        ?>" required></td>
					</tr>
					<tr>
						<th><label for="lddfw-admin-driver-state"><?php 
        echo esc_html__( 'State / County', 'lddfw' );
        ?> *</label></th>
						<td><input type="text" class="regular-text" id="lddfw-admin-driver-state" name="state" value="<?php 
        echo esc_attr( $state );
        ?>" required></td>
					</tr>
					<tr>
						<th><label for="lddfw-admin-driver-postcode"><?php 
        echo esc_html__( 'Postcode / ZIP', 'lddfw' );
        ?> *</label></th>
						<td><input type="text" class="regular-text" id="lddfw-admin-driver-postcode" name="postcode" value="<?php 
        echo esc_attr( $postcode );
        ?>" required></td>
					</tr>
					<tr>
						<th><label for="lddfw-admin-driver-country"><?php 
        echo esc_html__( 'Country / Region', 'lddfw' );
        ?> *</label></th>
						<td>
							<select name="country" id="lddfw-admin-driver-country" class="regular-text" required>
								<option value=""><?php 
        echo esc_html__( 'Select Country / Region', 'lddfw' );
        ?></option>
								<?php 
        foreach ( $countries as $code => $name ) {
            echo '<option value="' . esc_attr( $code ) . '" ' . selected( $country, $code, false ) . '>' . esc_html( $name ) . '</option>';
        }
        ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Latitude / Longitude', 'lddfw' );
        ?></label></th>
						<td>
							<?php 
        $lddfw_admin_lat_lng_inputs = '<input type="text" style="width:140px" name="lat" value="' . esc_attr( $lat ) . '" placeholder="' . esc_attr__( 'Latitude', 'lddfw' ) . '">' . ' <input type="text" style="width:140px" name="lng" value="' . esc_attr( $lng ) . '" placeholder="' . esc_attr__( 'Longitude', 'lddfw' ) . '">';
        echo lddfw_admin_premium_feature( $lddfw_admin_lat_lng_inputs );
        ?>
						</td>
					</tr>
				</tbody></table>
			</div>

			<div class="lddfw-form-section">
				<h3><?php 
        echo esc_html__( 'Driver settings', 'lddfw' );
        ?></h3>
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Availability', 'lddfw' );
        ?></label></th>
						<td>
							<label><input type="checkbox" name="availability" value="1" <?php 
        checked( '1', $availability );
        ?>> <?php 
        echo esc_html__( 'Driver is currently available', 'lddfw' );
        ?></label>
						</td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Account active', 'lddfw' );
        ?></label></th>
						<td>
							<label><input type="checkbox" name="account" value="1" <?php 
        checked( '1', $account );
        ?>> <?php 
        echo esc_html__( 'Include this driver in assignments and select boxes', 'lddfw' );
        ?></label>
						</td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Can claim orders', 'lddfw' );
        ?></label></th>
						<td>
							<?php 
        $lddfw_admin_claim_field = '<label><input type="checkbox" name="claim" value="1" ' . checked( '1', $claim, false ) . '> ' . esc_html__( 'Allow this driver to claim unassigned orders', 'lddfw' ) . '</label>';
        echo lddfw_admin_premium_feature( $lddfw_admin_claim_field );
        ?>
						</td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Vehicle', 'lddfw' );
        ?></label></th>
						<td>
							<?php 
        $lddfw_admin_vehicle_field = '<input type="text" class="regular-text" name="vehicle" value="' . esc_attr( $vehicle ) . '">';
        echo lddfw_admin_premium_feature( $lddfw_admin_vehicle_field );
        ?>
						</td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'License plate', 'lddfw' );
        ?></label></th>
						<td>
							<?php 
        $lddfw_admin_plate_field = '<input type="text" class="regular-text" name="plate" value="' . esc_attr( $plate ) . '">';
        echo lddfw_admin_premium_feature( $lddfw_admin_plate_field );
        ?>
						</td>
					</tr>
					<tr>
						<th><label><?php 
        echo esc_html__( 'Cities (for auto-assign)', 'lddfw' );
        ?></label></th>
						<td>
							<?php 
        $lddfw_admin_cities_field = '<input type="text" class="regular-text" name="cities" value="' . esc_attr( implode( ', ', $cities ) ) . '">' . '<p class="description">' . esc_html__( 'Comma-separated list of cities this driver delivers to.', 'lddfw' ) . '</p>';
        echo lddfw_admin_premium_feature( $lddfw_admin_cities_field );
        ?>
						</td>
					</tr>
				</tbody></table>
			</div>

			<div class="lddfw-driver-form-actions">
				<button type="button" class="button button-secondary lddfw-driver-form-cancel"><?php 
        echo esc_html__( 'Cancel', 'lddfw' );
        ?></button>
				<button type="submit" class="button button-primary"><?php 
        echo ( $is_new ? esc_html__( 'Create driver', 'lddfw' ) : esc_html__( 'Save changes', 'lddfw' ) );
        ?></button>
			</div>
		</form>
		<?php 
        return (string) ob_get_clean();
    }

    /**
     * Validates phone (international + country code) and address fields for the admin driver modal.
     *
     * @param string $phone     Billing phone.
     * @param string $address_1 Address line 1.
     * @param string $city      City.
     * @param string $state     State / county.
     * @param string $postcode  Postcode.
     * @param string $country   WooCommerce country code.
     * @return string Empty string if valid; otherwise a translated error message.
     */
    private function lddfw_admin_validate_driver_phone_and_address(
        $phone,
        $address_1,
        $city,
        $state,
        $postcode,
        $country
    ) {
        $phone = trim( (string) $phone );
        if ( '' === $phone ) {
            return __( 'Phone number is required.', 'lddfw' );
        }
        if ( '+' !== $phone[0] ) {
            return __( 'Phone must start with + followed by the country code (international format, e.g. +1 555 123 4567).', 'lddfw' );
        }
        $digits_only = preg_replace( '/\\D/', '', substr( $phone, 1 ) );
        $digits_len = strlen( $digits_only );
        if ( $digits_len < 8 || $digits_len > 15 ) {
            return __( 'Enter a valid international phone number: +, country code, then 8–15 digits in total.', 'lddfw' );
        }
        if ( '' === trim( (string) $address_1 ) ) {
            return __( 'Address line 1 is required.', 'lddfw' );
        }
        if ( '' === trim( (string) $city ) ) {
            return __( 'City is required.', 'lddfw' );
        }
        if ( '' === trim( (string) $state ) ) {
            return __( 'State / County is required.', 'lddfw' );
        }
        if ( '' === trim( (string) $postcode ) ) {
            return __( 'Postcode / ZIP is required.', 'lddfw' );
        }
        if ( '' === trim( (string) $country ) ) {
            return __( 'Country / Region is required.', 'lddfw' );
        }
        if ( class_exists( 'WC_Countries' ) ) {
            $wc_countries = ( new WC_Countries() )->get_countries();
            if ( is_array( $wc_countries ) && count( $wc_countries ) > 0 && !isset( $wc_countries[$country] ) ) {
                return __( 'Please select a valid country / region.', 'lddfw' );
            }
        }
        return '';
    }

    /**
     * Admin service: create a new driver or update an existing one.
     *
     * Requires the 'edit_users' capability. Returns a JSON-encoded string for
     * consistency with other LDDFW AJAX services.
     *
     * @since 2.3.0
     * @return string JSON string with result / error / driver_id.
     */
    public function lddfw_admin_driver_save_service() {
        $result = 0;
        $error = '';
        $driver_id = 0;
        if ( !current_user_can( 'edit_users' ) ) {
            return wp_json_encode( array(
                'result' => 0,
                'error'  => __( 'Insufficient permissions.', 'lddfw' ),
            ) );
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified upstream via check_ajax_referer( 'lddfw-drivers-page' ) in the AJAX dispatcher.
        $driver_id = ( isset( $_POST['driver_id'] ) ? intval( $_POST['driver_id'] ) : 0 );
        $is_new = 0 === $driver_id;
        $first_name = ( isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '' );
        $last_name = ( isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '' );
        $email = ( isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '' );
        $phone = ( isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '' );
        $password = ( isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '' );
        $address_1 = ( isset( $_POST['address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['address_1'] ) ) : '' );
        $address_2 = ( isset( $_POST['address_2'] ) ? sanitize_text_field( wp_unslash( $_POST['address_2'] ) ) : '' );
        $city = ( isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '' );
        $state = ( isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '' );
        $postcode = ( isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '' );
        $country = ( isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '' );
        $lat = ( isset( $_POST['lat'] ) ? sanitize_text_field( wp_unslash( $_POST['lat'] ) ) : '' );
        $lng = ( isset( $_POST['lng'] ) ? sanitize_text_field( wp_unslash( $_POST['lng'] ) ) : '' );
        $vehicle = ( isset( $_POST['vehicle'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle'] ) ) : '' );
        $plate = ( isset( $_POST['plate'] ) ? sanitize_text_field( wp_unslash( $_POST['plate'] ) ) : '' );
        $cities_raw = ( isset( $_POST['cities'] ) ? sanitize_text_field( wp_unslash( $_POST['cities'] ) ) : '' );
        $availability = ( isset( $_POST['availability'] ) ? '1' : '0' );
        $account = ( isset( $_POST['account'] ) ? '1' : '0' );
        $claim = ( isset( $_POST['claim'] ) ? '1' : '0' );
        $driver_img_id = ( isset( $_POST['driver_image_id'] ) ? intval( $_POST['driver_image_id'] ) : -1 );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        // Validate.
        if ( '' === $first_name || '' === $last_name ) {
            return wp_json_encode( array(
                'result' => 0,
                'error'  => __( 'First and last name are required.', 'lddfw' ),
            ) );
        }
        if ( '' === $email || !is_email( $email ) ) {
            return wp_json_encode( array(
                'result' => 0,
                'error'  => __( 'A valid email is required.', 'lddfw' ),
            ) );
        }
        // Uniqueness check for email.
        $existing = get_user_by( 'email', $email );
        if ( $existing && ($is_new || intval( $existing->ID ) !== $driver_id) ) {
            return wp_json_encode( array(
                'result' => 0,
                'error'  => __( 'Email already exists for another user.', 'lddfw' ),
            ) );
        }
        $location_error = $this->lddfw_admin_validate_driver_phone_and_address(
            $phone,
            $address_1,
            $city,
            $state,
            $postcode,
            $country
        );
        if ( '' !== $location_error ) {
            return wp_json_encode( array(
                'result' => 0,
                'error'  => $location_error,
            ) );
        }
        $phone = trim( preg_replace( '/\\s+/', ' ', (string) $phone ) );
        if ( $is_new ) {
            // Create the user with driver role.
            $username_base = sanitize_user( strtolower( $first_name . $last_name ), true );
            if ( '' === $username_base ) {
                $username_base = sanitize_user( strtolower( strstr( $email, '@', true ) ), true );
            }
            $username = $username_base;
            $suffix = 1;
            while ( username_exists( $username ) ) {
                $username = $username_base . $suffix;
                $suffix++;
            }
            if ( '' === $password ) {
                $password = wp_generate_password( 16, true, true );
            }
            $new_user_id = wp_insert_user( array(
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'role'         => 'driver',
            ) );
            if ( is_wp_error( $new_user_id ) ) {
                return wp_json_encode( array(
                    'result' => 0,
                    'error'  => $new_user_id->get_error_message(),
                ) );
            }
            $driver_id = (int) $new_user_id;
        } else {
            // Update core user fields.
            $u = get_userdata( $driver_id );
            if ( false === $u || !in_array( 'driver', (array) $u->roles, true ) ) {
                return wp_json_encode( array(
                    'result' => 0,
                    'error'  => __( 'This account is not registered as a driver.', 'lddfw' ),
                ) );
            }
            $update_args = array(
                'ID'           => $driver_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'user_email'   => $email,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'nickname'     => trim( $first_name . ' ' . $last_name ),
            );
            $updated = wp_update_user( $update_args );
            if ( is_wp_error( $updated ) ) {
                return wp_json_encode( array(
                    'result' => 0,
                    'error'  => $updated->get_error_message(),
                ) );
            }
            if ( '' !== $password ) {
                wp_set_password( $password, $driver_id );
            }
        }
        // Free build: modal hides premium-only fields - do not overwrite stored values from POST.
        if ( function_exists( 'lddfw_is_free' ) && lddfw_is_free() ) {
            if ( $is_new ) {
                $claim = '0';
                $vehicle = '';
                $plate = '';
                $cities_raw = '';
                $lat = '';
                $lng = '';
            } else {
                $claim = (string) get_user_meta( $driver_id, 'lddfw_driver_claim', true );
                $vehicle = (string) get_user_meta( $driver_id, 'lddfw_driver_vehicle', true );
                $plate = (string) get_user_meta( $driver_id, 'lddfw_driver_licence_plate', true );
                $lat = (string) get_user_meta( $driver_id, 'lddfw_address_latitude', true );
                $lng = (string) get_user_meta( $driver_id, 'lddfw_address_longitude', true );
                $cities_meta = get_user_meta( $driver_id, 'lddfw_driver_cities', true );
                $cities_meta = ( is_array( $cities_meta ) ? $cities_meta : array() );
                $cities_raw = implode( ', ', $cities_meta );
            }
        }
        // Persist meta.
        update_user_meta( $driver_id, 'billing_first_name', $first_name );
        update_user_meta( $driver_id, 'billing_last_name', $last_name );
        update_user_meta( $driver_id, 'billing_address_1', $address_1 );
        update_user_meta( $driver_id, 'billing_address_2', $address_2 );
        update_user_meta( $driver_id, 'billing_city', $city );
        update_user_meta( $driver_id, 'billing_state', $state );
        update_user_meta( $driver_id, 'billing_postcode', $postcode );
        update_user_meta( $driver_id, 'billing_country', $country );
        update_user_meta( $driver_id, 'billing_phone', $phone );
        update_user_meta( $driver_id, 'lddfw_driver_vehicle', $vehicle );
        update_user_meta( $driver_id, 'lddfw_driver_licence_plate', $plate );
        update_user_meta( $driver_id, 'lddfw_driver_availability', $availability );
        update_user_meta( $driver_id, 'lddfw_driver_account', $account );
        update_user_meta( $driver_id, 'lddfw_driver_claim', $claim );
        if ( '' !== $lat ) {
            update_user_meta( $driver_id, 'lddfw_address_latitude', $lat );
        }
        if ( '' !== $lng ) {
            update_user_meta( $driver_id, 'lddfw_address_longitude', $lng );
        }
        // Cities: comma-separated -> array.
        $cities_array = array();
        if ( '' !== $cities_raw ) {
            $parts = array_map( 'trim', explode( ',', $cities_raw ) );
            foreach ( $parts as $p ) {
                if ( '' !== $p ) {
                    $cities_array[] = $p;
                }
            }
        }
        update_user_meta( $driver_id, 'lddfw_driver_cities', $cities_array );
        if ( $driver_img_id >= 0 ) {
            if ( 0 === $driver_img_id ) {
                delete_user_meta( $driver_id, 'lddfw_driver_image' );
            } else {
                update_user_meta( $driver_id, 'lddfw_driver_image', $driver_img_id );
            }
        }
        $result = 1;
        $success = ( $is_new ? __( 'Driver created successfully.', 'lddfw' ) : __( 'Driver updated successfully.', 'lddfw' ) );
        return wp_json_encode( array(
            'result'    => $result,
            'message'   => $success,
            'driver_id' => $driver_id,
            'is_new'    => $is_new,
        ) );
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
