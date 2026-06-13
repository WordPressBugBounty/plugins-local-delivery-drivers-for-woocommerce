<?php

/**
 * Plugin Screens.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
/**
 * Plugin Screens.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
use Automattic\WooCommerce\Utilities\OrderUtil;
class LDDFW_Screens {
    /**
     * Footer.
     *
     * @since 1.0.0
     * @return html
     */
    public function lddfw_footer() {
        return "<div id='footer'></div>";
    }

    /**
     * Header.
     *
     * @since 1.0.0
     * @param string $title page title.
     * @param string $back_url the url for back.
     * @return html
     */
    public function lddfw_header( $title = null, $back_url = null ) {
        global $lddfw_user, $lddfw_driver_availability, $lddfw_screen;
        if ( '1' === $lddfw_driver_availability ) {
            $availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class="lddfw_availability text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
        } else {
            $availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class="lddfw_availability text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
        }
        $html = '
            <div id="lddfw_header">
            <div class="container">
                <div class="row">';
        $html .= '<div class="col-2 lddfw_back_column">';
        if ( null !== $back_url ) {
            $html .= '<a href="' . $back_url . '" class="lddfw_back_link lddfw-back-btn lddfw_loader" aria-label="' . esc_attr__( 'Go back', 'lddfw' ) . '">
				<svg class="lddfw-back-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
			</a>';
        }
        $html .= '</div>';
        $html .= '<div class="col-8 text-center lddfw_header_title">';
        $html .= $title;
        $html .= '</div>';
        global 
            $lddfw_driver_assigned_status_name,
            $lddfw_out_for_delivery_status_name,
            $lddfw_failed_attempt_status_name,
            $lddfw_out_for_delivery_counter,
            $lddfw_failed_attempt_counter,
            $lddfw_delivered_counter,
            $lddfw_assign_to_driver_counter,
            $lddfw_claim_orders_counter
        ;
        $driver_photo = '';
        // Total unread count for the hamburger badge.
        $lddfw_menu_total_count = intval( $lddfw_claim_orders_counter ) + intval( $lddfw_assign_to_driver_counter ) + intval( $lddfw_out_for_delivery_counter ) + intval( $lddfw_failed_attempt_counter );
        if ( $lddfw_menu_total_count > 0 ) {
            /* translators: %d: number of pending orders (menu badge) */
            $pending_menu_label = _n(
                '%d pending order',
                '%d pending orders',
                $lddfw_menu_total_count,
                'lddfw'
            );
            $hamburger_badge = '<span class="lddfw-menu-trigger__badge" aria-label="' . esc_attr( sprintf( $pending_menu_label, $lddfw_menu_total_count ) ) . '">' . (( $lddfw_menu_total_count > 99 ? '99+' : esc_html( $lddfw_menu_total_count ) )) . '</span>';
        } else {
            $hamburger_badge = '';
        }
        $current_mode = lddfw_get_app_mode( $lddfw_user->ID );
        if ( 'dark' !== $current_mode ) {
            $current_mode = 'light';
        }
        $html .= '<div class="col-2 text-right lddfw-header-actions">
				<a href="#" id="lddfw_theme_toggle" class="lddfw-theme-toggle" data-mode="' . esc_attr( $current_mode ) . '" aria-label="' . esc_attr__( 'Toggle dark mode', 'lddfw' ) . '" title="' . esc_attr__( 'Toggle dark mode', 'lddfw' ) . '">
				<svg class="lddfw-theme-toggle__icon lddfw-theme-toggle__icon--moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
				<svg class="lddfw-theme-toggle__icon lddfw-theme-toggle__icon--sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm-1-5h2v3h-2V2zm0 17h2v3h-2v-3zM4.22 5.64l1.42-1.42 2.12 2.12-1.41 1.42-2.13-2.12zm12.02 12.02 1.41-1.42 2.13 2.13-1.42 1.41-2.12-2.12zM2 11h3v2H2v-2zm17 0h3v2h-3v-2zM5.64 19.78l-1.42-1.41 2.12-2.13 1.42 1.42-2.12 2.12zM17.66 6.34l1.42-1.42 2.12 2.13-1.41 1.41-2.13-2.12z"/></svg>
				</a>
				<a href="#" id="lddfw_menu" class="lddfw-menu-trigger" onclick="lddfw_openNav(); return false;" aria-label="' . esc_attr__( 'Open menu', 'lddfw' ) . '">
				<svg class="lddfw-menu-trigger__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/></svg>
				' . $hamburger_badge . $availability_icon . '
				</a>
				<div id="lddfw_menu_backdrop" class="lddfw-menu-backdrop" onclick="lddfw_closeNav()"></div>
				<div id="lddfw_mySidenav" class="lddfw_sidenav lddfw-menu">
				<a href="javascript:void(0)" class="lddfw_closebtn lddfw-menu__close" onclick="lddfw_closeNav()" aria-label="' . esc_attr__( 'Close menu', 'lddfw' ) . '">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
				</a>
				<div class="lddfw-menu__header dropdown-header">
					<span class="lddfw-menu__avatar">' . (( '' !== $driver_photo ? $driver_photo : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>' )) . '</span>
					<span class="lddfw-menu__identity">
						<h3 class="lddfw-menu__name">' . esc_html( $lddfw_user->display_name ) . '</h3>
						<span class="lddfw-menu__role">' . esc_html__( 'Driver', 'lddfw' ) . '</span>
					</span>
				</div>

				<div class="lddfw-menu__section">
					<div class="lddfw-menu__section-title">' . esc_html__( 'Overview', 'lddfw' ) . '</div>
					<a class="dropdown-item lddfw-menu__item lddfw_loader lddfw_loader_fixed' . $this->lddfw_menu_active_class( array('dashboard'), $lddfw_screen ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=dashboard' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--dashboard">' . $this->lddfw_menu_icon_svg( 'dashboard' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html__( 'Dashboard', 'lddfw' ) . '</span>
					</a>
				</div>

				<div class="lddfw-menu__section">
					<div class="lddfw-menu__section-title">' . esc_html__( 'Deliveries', 'lddfw' ) . '</div>';
        $html .= '
					<a class="dropdown-item lddfw-menu__item lddfw-menu__item--assign lddfw_loader lddfw_loader_fixed' . $this->lddfw_menu_active_class( array('assign_to_driver'), $lddfw_screen ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=assigned_orders' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--assign">' . $this->lddfw_menu_icon_svg( 'assign_to_driver' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html( $lddfw_driver_assigned_status_name ) . '</span>
						' . $this->lddfw_menu_count_badge( $lddfw_assign_to_driver_counter, 'assign' ) . '
					</a>
					<a class="dropdown-item lddfw-menu__item lddfw-menu__item--out lddfw_loader lddfw_loader_fixed' . $this->lddfw_menu_active_class( array('out_for_delivery'), $lddfw_screen ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--out">' . $this->lddfw_menu_icon_svg( 'out_for_delivery' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html( $lddfw_out_for_delivery_status_name ) . '</span>
						' . $this->lddfw_menu_count_badge( $lddfw_out_for_delivery_counter, 'out' ) . '
					</a>
					<a class="dropdown-item lddfw-menu__item lddfw-menu__item--failed lddfw_loader lddfw_loader_fixed' . $this->lddfw_menu_active_class( array('failed_delivery'), $lddfw_screen ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=failed_delivery' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--failed">' . $this->lddfw_menu_icon_svg( 'failed_delivery' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html( $lddfw_failed_attempt_status_name ) . '</span>
						' . $this->lddfw_menu_count_badge( $lddfw_failed_attempt_counter, 'failed' ) . '
					</a>
					<a class="dropdown-item lddfw-menu__item lddfw-menu__item--delivered lddfw_loader lddfw_loader_fixed' . $this->lddfw_menu_active_class( array('delivered'), $lddfw_screen ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--delivered">' . $this->lddfw_menu_icon_svg( 'delivered' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html__( 'Delivered', 'lddfw' ) . '</span>
						' . $this->lddfw_menu_count_badge( $lddfw_delivered_counter, 'delivered' ) . '
					</a>
				</div>

				<div class="lddfw-menu__section">
					<div class="lddfw-menu__section-title">' . esc_html__( 'Account', 'lddfw' ) . '</div>
					<a class="dropdown-item lddfw-menu__item lddfw_loader lddfw_loader_fixed' . $this->lddfw_menu_active_class( array('settings'), $lddfw_screen ) . '" title="' . esc_attr__( 'Settings', 'lddfw' ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=settings' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--settings">' . $this->lddfw_menu_icon_svg( 'settings' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html__( 'Settings', 'lddfw' ) . '</span>
					</a>
					';
        // Close "Account" section.
        $html .= '</div>';
        // Sign out.
        $html .= '<div class="lddfw-menu__section lddfw-menu__section--logout">
					<a class="dropdown-item lddfw-menu__item lddfw-menu__item--logout lddfw_loader lddfw_loader_fixed" title="' . esc_attr__( 'Log out', 'lddfw' ) . '" href="' . lddfw_drivers_page_url( 'lddfw_screen=logout' ) . '">
						<span class="lddfw-menu__icon lddfw-menu__icon--logout">' . $this->lddfw_menu_icon_svg( 'logout' ) . '</span>
						<span class="lddfw-menu__label">' . esc_html__( 'Log out', 'lddfw' ) . '</span>
					</a>
				</div>
			</div>
			</div>
		</div>
		</div>
		</div>';
        return $html;
    }

    /**
     * Inline SVG icon by name for the driver side menu.
     *
     * @param string $which icon key.
     * @return string SVG markup.
     */
    private function lddfw_menu_icon_svg( $which ) {
        $icons = array(
            'dashboard'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>',
            'claim_orders'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>',
            'assign_to_driver' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 5c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 11H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/></svg>',
            'out_for_delivery' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM5.5 19c-.83 0-1.5-.67-1.5-1.5S4.67 16 5.5 16s1.5.67 1.5 1.5S6.33 19 5.5 19zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM17 12h4l2.5 3H17v-3z"/></svg>',
            'failed_delivery'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
            'delivered'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
            'settings'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M19.14 12.94a7.49 7.49 0 0 0 .05-.94c0-.32-.03-.63-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96a7.03 7.03 0 0 0-1.62-.94l-.36-2.54A.484.484 0 0 0 13.89 2h-3.84c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.7 8.47c-.12.21-.08.47.12.61L4.85 10.66c-.04.31-.07.62-.07.94s.03.63.07.94L2.82 14.12a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.3.59.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54c.03.24.23.41.47.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.08-.47-.12-.61l-2.01-1.58zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>',
            'info'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
            'dot'              => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle fill="currentColor" cx="12" cy="12" r="4"/></svg>',
            'logout'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17 8l-1.41 1.41L17.17 11H9v2h8.17l-1.58 1.58L17 16l4-4-4-4zM5 5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7v-2H5V5z"/></svg>',
        );
        return ( isset( $icons[$which] ) ? $icons[$which] : '' );
    }

    /**
     * Returns " is-active" class suffix when current screen is in the list.
     *
     * @param array  $screens array of matching screen keys.
     * @param string $current current screen.
     * @return string
     */
    private function lddfw_menu_active_class( $screens, $current ) {
        return ( in_array( $current, $screens, true ) ? ' is-active' : '' );
    }

    /**
     * Returns a count badge span for the driver menu (empty string if 0).
     *
     * @param int    $count count value.
     * @param string $variant color variant key (claim|assign|out|failed|delivered).
     * @return string
     */
    private function lddfw_menu_count_badge( $count, $variant = 'default' ) {
        $count = intval( $count );
        if ( $count <= 0 ) {
            return '';
        }
        $display = ( $count > 99 ? '99+' : (string) $count );
        return '<span class="lddfw-menu__count lddfw-menu__count--' . esc_attr( $variant ) . '">' . esc_html( $display ) . '</span>';
    }

    /**
     * Homepage.
     *
     * @since 1.0.0
     * @return html
     */
    public function lddfw_home() {
        // show delivery driver homepage.
        global $lddfw_screen, $lddfw_reset_key, $lddfw_reset_login;
        $style_home = '';
        if ( 'resetpassword' === $lddfw_screen ) {
            $style_home = 'style="display:none"';
        }
        // home page.
        $html = '<div class="lddfw_wpage" id="lddfw_home" ' . $style_home . '>
		<div class="container-fluid lddfw_cover"><span class="lddfw_helper"></span>';
        $title = esc_html( __( 'WELCOME', 'lddfw' ) );
        $subtitle = esc_html( __( 'To Delivery Drivers Manager', 'lddfw' ) );
        $logo = '<img class="lddfw_header_image" src="' . plugins_url() . '/' . LDDFW_FOLDER . '/public/images/lddfw.png?ver=' . LDDFW_VERSION . '">';
        // Wrap the logo in a tile so the 2026 home redesign can style the
        // translucent rounded "tile" and the image's own rounded corners
        // independently. The original <img class="lddfw_header_image"> is
        // preserved unchanged inside the wrapper for backward compatibility.
        $html .= '<div class="lddfw-home__logo-tile">' . $logo . '</div>';
        $html .= '</div>
		<div class="container">
			<h1>' . $title . '</h1>
			<p>' . $subtitle . '</p>
			<button id="lddfw_start" class="btn btn-primary btn-lg btn-block" type="button">' . esc_html( __( 'Get started', 'lddfw' ) ) . '</button>
		</div>
	</div>
	';
        $login = new LDDFW_Login();
        $html .= $login->lddfw_login_screen();
        $password = new LDDFW_Password();
        $html .= $password->lddfw_forgot_password_screen();
        $html .= $password->lddfw_forgot_password_email_sent_screen();
        $html .= $password->lddfw_create_password_screen();
        $html .= $password->lddfw_new_password_created_screen();
        return $html;
    }

    /**
     * Delivery page.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_out_for_delivery_screen( $driver_id ) {
        global $lddfw_out_for_delivery_status_name, $lddfw_out_for_delivery_counter;
        $orders = new LDDFW_Orders();
        $orders_count = intval( $lddfw_out_for_delivery_counter );
        $title = $lddfw_out_for_delivery_status_name;
        $back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
        $html = $this->lddfw_header( $title, $back_url );
        $html .= '<div id="lddfw_content" class="container lddfw_page_content lddfw-out-screen">';
        // Hero section - amber "Out for delivery" variant.
        $hero_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M624 352h-16V243.9c0-12.7-5.1-24.9-14.1-33.9L494 110.1c-9-9-21.2-14.1-33.9-14.1H416V48c0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48v320c0 26.5 21.5 48 48 48h16c0 53 43 96 96 96s96-43 96-96h128c0 53 43 96 96 96s96-43 96-96h48c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zM160 464c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm320 0c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm80-208H416V144h44.1l99.9 99.9V256z"/></svg>';
        $html .= '<div class="lddfw-reviews-hero lddfw-reviews-hero--out">';
        $html .= '<div class="lddfw-reviews-hero__avatar lddfw-reviews-hero__avatar--icon" aria-hidden="true">' . $hero_icon . '</div>';
        $html .= '<div class="lddfw-reviews-hero__identity">';
        $html .= '<div class="lddfw-reviews-hero__eyebrow">' . esc_html__( 'On the road', 'lddfw' ) . '</div>';
        $html .= '<div class="lddfw-reviews-hero__name">' . esc_html( $title ) . '</div>';
        if ( $orders_count > 0 ) {
            $html .= '<div class="lddfw-reviews-hero__subtitle">' . esc_html__( 'Plan a route, then tap View Route to navigate', 'lddfw' ) . '</div>';
            /* translators: %d: number of orders currently out for delivery on this driver's route */
            $count_text = sprintf( _n(
                '%d order on route',
                '%d orders on route',
                $orders_count,
                'lddfw'
            ), $orders_count );
            $html .= '<span class="lddfw-reviews-hero__count-pill">' . esc_html( $count_text ) . '</span>';
        }
        $html .= '</div></div>';
        if ( lddfw_is_free() ) {
            $button = esc_attr( __( 'Plan your route', 'lddfw' ) );
            $content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Optimize your route by distance to save time and fuel.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Preview your full route on Google Maps before you leave.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Get turn-by-turn navigation with Waze, Apple Maps, or Google Maps.', 'lddfw' ) );
            $html .= '<div style="margin-bottom:15px;">' . lddfw_premium_feature_notice( $button, $content, '' ) . '</div>';
        }
        $html .= '<div id="lddfw_plain_route_container">';
        $html .= $orders->lddfw_out_for_delivery( $driver_id );
        $html .= '</div>';
        $html .= '</div>';
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Dashboard screen.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_dashboard_screen( $driver_id ) {
        global 
            $lddfw_driver_assigned_status_name,
            $lddfw_out_for_delivery_status_name,
            $lddfw_failed_attempt_status_name,
            $lddfw_driver_availability,
            $lddfw_out_for_delivery_counter,
            $lddfw_failed_attempt_counter,
            $lddfw_delivered_counter,
            $lddfw_assign_to_driver_counter,
            $lddfw_claim_orders_counter,
            $lddfw_user
        ;
        $title = __( 'Dashboard', 'lddfw' );
        $html = $this->lddfw_header( $title );
        $html .= '<div id="lddfw_content" class="container lddfw_dashboard lddfw_page_content">
				<div class="row">
				<div class="col-12">';
        // Greeting strip: avatar + time-aware hello + date.
        $greeting_photo = '';
        if ( '' === $greeting_photo ) {
            $greeting_photo = '<span class="lddfw-greeting__avatar lddfw-greeting__avatar--fallback" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
			</span>';
        }
        $hour = (int) current_time( 'G' );
        if ( $hour < 12 ) {
            $greeting_text = __( 'Good morning', 'lddfw' );
        } elseif ( $hour < 18 ) {
            $greeting_text = __( 'Good afternoon', 'lddfw' );
        } else {
            $greeting_text = __( 'Good evening', 'lddfw' );
        }
        $display_name = ( isset( $lddfw_user->display_name ) ? $lddfw_user->display_name : '' );
        $date_label = date_i18n( get_option( 'date_format' ) );
        $html .= '<div class="lddfw-greeting">
			' . $greeting_photo . '
			<div class="lddfw-greeting__text">
				<div class="lddfw-greeting__hello">' . esc_html( $greeting_text ) . (( '' !== $display_name ? ', ' . esc_html( $display_name ) : '' )) . '</div>
				<div class="lddfw-greeting__date">' . esc_html( $date_label ) . '</div>
			</div>
		</div>';
        $html .= '<div class="lddfw-dashboard-kpi-grid">
				<div class="lddfw_box availability lddfw-kpi-card lddfw-kpi-card--availability">
				<div class="lddfw-kpi-card__body">
				<div class="lddfw-kpi-card__header">
				<div class="lddfw-kpi-card__eyebrow">' . esc_html( __( 'Status', 'lddfw' ) ) . '</div>
				</div>
				<div class="row">
				<div class="col-9 availability-text">' . esc_html( __( 'I am', 'lddfw' ) );
        if ( '1' === $lddfw_driver_availability ) {
            $html .= '
						<span id="lddfw_availability_status" available="' . esc_attr( __( 'Available', 'lddfw' ) ) . '" unavailable="' . esc_attr( __( 'Unavailable', 'lddfw' ) ) . '">' . esc_html( __( 'Available', 'lddfw' ) ) . '</span>
						</div>
						<div class="col-3 text-right">
							<a id="lddfw_availability" class="lddfw_active" title="' . esc_attr( __( 'Availability status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg></a>
						</div>
						';
        } else {
            $html .= '
						<span id="lddfw_availability_status" available="' . esc_attr( __( 'Available', 'lddfw' ) ) . '" unavailable="' . esc_attr( __( 'Unavailable', 'lddfw' ) ) . '">' . esc_html( __( 'Unavailable', 'lddfw' ) ) . '</span>
						</div>
						<div class="col-3 text-right">
							<a id="lddfw_availability" class="" title="' . esc_attr( __( 'Availability status', 'lddfw' ) ) . '" href="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg></a>
						</div>';
        }
        $html .= '
			</div>
			</div>
			<div class="lddfw-kpi-card__icon-col"><span class="lddfw-kpi-card__icon" aria-hidden="true">' . $this->lddfw_dashboard_kpi_icon_svg( 'availability' ) . '</span></div>
			</div>';
        // Driver report.
        $report = new LDDFW_Reports();
        $report_array = $report->lddfw_drivers_commission_query( date_i18n( 'Y-m-d' ), date_i18n( 'Y-m-d' ), $driver_id );
        $commission = 0;
        if ( !empty( $report_array ) ) {
        }
        $lddfw_driver_commission_permission = get_option( 'lddfw_driver_commission_permission', false );
        $lddfw_driver_commission_permission = ( false === $lddfw_driver_commission_permission || '1' === $lddfw_driver_commission_permission ? true : false );
        if ( true === $lddfw_driver_commission_permission ) {
            $html .= '<div class="lddfw_box min lddfw-kpi-card lddfw-kpi-card--earnings">';
            $html .= '<div class="lddfw-kpi-card__body">';
            $html .= '<div class="lddfw-kpi-card__header"><div class="lddfw-kpi-card__title">' . esc_html( __( 'Today\'s Earnings', 'lddfw' ) ) . '</div></div>';
            $html .= '<div class="lddfw-kpi-card__value">';
            if ( lddfw_is_free() ) {
                $content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'See your exact earnings for today in real time.', 'lddfw' ) );
                $html .= lddfw_premium_feature_notice( '', $content, 'lddfw_inline' );
            } else {
                $html .= '<b>' . lddfw_premium_feature( wc_price( $commission ) ) . '</b>';
            }
            $html .= '</div></div>';
            $html .= '<div class="lddfw-kpi-card__icon-col"><span class="lddfw-kpi-card__icon" aria-hidden="true">' . $this->lddfw_dashboard_kpi_icon_svg( 'earnings' ) . '</span></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        // Primary CTA: Out for delivery (full-width, promoted).
        $out_cta_label = ( '' !== $lddfw_out_for_delivery_status_name ? $lddfw_out_for_delivery_status_name : esc_html__( 'Out for delivery', 'lddfw' ) );
        if ( $lddfw_out_for_delivery_counter > 0 ) {
            /* translators: %d: number of orders in the driver route */
            $out_route_order_label = _n(
                '%d order in your route',
                '%d orders in your route',
                $lddfw_out_for_delivery_counter,
                'lddfw'
            );
            $out_cta_sub = sprintf( $out_route_order_label, $lddfw_out_for_delivery_counter );
        } else {
            $out_cta_sub = esc_html__( 'No active deliveries right now', 'lddfw' );
        }
        $html .= '<a class="lddfw-primary-cta lddfw_loader' . (( 0 === (int) $lddfw_out_for_delivery_counter ? ' lddfw-primary-cta--empty' : '' )) . '" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) ) . '">
			<span class="lddfw-primary-cta__icon" aria-hidden="true">' . $this->lddfw_dashboard_order_stat_icon_svg( 'out_for_delivery' ) . '</span>
			<span class="lddfw-primary-cta__text">
				<span class="lddfw-primary-cta__label">' . esc_html( $out_cta_label ) . '</span>
				<span class="lddfw-primary-cta__sub">' . esc_html( $out_cta_sub ) . '</span>
			</span>
			<span class="lddfw-primary-cta__count">' . intval( $lddfw_out_for_delivery_counter ) . '</span>
			<span class="lddfw-primary-cta__chevron" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
			</span>
		</a>';
        $html .= '<div class="row">';
        $html .= '	<div class="col-6">
				<div class="lddfw_box min lddfw-order-stat-card lddfw-order-stat-card--assign">
				<a class="lddfw_loader lddfw-order-stat-card__link" href="' . lddfw_drivers_page_url( 'lddfw_screen=assigned_orders' ) . '">
					<div class="lddfw-order-stat-card__text">
						<span class="lddfw_label lddfw-order-stat-card__label">' . $lddfw_driver_assigned_status_name . '</span>
						<span class="lddfw_number">' . $lddfw_assign_to_driver_counter . '</span>
					</div>
					<div class="lddfw-order-stat-card__icon-col">
						<span class="lddfw-order-stat-card__icon" aria-hidden="true">' . $this->lddfw_dashboard_order_stat_icon_svg( 'assign_to_driver' ) . '</span>
					</div>
				</a>
				</div>
			</div>
			<div class="col-6">
				<div class="lddfw_box min lddfw-order-stat-card lddfw-order-stat-card--failed">
				<a class="lddfw_loader lddfw-order-stat-card__link" href="' . lddfw_drivers_page_url( 'lddfw_screen=failed_delivery' ) . '">
					<div class="lddfw-order-stat-card__text">
						<span class="lddfw_label lddfw-order-stat-card__label">' . $lddfw_failed_attempt_status_name . '</span>
						<span class="lddfw_number">' . $lddfw_failed_attempt_counter . '</span>
					</div>
					<div class="lddfw-order-stat-card__icon-col">
						<span class="lddfw-order-stat-card__icon" aria-hidden="true">' . $this->lddfw_dashboard_order_stat_icon_svg( 'failed_delivery' ) . '</span>
					</div>
				</a>
				</div>
			</div>
			<div class="col-6">
				<div class="lddfw_box min lddfw-order-stat-card lddfw-order-stat-card--delivered">
				<a class="lddfw_loader lddfw-order-stat-card__link" href="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">
					<div class="lddfw-order-stat-card__text">
						<span class="lddfw_label lddfw-order-stat-card__label">' . esc_html( __( 'Delivered Orders', 'lddfw' ) ) . '</span>
						<span class="lddfw_number">' . $lddfw_delivered_counter . '</span>
					</div>
					<div class="lddfw-order-stat-card__icon-col">
						<span class="lddfw-order-stat-card__icon" aria-hidden="true">' . $this->lddfw_dashboard_order_stat_icon_svg( 'delivered' ) . '</span>
					</div>
				</a>
				</div>
			</div>
		</div>
		</div>
		</div>
		</div>';
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Failed delivery screen.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_failed_delivery_screen( $driver_id ) {
        global $lddfw_failed_attempt_status_name, $lddfw_failed_attempt_counter;
        $title = $lddfw_failed_attempt_status_name;
        $back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
        $html = $this->lddfw_header( $title, $back_url );
        $html .= '<div id="lddfw_content" class="container lddfw_page_content lddfw-failed-screen">';
        // Hero section - red/warning variant.
        $hero_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';
        $html .= '<div class="lddfw-reviews-hero lddfw-reviews-hero--failed">';
        $html .= '<div class="lddfw-reviews-hero__avatar lddfw-reviews-hero__avatar--icon" aria-hidden="true">' . $hero_icon . '</div>';
        $html .= '<div class="lddfw-reviews-hero__identity">';
        $html .= '<div class="lddfw-reviews-hero__eyebrow">' . esc_html__( 'Failed attempts', 'lddfw' ) . '</div>';
        $html .= '<div class="lddfw-reviews-hero__name">' . esc_html( $title ) . '</div>';
        $failed_count = intval( $lddfw_failed_attempt_counter );
        if ( $failed_count > 0 ) {
            /* translators: %d: number of orders */
            $failed_order_label = _n(
                '%d order',
                '%d orders',
                $failed_count,
                'lddfw'
            );
            $count_text = sprintf( $failed_order_label, $failed_count );
            $html .= '<span class="lddfw-reviews-hero__count-pill">' . esc_html( $count_text ) . '</span>';
        }
        $html .= '</div></div>';
        $orders = new LDDFW_Orders();
        $html .= $orders->lddfw_failed_delivery( $driver_id );
        $html .= '</div>';
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Assigned orders screen (orders in driver-assigned status).
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_assign_to_driver_screen( $driver_id ) {
        global $lddfw_driver_assigned_status_name, $lddfw_assign_to_driver_counter;
        $title = $lddfw_driver_assigned_status_name;
        $back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
        $html = $this->lddfw_header( $title, $back_url );
        $orders = new LDDFW_Orders();
        $orders_count = intval( $lddfw_assign_to_driver_counter );
        // Map view gating - same conditions as the claim screen plus the
        // dedicated `lddfw_assign_show_map` admin toggle. Failure of any
        // condition means we ship the screen as it is today (list-only),
        // preserving 100% of existing behavior for installs without the
        // API key, free installs, and admins who explicitly opted out.
        $assign_map_enabled = false;
        $assign_map_key = '';
        if ( $orders_count > 0 && lddfw_fs()->is__premium_only() && lddfw_fs()->is_plan( 'premium', true ) && '0' !== get_option( 'lddfw_assign_show_map', '1' ) ) {
            $assign_map_key = (string) get_option( 'lddfw_google_api_key', '' );
            if ( '' !== $assign_map_key ) {
                $assign_map_enabled = true;
            }
        }
        $html .= '<div id="lddfw_content" class="container lddfw_page_content lddfw-assign-screen">';
        // Hero section - purple assign variant (mirrors the claim-orders blue hero).
        $hero_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M50.2 375.6c2.3 8.5 11.1 13.6 19.6 11.3l216.4-58c8.5-2.3 13.6-11.1 11.3-19.6l-49.7-185.5c-2.3-8.5-11.1-13.6-19.6-11.3L151 133.3l24.8 92.7-61.8 16.5-24.8-92.7-77.3 20.7C3.4 172.8-1.7 181.6.6 190.1l49.6 185.5zM384 0c-17.7 0-32 14.3-32 32v323.6L5.9 450c-4.3 1.2-6.8 5.6-5.6 9.8l12.6 46.3c1.2 4.3 5.6 6.8 9.8 5.6l393.7-107.4C418.8 464.1 467.6 512 528 512c61.9 0 112-50.1 112-112V0H384zm144 448c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48z"/></svg>';
        $html .= '<div class="lddfw-reviews-hero lddfw-reviews-hero--assign">';
        $html .= '<div class="lddfw-reviews-hero__avatar lddfw-reviews-hero__avatar--icon" aria-hidden="true">' . $hero_icon . '</div>';
        $html .= '<div class="lddfw-reviews-hero__identity">';
        $html .= '<div class="lddfw-reviews-hero__eyebrow">' . esc_html__( 'Plan today\'s route', 'lddfw' ) . '</div>';
        $html .= '<div class="lddfw-reviews-hero__name">' . esc_html__( 'Out for delivery', 'lddfw' ) . '</div>';
        if ( $orders_count > 0 ) {
            $html .= '<div class="lddfw-reviews-hero__subtitle">' . esc_html__( 'Select orders, preview your route, then send them out for delivery.', 'lddfw' ) . '</div>';
            /* translators: %d: number of orders assigned to this driver */
            $count_text = sprintf( _n(
                '%d order assigned',
                '%d orders assigned',
                $orders_count,
                'lddfw'
            ), $orders_count );
            $html .= '<span class="lddfw-reviews-hero__count-pill">' . esc_html( $count_text ) . '</span>';
        }
        $html .= '</div></div>';
        // Segmented List / Map control - only emitted when the map feature is
        // enabled. Hidden entirely otherwise so list-only installs see no new
        // chrome. CSS classes are deliberately shared with the claim screen
        // (`.lddfw-claim-viewswitch*`); the segmented pill is generic and any
        // future "list ↔ map" toggle on a third screen can reuse them.
        if ( $assign_map_enabled ) {
            $list_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 6h2v2H4V6zm0 5h2v2H4v-2zm0 5h2v2H4v-2zm4-10h12v2H8V6zm0 5h12v2H8v-2zm0 5h12v2H8v-2z"/></svg>';
            $map_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>';
            $html .= '<div class="lddfw-claim-viewswitch" role="tablist" aria-label="' . esc_attr__( 'Assigned orders view', 'lddfw' ) . '">' . '<button type="button" class="lddfw-claim-viewswitch__btn is-active" data-lddfw-assign-view="list" role="tab" aria-selected="true" aria-controls="lddfw_assign_list_container">' . '<span class="lddfw-claim-viewswitch__icon" aria-hidden="true">' . $list_icon . '</span>' . '<span class="lddfw-claim-viewswitch__label">' . esc_html__( 'List', 'lddfw' ) . '</span>' . '</button>' . '<button type="button" class="lddfw-claim-viewswitch__btn" data-lddfw-assign-view="map" role="tab" aria-selected="false" aria-controls="lddfw_assign_map_container">' . '<span class="lddfw-claim-viewswitch__icon" aria-hidden="true">' . $map_icon . '</span>' . '<span class="lddfw-claim-viewswitch__label">' . esc_html__( 'Map', 'lddfw' ) . '</span>' . '</button>' . '</div>';
        }
        if ( lddfw_is_free() && $orders_count > 0 ) {
            $content = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'See all assigned orders plotted on a live map.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Switch instantly between list and map view.', 'lddfw' ) ) . '
					<hr>' . lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Spot the fastest pickup order at a glance.', 'lddfw' ) );
            $html .= lddfw_premium_feature_notice( __( 'Map View', 'lddfw' ), $content, 'lddfw_inline' );
        }
        $html .= '<div id="lddfw_alert" style="display: none;"></div>';
        // List pane wrapper. The wrapper is emitted unconditionally - when the
        // map feature is off it has no visual impact (just a passthrough div),
        // when it is on the segmented control's JS toggles a class on the
        // `.lddfw-assign-screen` parent to hide this pane while keeping the
        // checkboxes in the DOM. The OFD AJAX handler reads the same checkbox
        // set regardless of which view is currently visible.
        $html .= '<div id="lddfw_assign_list_container" class="lddfw-claim-view-pane lddfw-claim-view-pane--list" role="tabpanel" aria-labelledby="lddfw_assign_view_list">';
        $html .= '<div id="lddfw_assign_to_driver_orders">';
        $html .= $orders->lddfw_assign_to_driver( $driver_id );
        $html .= '</div>';
        $html .= '</div>';
        // Map pane - only rendered when enabled so the markup never ships to
        // drivers on list-only installs. The pane itself is hidden by default
        // (CSS + the [hidden] attribute on the container).
        if ( $assign_map_enabled ) {
            $html .= $this->lddfw_assign_map_pane__premium_only( $driver_id, $assign_map_key );
        }
        $html .= '</div>';
        // Read-only route preview: only when premium is active AND a Google
        // Maps API key is configured. The preview never changes WC order
        // status nor triggers customer notifications - Out for delivery
        // remains the single commit button below. Mirrors LDDFW_Route
        // script globals (lddfw_google_api_key / _origin / travel mode)
        // so the assign screen can reuse the same Google Maps SDK loader.
        $preview_enabled = false;
        if ( 0 < $orders_count ) {
            // Inline `padding:0` on container / col-12 so Bootstrap's default
            // 15px gutter is stripped unconditionally inside the assign footer.
            // Mirrors the lddfw-claim-footer pattern so the post-submit two-
            // button success CTA can span the full footer width on narrow phones.
            $html .= '
		<div class="lddfw_footer_buttons lddfw-assign-footer">
			<div class="container" style="padding-left:0;padding-right:0;max-width:none;">
				<div class="row" style="margin-left:0;margin-right:0;">
					<div class="col-12" style="padding-left:0;padding-right:0;">';
            if ( $preview_enabled ) {
                // Secondary Preview-route button: hidden until at least one
                // order is selected (JS toggles via change handler). Outline
                // style mirrors the OFD screen's "View Route" secondary CTA
                // so both screens share the same visual hierarchy.
                $html .= '
						<div class="lddfw-preview-route-row">
							<a href="#" id="lddfw_preview_route_button" class="btn btn-lg btn-block lddfw-preview-route-btn" style="display:none">
								<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="route" class="svg-inline--fa fa-route fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M416 320h-96c-17.6 0-32-14.4-32-32s14.4-32 32-32h96s96-107 96-160-43-96-96-96-96 43-96 96c0 25.5 22.2 63.4 45.3 96H320c-52.9 0-96 43.1-96 96s43.1 96 96 96h96c17.6 0 32 14.4 32 32s-14.4 32-32 32H185.5c-16 24.8-33.8 47.7-47.3 64H416c52.9 0 96-43.1 96-96s-43.1-96-96-96zm0-256c17.7 0 32 14.3 32 32s-14.3 32-32 32-32-14.3-32-32 14.3-32 32-32zM96 256c-53 0-96 43-96 96s96 160 96 160 96-107 96-160-43-96-96-96zm0 128c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z"></path></svg>
								' . esc_html__( 'Preview route', 'lddfw' ) . '
							</a>
							<a href="#" id="lddfw_preview_route_button_loading" style="display:none" class="btn btn-lg btn-block lddfw-preview-route-btn">
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								' . esc_html__( 'Building preview...', 'lddfw' ) . '
							</a>
						</div>';
            }
            $html .= '
						<a href="#" id="lddfw_out_for_delivery_button" class="btn btn-lg btn-block btn-success"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="truck-loading" class="svg-inline--fa fa-truck-loading fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M50.2 375.6c2.3 8.5 11.1 13.6 19.6 11.3l216.4-58c8.5-2.3 13.6-11.1 11.3-19.6l-49.7-185.5c-2.3-8.5-11.1-13.6-19.6-11.3L151 133.3l24.8 92.7-61.8 16.5-24.8-92.7-77.3 20.7C3.4 172.8-1.7 181.6.6 190.1l49.6 185.5zM384 0c-17.7 0-32 14.3-32 32v323.6L5.9 450c-4.3 1.2-6.8 5.6-5.6 9.8l12.6 46.3c1.2 4.3 5.6 6.8 9.8 5.6l393.7-107.4C418.8 464.1 467.6 512 528 512c61.9 0 112-50.1 112-112V0H384zm144 448c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48z"></path></svg> ' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</a>
						<a href="#" id="lddfw_out_for_delivery_button_loading"  style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-success"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						' . esc_html( __( 'Loading', 'lddfw' ) ) . '</a>
					</div>
				</div>
			</div>
		</div>';
        }
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Delivered screen.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_delivered_screen( $driver_id ) {
        $driver_prices_permission = get_option( 'lddfw_driver_prices_permission', false );
        $lddfw_driver_commission_permission = get_option( 'lddfw_driver_commission_permission', false );
        $driver_prices_permission = ( false === $driver_prices_permission || '1' === $driver_prices_permission ? true : false );
        $lddfw_driver_commission_permission = ( false === $lddfw_driver_commission_permission || '1' === $lddfw_driver_commission_permission ? true : false );
        // This week dates.
        $current_week = get_weekstartend( date_i18n( 'Y-m-d' ), '' );
        $current_start_week = gmdate( 'Y-m-d', $current_week['start'] );
        $current_end_week = gmdate( 'Y-m-d', $current_week['end'] );
        // Last week dates.
        $previous_start_week = gmdate( 'Y-m-d', strtotime( $current_start_week . ' -7 day' ) );
        $previous_end_week = gmdate( 'Y-m-d', strtotime( $current_end_week . ' -7 day' ) );
        global $lddfw_dates;
        $title = __( 'Delivered', 'lddfw' );
        $back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
        $html = $this->lddfw_header( $title, $back_url );
        $today_val = date_i18n( 'Y-m-d' );
        $yesterday_val = date_i18n( 'Y-m-d', strtotime( '-1 days' ) );
        $month_start = date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) );
        $month_end = date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) );
        $last_month_start = date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) );
        $last_month_end = date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) );
        $date_options = array(
            $today_val . ',' . $today_val                   => __( 'Today', 'lddfw' ),
            $yesterday_val . ',' . $yesterday_val           => __( 'Yesterday', 'lddfw' ),
            $current_start_week . ',' . $current_end_week   => __( 'This week', 'lddfw' ),
            $previous_start_week . ',' . $previous_end_week => __( 'Last week', 'lddfw' ),
            $month_start . ',' . $month_end                 => __( 'This month', 'lddfw' ),
            $last_month_start . ',' . $last_month_end       => __( 'Last month', 'lddfw' ),
        );
        // Resolve the selected range (default = today) and friendly date display.
        if ( '' === $lddfw_dates ) {
            $from_date = $today_val;
            $to_date = $today_val;
            $date_display = date_i18n( lddfw_date_format( 'date' ) );
            $selected_preset = $today_val . ',' . $today_val;
        } else {
            $lddfw_dates_array = explode( ',', $lddfw_dates );
            if ( 1 < count( $lddfw_dates_array ) ) {
                $from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                $to_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[1] ) );
                if ( $lddfw_dates_array[0] === $lddfw_dates_array[1] ) {
                    $date_display = date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[0] ) );
                } else {
                    $date_display = date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[0] ) ) . ' – ' . date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[1] ) );
                }
            } else {
                $from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                $to_date = $from_date;
                $date_display = date_i18n( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_array[0] ) );
            }
            $selected_preset = $from_date . ',' . $to_date;
        }
        // Friendly preset label for the hero (falls back to the date display).
        $hero_range_label = ( isset( $date_options[$selected_preset] ) ? $date_options[$selected_preset] : $date_display );
        // Driver report.
        $report = new LDDFW_Reports();
        $report_array = $report->lddfw_drivers_commission_query( $from_date, $to_date, $driver_id );
        $orders_counter = 0;
        $orders_price = 0;
        $shipping_price = 0;
        $commission = 0;
        $has_report = !empty( $report_array );
        if ( $has_report ) {
            $orders_counter = intval( $report_array[0]->orders );
        }
        $premium_notice_orders_total = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Track your total order revenue for any date range.', 'lddfw' ) );
        $premium_notice_shipping_total = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'See your total shipping earnings across delivered orders.', 'lddfw' ) );
        $premium_notice_commission = lddfw_premium_feature( '' ) . ' ' . esc_html( __( 'Know your exact commission on every delivery.', 'lddfw' ) );
        $html .= '<div id="lddfw_content" class="container lddfw_page_content lddfw-delivered-screen">';
        // Hero.
        $hero_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
        $html .= '<div class="lddfw-reviews-hero lddfw-reviews-hero--delivered">';
        $html .= '<div class="lddfw-reviews-hero__avatar lddfw-reviews-hero__avatar--icon" aria-hidden="true">' . $hero_icon . '</div>';
        $html .= '<div class="lddfw-reviews-hero__identity">';
        $html .= '<div class="lddfw-reviews-hero__eyebrow">' . esc_html__( 'Delivered orders', 'lddfw' ) . '</div>';
        $html .= '<div class="lddfw-reviews-hero__name">' . esc_html( $hero_range_label ) . '</div>';
        if ( $has_report && $orders_counter > 0 ) {
            /* translators: %d: number of orders */
            $delivered_order_label = _n(
                '%d order',
                '%d orders',
                $orders_counter,
                'lddfw'
            );
            $count_text = sprintf( $delivered_order_label, $orders_counter );
            $html .= '<span class="lddfw-reviews-hero__count-pill">' . esc_html( $count_text ) . '</span>';
        }
        $html .= '</div></div>';
        // Date-range filter bar.
        $html .= '<div class="lddfw-delivered-filter">';
        $html .= '<span class="lddfw-delivered-filter__label"><span class="lddfw-delivered-filter__label-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM7 12h5v5H7z"/></svg></span>' . esc_html__( 'Range', 'lddfw' ) . '</span>';
        $html .= '<span class="lddfw-delivered-filter__select"><select class="custom-select form-control custom-select-lg" id="lddfw_dates_range" data="' . esc_attr( lddfw_drivers_page_url( 'lddfw_screen=delivered' ) ) . '">';
        foreach ( $date_options as $value => $label ) {
            $selected = ( $value === $selected_preset ? ' selected' : '' );
            $html .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        $html .= '</select></span>';
        $html .= '<span class="lddfw-delivered-filter__current lddfw_date_range">' . esc_html( $date_display ) . '</span>';
        $html .= '</div>';
        // Stats grid - mirrors the columns the old report row used.
        if ( $has_report ) {
            $icon_orders = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
            $icon_money = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1H6.32c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>';
            $icon_shipping = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm12 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM17 12V9.5h2.5l1.96 2.5H17z"/></svg>';
            $icon_commission = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.11-.9-2-2-2zm-8.47 12.47L8.2 13.13l-1.07 1.07 3.4 3.4 7-7-1.07-1.06-5.93 5.93z"/></svg>';
            $html .= '<div class="lddfw-delivered-stats">';
            // Orders count - always visible.
            $html .= '<div class="lddfw-delivered-stat">';
            $html .= '<div class="lddfw-delivered-stat__text">';
            $html .= '<span class="lddfw-delivered-stat__value">' . esc_html( number_format_i18n( $orders_counter ) ) . '</span>';
            $html .= '<span class="lddfw-delivered-stat__label">' . esc_html__( 'Orders', 'lddfw' ) . '</span>';
            $html .= '</div>';
            $html .= '<span class="lddfw-delivered-stat__icon lddfw-delivered-stat__icon--success" aria-hidden="true">' . $icon_orders . '</span>';
            $html .= '</div>';
            if ( true === $driver_prices_permission ) {
                // Orders Total.
                $html .= '<div class="lddfw-delivered-stat">';
                $html .= '<div class="lddfw-delivered-stat__text">';
                $html .= '<span class="lddfw-delivered-stat__value">';
                if ( lddfw_is_free() ) {
                    $html .= lddfw_premium_feature_notice( '', $premium_notice_orders_total, 'lddfw_inline' );
                } else {
                    $html .= lddfw_premium_feature( lddfw_price( $driver_prices_permission, wc_price( $orders_price ) ) );
                }
                $html .= '</span>';
                $html .= '<span class="lddfw-delivered-stat__label">' . esc_html__( 'Orders Total', 'lddfw' ) . '</span>';
                $html .= '</div>';
                $html .= '<span class="lddfw-delivered-stat__icon lddfw-delivered-stat__icon--money" aria-hidden="true">' . $icon_money . '</span>';
                $html .= '</div>';
                // Shipping Total.
                $html .= '<div class="lddfw-delivered-stat">';
                $html .= '<div class="lddfw-delivered-stat__text">';
                $html .= '<span class="lddfw-delivered-stat__value">';
                if ( lddfw_is_free() ) {
                    $html .= lddfw_premium_feature_notice( '', $premium_notice_shipping_total, 'lddfw_inline' );
                } else {
                    $html .= lddfw_premium_feature( lddfw_price( $driver_prices_permission, wc_price( $shipping_price ) ) );
                }
                $html .= '</span>';
                $html .= '<span class="lddfw-delivered-stat__label">' . esc_html__( 'Shipping Total', 'lddfw' ) . '</span>';
                $html .= '</div>';
                $html .= '<span class="lddfw-delivered-stat__icon lddfw-delivered-stat__icon--shipping" aria-hidden="true">' . $icon_shipping . '</span>';
                $html .= '</div>';
            }
            if ( true === $lddfw_driver_commission_permission ) {
                // Commission.
                $html .= '<div class="lddfw-delivered-stat">';
                $html .= '<div class="lddfw-delivered-stat__text">';
                $html .= '<span class="lddfw-delivered-stat__value">';
                if ( lddfw_is_free() ) {
                    $html .= lddfw_premium_feature_notice( '', $premium_notice_commission, 'lddfw_inline' );
                } else {
                    $html .= lddfw_premium_feature( wc_price( $commission ) );
                }
                $html .= '</span>';
                $html .= '<span class="lddfw-delivered-stat__label">' . esc_html__( 'Commission', 'lddfw' ) . '</span>';
                $html .= '</div>';
                $html .= '<span class="lddfw-delivered-stat__icon lddfw-delivered-stat__icon--commission" aria-hidden="true">' . $icon_commission . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $orders = new LDDFW_Orders();
        $html .= $orders->lddfw_delivered( $driver_id );
        $html .= '</div>';
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Order screen.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_order_screen( $driver_id ) {
        global $lddfw_order_id;
        $order_class = new LDDFW_Order();
        $orders_class = new LDDFW_Orders();
        $back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
        $order_driverid = '';
        $title = __( 'Order', 'lddfw' );
        $driver_claim_permission = false;
        $order = false;
        // Check if the order type is 'shop_order'.
        if ( 'shop_order' !== OrderUtil::get_order_type( $lddfw_order_id ) ) {
            $html = $this->lddfw_header( $title, $back_url );
            $html .= '<div style="margin-top:100px" class="alert alert-danger">' . esc_html( __( 'Invalid order type.', 'lddfw' ) ) . '</div>';
            $html .= $this->lddfw_footer();
            return $html;
        }
        // Get the order.
        $order = wc_get_order( $lddfw_order_id );
        if ( !$order ) {
            $html = $this->lddfw_header( $title, $back_url );
            $html .= '<div style="margin-top:100px" class="alert alert-danger">' . esc_html( __( 'Order not found.', 'lddfw' ) ) . '</div>';
            $html .= $this->lddfw_footer();
            return $html;
        }
        // Get order metadata.
        $order_driverid = $order->get_meta( 'lddfw_driverid' );
        $order_status = $order->get_status();
        // Set the back URL based on order status.
        switch ( 'wc-' . $order_status ) {
            case get_option( 'lddfw_delivered_status' ):
                $back_url = lddfw_drivers_page_url( 'lddfw_screen=delivered' );
                break;
            case get_option( 'lddfw_failed_attempt_status' ):
                $back_url = lddfw_drivers_page_url( 'lddfw_screen=failed_delivery' );
                break;
            case get_option( 'lddfw_out_for_delivery_status' ):
                $back_url = lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' );
                break;
            case get_option( 'lddfw_driver_assigned_status' ):
                $back_url = lddfw_drivers_page_url( 'lddfw_screen=assigned_orders' );
                break;
        }
        $title = __( 'Order #', 'lddfw' ) . ' ' . $order->get_order_number();
        // Append additional parameters to the back URL if present.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET params for building a back URL; no data mutation.
        $back_url = ( isset( $_GET['lddfw_dates'] ) ? $back_url . '&lddfw_dates=' . sanitize_text_field( wp_unslash( $_GET['lddfw_dates'] ) ) : $back_url );
        $back_url = ( isset( $_GET['lddfw_page'] ) ? $back_url . '&lddfw_page=' . sanitize_text_field( wp_unslash( $_GET['lddfw_page'] ) ) : $back_url );
        // Generate HTML header.
        $html = $this->lddfw_header( $title, $back_url );
        // Show the order page if the driver has permission.
        if ( $order_driverid !== '' && intval( $order_driverid ) === intval( $driver_id ) || $driver_claim_permission ) {
            $html .= $order_class->lddfw_order_page( $order, $driver_id );
        } else {
            $html .= '<div style="margin-top:100px" class="alert alert-danger">' . esc_html( __( 'Access Denied. You do not have permission to access this order.', 'lddfw' ) ) . '</div>';
        }
        // Generate HTML footer and return the complete HTML.
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Edit driver screen.
     *
     * @since 1.5.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_driver_settings_screen( $driver_id ) {
        $back_url = lddfw_drivers_page_url( 'lddfw_screen=dashboard' );
        $title = esc_html( __( 'Settings', 'lddfw' ) );
        $html = $this->lddfw_header( $title, $back_url );
        $driver = new LDDFW_Driver();
        $html .= $driver->lddfw_edit_driver_form( $driver_id );
        $html .= $this->lddfw_footer();
        return $html;
    }

    /**
     * Decorative SVG icon for driver dashboard KPI cards.
     *
     * @param string $which availability|tracking|rating|earnings.
     * @return string
     */
    private function lddfw_dashboard_kpi_icon_svg( $which ) {
        $icons = array(
            'availability' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-kpi-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
            'tracking'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-kpi-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>',
            'rating'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-kpi-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>',
            'earnings'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-kpi-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>',
        );
        return ( isset( $icons[$which] ) ? $icons[$which] : '' );
    }

    /**
     * Decorative SVG icon for driver dashboard order summary cards.
     *
     * @param string $which claim_orders|assign_to_driver|out_for_delivery|failed_delivery|delivered.
     * @return string
     */
    private function lddfw_dashboard_order_stat_icon_svg( $which ) {
        $icons = array(
            'claim_orders'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-order-stat-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>',
            'assign_to_driver' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-order-stat-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 5c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 11H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/></svg>',
            'out_for_delivery' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-order-stat-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM5.5 19c-.83 0-1.5-.67-1.5-1.5S4.67 16 5.5 16s1.5.67 1.5 1.5S6.33 19 5.5 19zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM17 12h4l2.5 3H17v-3z"/></svg>',
            'failed_delivery'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-order-stat-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
            'delivered'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="lddfw-order-stat-card__icon-svg" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
        );
        return ( isset( $icons[$which] ) ? $icons[$which] : '' );
    }

}
