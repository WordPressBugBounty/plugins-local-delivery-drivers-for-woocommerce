<?php

/**
 * Drivers & Applications admin page.
 *
 * Consolidates the list of active drivers and the driver application queue
 * into a single admin screen with tabs. The Applications tab is visible for
 * all builds; the queue UI is premium-only and the free build shows an upgrade notice.
 *
 * @link  http://www.powerfulwp.com
 * @since 2.3.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Drivers & Applications admin page.
 */
class LDDFW_Drivers_Page {
    /** Per-page limit for both tabs. */
    const PER_PAGE = 20;

    /**
     * Main entry point – renders the page wrapped in a .wrap container.
     *
     * @return void
     */
    public static function render() {
        $tab = ( isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'drivers' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( !in_array( $tab, array('drivers', 'applications'), true ) ) {
            $tab = 'drivers';
        }
        $apps_available = lddfw_fs()->is__premium_only() && lddfw_fs()->can_use_premium_code();
        echo '<div class="wrap lddfw-drivers-page">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Drivers & Applications', 'lddfw' ) . '</h1>';
        if ( 'drivers' === $tab ) {
            echo ' <a href="#" class="page-title-action lddfw-driver-create">' . esc_html__( 'Add new driver', 'lddfw' ) . '</a>';
        }
        echo '<hr class="wp-header-end">';
        self::render_tabs_nav( $tab, $apps_available );
        if ( 'drivers' === $tab ) {
            self::render_drivers_tab();
        } elseif ( $apps_available ) {
            self::render_applications_tab__premium_only();
        } else {
            self::render_applications_tab_free_gate();
        }
        // Hidden modal containers used by jQuery UI Dialog.
        echo '<div id="lddfw-driver-modal" style="display:none;"></div>';
        echo '<div id="lddfw-application-modal" style="display:none;"></div>';
        // AJAX config for the inline JS on this page.
        echo '<script type="text/javascript">window.lddfwDriversPage = ' . wp_json_encode( array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'lddfw-drivers-page' ),
            'i18n'           => array(
                'addNew'             => __( 'Add new driver', 'lddfw' ),
                'edit'               => __( 'Edit driver', 'lddfw' ),
                'save'               => __( 'Save', 'lddfw' ),
                'cancel'             => __( 'Cancel', 'lddfw' ),
                'saving'             => __( 'Saving...', 'lddfw' ),
                'error'              => __( 'Something went wrong.', 'lddfw' ),
                'confirmApprove'     => __( 'Approve this application? A driver account will be created and the applicant will receive a set-password link.', 'lddfw' ),
                'confirmReject'      => __( 'Reject this application?', 'lddfw' ),
                'confirmDelete'      => __( 'Delete this application? This cannot be undone.', 'lddfw' ),
                'rejectionReason'    => __( 'Optional reason (will be included in the notification):', 'lddfw' ),
                'inactiveDriver'     => __( 'Inactive', 'lddfw' ),
                'applicationDetails' => __( 'Application details', 'lddfw' ),
                'viewDetails'        => __( 'View details', 'lddfw' ),
                'loadingDetails'     => __( 'Loading...', 'lddfw' ),
                'selectPhoto'        => __( 'Select photo', 'lddfw' ),
                'usePhoto'           => __( 'Use this photo', 'lddfw' ),
            ),
            'placeholderUrl' => plugins_url() . '/' . LDDFW_FOLDER . '/public/images/user.png?ver=' . LDDFW_VERSION,
        ) ) . ';</script>';
        echo '</div>';
    }

    /**
     * Tabs navigation (WP nav-tab-wrapper).
     *
     * @param string $active          Active tab.
     * @param bool   $apps_available  Whether premium application queue is active (for “new” count badge).
     * @return void
     */
    protected static function render_tabs_nav( $active, $apps_available ) {
        $drivers_url = admin_url( 'admin.php?page=lddfw-drivers&tab=drivers' );
        $apps_url = admin_url( 'admin.php?page=lddfw-drivers&tab=applications' );
        echo '<nav class="nav-tab-wrapper lddfw-drivers-nav">';
        echo '<a href="' . esc_url( $drivers_url ) . '" class="nav-tab ' . (( 'drivers' === $active ? 'nav-tab-active' : '' )) . '">';
        echo '<span class="dashicons dashicons-admin-users"></span> ' . esc_html__( 'Drivers', 'lddfw' );
        echo '</a>';
        $pending = 0;
        if ( $apps_available ) {
            $pending = self::count_applications_by_status( 'new' );
        }
        echo '<a href="' . esc_url( $apps_url ) . '" class="nav-tab ' . (( 'applications' === $active ? 'nav-tab-active' : '' )) . '">';
        echo '<span class="dashicons dashicons-clipboard"></span> ' . esc_html__( 'Applications', 'lddfw' );
        if ( $pending > 0 ) {
            echo ' <span class="lddfw-tab-badge">' . esc_html( (string) $pending ) . '</span>';
        }
        echo '</a>';
        echo '</nav>';
    }

    /**
     * Applications tab on free build: same headline/subtitle pattern as lddfw_premium_feature_notice_content + CTA.
     *
     * @return void
     */
    protected static function render_applications_tab_free_gate() {
        $pricing_url = 'https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium#pricing';
        $star = ( function_exists( 'lddfw_premium_feature' ) ? lddfw_premium_feature( '' ) : '' );
        $bullets = '<p>' . $star . ' ' . esc_html__( 'Let drivers apply directly from your website.', 'lddfw' ) . '</p>';
        $bullets .= '<p>' . $star . ' ' . esc_html__( 'Manage all applications from one clean admin screen.', 'lddfw' ) . '</p>';
        $bullets .= '<p>' . $star . ' ' . esc_html__( 'Send instant email, SMS, or WhatsApp notifications on every status change.', 'lddfw' ) . '</p>';
        echo '<div class="lddfw-page-section">';
        echo '<div class="lddfw-applications-premium-gate">';
        if ( function_exists( 'lddfw_premium_feature_notice_content' ) ) {
            echo lddfw_premium_feature_notice_content( $bullets );
        } else {
            echo '<div class="lddfw_premium-feature-content"><h2>' . esc_html__( 'Premium Feature', 'lddfw' ) . '</h2>';
            echo '<p>' . esc_html__( 'You Discovered a Premium Feature!', 'lddfw' ) . '</p>';
            echo wp_kses_post( $bullets );
            echo '</div>';
        }
        echo '<p class="lddfw-applications-premium-gate__cta">';
        echo '<a href="' . esc_url( lddfw_fs()->checkout_url() ) . '" class="button button-primary button-hero" target="_blank" rel="noopener noreferrer">' . esc_html__( 'UNLOCK PREMIUM', 'lddfw' ) . '</a>';
        echo '</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Drivers tab with search + pagination.
     *
     * Uses the same "modern table" look as the dashboard drivers widget
     * and keeps the availability / claim toggle AJAX handlers working via
     * the existing `.lddfw_availability_icon` / `.lddfw_claim_icon` / `.lddfw_account_icon` selectors
     * bound in `admin/js/lddfw-admin.js`.
     *
     * @return void
     */
    protected static function render_drivers_tab() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET params for admin list-table display.
        $search = ( isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '' );
        $paged = max( 1, ( isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 ) );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $per_page = self::PER_PAGE;
        $args = array(
            'role'           => 'driver',
            'number'         => $per_page,
            'offset'         => ($paged - 1) * $per_page,
            'search'         => ( '' !== $search ? '*' . esc_attr( $search ) . '*' : '' ),
            'search_columns' => array(
                'user_login',
                'user_nicename',
                'user_email',
                'display_name'
            ),
            'orderby'        => 'display_name',
            'order'          => 'ASC',
            'count_total'    => true,
        );
        $q = new WP_User_Query($args);
        $drivers = $q->get_results();
        $total = (int) $q->get_total();
        $pages = max( 1, ceil( $total / $per_page ) );
        $badges = array();
        if ( class_exists( 'LDDFW_Reports' ) ) {
            $reports_tmp = new LDDFW_Reports();
            if ( method_exists( $reports_tmp, 'get_driver_badges' ) ) {
                $badges = $reports_tmp->get_driver_badges();
            }
        }
        $is_premium = lddfw_fs()->is__premium_only() && lddfw_fs()->can_use_premium_code();
        echo '<div class="lddfw-page-section">';
        // Search / filter bar.
        echo '<form method="get" class="lddfw-drivers-filter">';
        echo '<input type="hidden" name="page" value="lddfw-drivers">';
        echo '<input type="hidden" name="tab" value="drivers">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="lddfw-drivers-search">' . esc_html__( 'Search drivers', 'lddfw' ) . '</label>';
        echo '<input type="search" id="lddfw-drivers-search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search name or email', 'lddfw' ) . '">';
        echo '<input type="submit" class="button" value="' . esc_attr__( 'Search', 'lddfw' ) . '">';
        echo '</p>';
        echo '</form>';
        echo '<div class="lddfw-modern-table-wrap">';
        echo '<table class="lddfw-modern-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Driver', 'lddfw' ) . '</th>';
        echo '<th>' . esc_html__( 'Phone', 'lddfw' ) . '</th>';
        echo '<th>' . esc_html__( 'Email', 'lddfw' ) . '</th>';
        echo '<th>' . esc_html__( 'Address', 'lddfw' ) . '</th>';
        echo '<th>' . esc_html__( 'Vehicle', 'lddfw' ) . '</th>';
        echo '<th>' . esc_html__( 'Cities (auto-assign)', 'lddfw' ) . '</th>';
        echo '<th class="lddfw-text-center">' . esc_html__( 'Availability', 'lddfw' ) . '</th>';
        echo '<th class="lddfw-text-center">' . esc_html__( 'Claim orders', 'lddfw' ) . '</th>';
        echo '<th class="lddfw-text-center">' . esc_html__( 'Account active', 'lddfw' ) . '</th>';
        echo '<th class="lddfw-text-center">' . esc_html__( 'Actions', 'lddfw' ) . '</th>';
        echo '</tr></thead><tbody>';
        $visible_total = 0;
        if ( empty( $drivers ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="10">' . esc_html__( 'No drivers found.', 'lddfw' ) . '</td></tr>';
        } else {
            foreach ( $drivers as $d ) {
                $did = (int) $d->ID;
                $full_name = (string) $d->display_name;
                $email = (string) $d->user_email;
                $phone = (string) get_user_meta( $did, 'billing_phone', true );
                $availability = (string) get_user_meta( $did, 'lddfw_driver_availability', true );
                $driver_claim = (string) get_user_meta( $did, 'lddfw_driver_claim', true );
                $account = (string) get_user_meta( $did, 'lddfw_driver_account', true );
                $billing_address_1 = (string) get_user_meta( $did, 'billing_address_1', true );
                $billing_address_2 = (string) get_user_meta( $did, 'billing_address_2', true );
                $billing_city = (string) get_user_meta( $did, 'billing_city', true );
                $billing_company = (string) get_user_meta( $did, 'billing_company', true );
                $vehicle = (string) get_user_meta( $did, 'lddfw_driver_vehicle', true );
                $cities_meta = get_user_meta( $did, 'lddfw_driver_cities', true );
                $assign_cities = array();
                if ( is_array( $cities_meta ) ) {
                    foreach ( $cities_meta as $c ) {
                        $c = trim( (string) $c );
                        if ( '' !== $c ) {
                            $assign_cities[] = $c;
                        }
                    }
                }
                $assign_cities_str = implode( ', ', $assign_cities );
                $billing_address = '';
                if ( '' !== $billing_company ) {
                    $billing_address .= $billing_company . ', ';
                }
                if ( '' !== $billing_address_1 ) {
                    $billing_address .= $billing_address_1;
                }
                if ( '' !== $billing_address_2 ) {
                    $billing_address .= ', ' . $billing_address_2;
                }
                if ( '' !== $billing_city ) {
                    $billing_address .= ', ' . $billing_city;
                }
                $availability_icon = '';
                $driver_claim_icon = '';
                $driver_account_icon = '';
                if ( $is_premium ) {
                    if ( '1' === $availability ) {
                        $availability_icon = '<a href="#" class="lddfw_availability_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $did ) . '" ><i class="lddfw-toggle-on"></i></a>';
                    } else {
                        $availability_icon = '<a href="#" class="lddfw_availability_icon lddfw_icon" driver_id="' . esc_attr( $did ) . '" ><i class="lddfw-toggle-off"></i></a>';
                    }
                    if ( '1' === $driver_claim ) {
                        $driver_claim_icon = '<a href="#" class="lddfw_claim_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $did ) . '" ><i class="lddfw-toggle-on"></i></a>';
                    } else {
                        $driver_claim_icon = '<a href="#" class="lddfw_claim_icon lddfw_icon" driver_id="' . esc_attr( $did ) . '" ><i class="lddfw-toggle-off"></i></a>';
                    }
                    if ( '1' === $account ) {
                        $driver_account_icon = '<a href="#" class="lddfw_account_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $did ) . '" ><i class="lddfw-toggle-on"></i></a>';
                    } else {
                        $driver_account_icon = '<a href="#" class="lddfw_account_icon lddfw_icon" driver_id="' . esc_attr( $did ) . '" ><i class="lddfw-toggle-off"></i></a>';
                    }
                }
                $avatar = ( class_exists( 'LDDFW_Reports' ) ? LDDFW_Reports::lddfw_render_avatar( $full_name, $did ) : '' );
                $badge_html = ( isset( $badges[$did] ) && class_exists( 'LDDFW_Reports' ) ? ' ' . LDDFW_Reports::lddfw_render_driver_badge( $badges[$did] ) : '' );
                $inactive_badge = '';
                if ( '1' !== $account ) {
                    $inactive_badge = ' <span class="lddfw-badge lddfw-badge-muted lddfw-driver-inactive-badge">' . esc_html__( 'Inactive', 'lddfw' ) . '</span>';
                }
                $visible_total++;
                echo '<tr data-driver-id="' . esc_attr( $did ) . '">';
                echo '<td>' . $avatar . '<a href="#" class="lddfw-driver-edit" data-driver-id="' . esc_attr( $did ) . '">' . esc_html( $full_name ) . '</a>' . $badge_html . $inactive_badge . '<div class="row-actions">' . '<span class="edit"><a href="#" class="lddfw-driver-edit" data-driver-id="' . esc_attr( $did ) . '">' . esc_html__( 'Edit', 'lddfw' ) . '</a> | </span>' . '<span class="view"><a href="' . esc_url( get_edit_user_link( $did ) ) . '">' . esc_html__( 'WP profile', 'lddfw' ) . '</a></span>' . '</div>' . '</td>';
                echo '<td>' . (( '' !== $phone ? '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>' : '&mdash;' )) . '</td>';
                echo '<td>' . (( '' !== $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '&mdash;' )) . '</td>';
                echo '<td>' . (( '' !== $billing_address ? esc_html( $billing_address ) : '&mdash;' )) . '</td>';
                $vehicle_cell = ( '' !== $vehicle ? esc_html( $vehicle ) : '&mdash;' );
                echo '<td class="lddfw-driver-col-vehicle">' . lddfw_admin_premium_feature( $vehicle_cell ) . '</td>';
                $cities_cell_parts = array();
                if ( '' !== $billing_city ) {
                    $cities_cell_parts[] = '<span class="lddfw-driver-col-cities__address"><span class="screen-reader-text">' . esc_html__( 'Address city: ', 'lddfw' ) . '</span>' . esc_html( $billing_city ) . '</span>';
                }
                if ( '' !== $assign_cities_str ) {
                    $cities_cell_parts[] = '<span class="lddfw-driver-col-cities__list"><span class="screen-reader-text">' . esc_html__( 'Comma-separated cities for auto-assign: ', 'lddfw' ) . '</span>' . esc_html( $assign_cities_str ) . '</span>';
                }
                $cities_cell = ( !empty( $cities_cell_parts ) ? implode( '<br class="lddfw-driver-col-cities__br" />', $cities_cell_parts ) : '&mdash;' );
                echo '<td class="lddfw-driver-col-cities">' . lddfw_admin_premium_feature( $cities_cell ) . '</td>';
                echo '<td class="lddfw-text-center">' . lddfw_admin_premium_feature( $availability_icon ) . '</td>';
                echo '<td class="lddfw-text-center">' . lddfw_admin_premium_feature( $driver_claim_icon ) . '</td>';
                echo '<td class="lddfw-text-center">' . lddfw_admin_premium_feature( $driver_account_icon ) . '</td>';
                echo '<td class="lddfw-text-center"><button type="button" class="button button-small lddfw-driver-edit" data-driver-id="' . esc_attr( $did ) . '">' . esc_html__( 'Edit', 'lddfw' ) . '</button></td>';
                echo '</tr>';
            }
        }
        $driver_label = ( 1 < $total ? __( 'Drivers', 'lddfw' ) : __( 'Driver', 'lddfw' ) );
        echo '</tbody>';
        echo '<tfoot>';
        echo '<tr>';
        echo '<td>' . esc_html( number_format_i18n( $total ) . ' ' . $driver_label ) . '</td>';
        echo '<td></td><td></td><td></td><td></td><td></td>';
        echo '<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_available_counter"></span> ' . esc_html__( 'Available', 'lddfw' ) . ' | <span id="lddfw_unavailable_counter"></span> ' . esc_html__( 'Unavailable', 'lddfw' ) ) . '</td>';
        echo '<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_claim_counter"></span> ' . esc_html__( 'Can claim', 'lddfw' ) . ' | <span id="lddfw_unclaim_counter"></span> ' . esc_html__( "Cannot claim", 'lddfw' ) ) . '</td>';
        echo '<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_account_active_counter"></span> ' . esc_html__( 'Active', 'lddfw' ) . ' | <span id="lddfw_account_inactive_counter"></span> ' . esc_html__( 'Inactive', 'lddfw' ) ) . '</td>';
        echo '<td></td>';
        echo '</tr>';
        echo '</tfoot>';
        echo '</table></div>';
        echo '</div>';
        self::render_pagination(
            $paged,
            $pages,
            $total,
            'drivers',
            array(
                's' => $search,
            )
        );
        // Init the footer counters after the table is rendered.
        if ( $is_premium ) {
            echo '<script>jQuery(function($){ if (typeof lddfw_counters === "function") { lddfw_counters(); } });</script>';
        }
    }

    /**
     * Nonced URL for application upload (admin-post).
     *
     * Important: we must not call add_query_arg() on the output of wp_nonce_url(),
     * because wp_nonce_url() returns an esc_html'd URL (with &amp;), and add_query_arg()
     * then treats "&amp;value_id" as a single arg, producing a broken URL.
     *
     * @param int  $value_id Field value row ID.
     * @param bool $inline   When true, adds lddfw_inline=1 so the handler serves the
     *                       file with Content-Disposition: inline (for <img src>).
     * @return string
     */
    protected static function application_file_url( $value_id, $inline = false ) {
        $value_id = (int) $value_id;
        if ( $value_id <= 0 ) {
            return '';
        }
        $base = admin_url( 'admin-post.php?action=lddfw_download_application_file&value_id=' . $value_id );
        if ( $inline ) {
            $base .= '&lddfw_inline=1';
        }
        return wp_nonce_url( $base, 'lddfw_download_file_' . $value_id );
    }

    /**
     * Download URL (attachment disposition).
     *
     * @param int $value_id Field value row ID.
     * @return string
     */
    protected static function application_file_download_url( $value_id ) {
        return self::application_file_url( $value_id, false );
    }

    /**
     * Inline preview URL (image-only, Content-Disposition: inline).
     * Note: we intentionally use "lddfw_inline" - "preview" is a WordPress core
     * query variable and breaks admin-post routing.
     *
     * @param int $value_id Field value row ID.
     * @return string
     */
    protected static function application_file_preview_url( $value_id ) {
        return self::application_file_url( $value_id, true );
    }

    /**
     * Inline SVG icons for the applications list action toolbar.
     *
     * @param string $name Icon key: view|approve|reject|mark_reviewed|driver|delete.
     * @return string SVG markup (not escaped - used only with fixed keys).
     */
    protected static function application_action_svg( $name ) {
        $svgs = array(
            'view'          => '<svg class="lddfw-app-action-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            'approve'       => '<svg class="lddfw-app-action-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>',
            'reject'        => '<svg class="lddfw-app-action-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
            'mark_reviewed' => '<svg class="lddfw-app-action-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
            'driver'        => '<svg class="lddfw-app-action-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'delete'        => '<svg class="lddfw-app-action-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>',
        );
        return ( isset( $svgs[$name] ) ? $svgs[$name] : '' );
    }

    /**
     * Icon toolbar for one application row (Applications list).
     *
     * @param object $app Row from lddfw_applications.
     * @return string HTML.
     */
    protected static function render_application_actions_toolbar( $app ) {
        $id = (int) $app->application_id;
        ob_start();
        ?>
		<div class="lddfw-app-actions" role="toolbar" aria-label="<?php 
        echo esc_attr__( 'Application actions', 'lddfw' );
        ?>">
			<button type="button" class="lddfw-app-icon-btn lddfw-app-view-details" data-app-id="<?php 
        echo esc_attr( (string) $id );
        ?>" title="<?php 
        echo esc_attr__( 'View details', 'lddfw' );
        ?>">
				<?php 
        echo self::application_action_svg( 'view' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG map.
        ?>
				<span class="screen-reader-text"><?php 
        echo esc_html__( 'View details', 'lddfw' );
        ?></span>
			</button>
			<?php 
        if ( in_array( $app->status, array('new', 'reviewed'), true ) ) {
            ?>
				<button type="button" class="lddfw-app-icon-btn is-primary lddfw-app-action" data-action="approve" data-app-id="<?php 
            echo esc_attr( (string) $id );
            ?>" title="<?php 
            echo esc_attr__( 'Approve', 'lddfw' );
            ?>">
					<?php 
            echo self::application_action_svg( 'approve' );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
					<span class="screen-reader-text"><?php 
            echo esc_html__( 'Approve', 'lddfw' );
            ?></span>
				</button>
				<button type="button" class="lddfw-app-icon-btn is-muted lddfw-app-action" data-action="reject" data-app-id="<?php 
            echo esc_attr( (string) $id );
            ?>" title="<?php 
            echo esc_attr__( 'Reject', 'lddfw' );
            ?>">
					<?php 
            echo self::application_action_svg( 'reject' );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
					<span class="screen-reader-text"><?php 
            echo esc_html__( 'Reject', 'lddfw' );
            ?></span>
				</button>
				<?php 
            if ( 'new' === $app->status ) {
                ?>
					<button type="button" class="lddfw-app-icon-btn is-muted lddfw-app-action" data-action="mark_reviewed" data-app-id="<?php 
                echo esc_attr( (string) $id );
                ?>" title="<?php 
                echo esc_attr__( 'Mark reviewed', 'lddfw' );
                ?>">
						<?php 
                echo self::application_action_svg( 'mark_reviewed' );
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
						<span class="screen-reader-text"><?php 
                echo esc_html__( 'Mark reviewed', 'lddfw' );
                ?></span>
					</button>
				<?php 
            }
            ?>
			<?php 
        }
        ?>
			<?php 
        if ( 'converted' === $app->status && !empty( $app->user_id ) ) {
            ?>
				<a href="<?php 
            echo esc_url( get_edit_user_link( $app->user_id ) );
            ?>" class="lddfw-app-icon-btn is-muted" title="<?php 
            echo esc_attr__( 'View driver', 'lddfw' );
            ?>">
					<?php 
            echo self::application_action_svg( 'driver' );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
					<span class="screen-reader-text"><?php 
            echo esc_html__( 'View driver', 'lddfw' );
            ?></span>
				</a>
			<?php 
        }
        ?>
			<button type="button" class="lddfw-app-icon-btn is-danger lddfw-app-action" data-action="delete" data-app-id="<?php 
        echo esc_attr( (string) $id );
        ?>" title="<?php 
        echo esc_attr__( 'Delete', 'lddfw' );
        ?>">
				<?php 
        echo self::application_action_svg( 'delete' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
				<span class="screen-reader-text"><?php 
        echo esc_html__( 'Delete', 'lddfw' );
        ?></span>
			</button>
		</div>
		<?php 
        return (string) ob_get_clean();
    }

    /**
     * Full HTML for the application details modal (AJAX).
     *
     * @param int $application_id Application ID.
     * @return string HTML fragment (empty if not found).
     */
    public static function render_application_details_html( $application_id ) {
        $application_id = (int) $application_id;
        if ( $application_id <= 0 ) {
            return '';
        }
        global $wpdb;
        $table = $wpdb->prefix . 'lddfw_applications';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is $wpdb->prefix.'lddfw_applications'; safe identifier, never user input.
        $app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE application_id = %d", $application_id ) );
        // db call ok; no-cache ok.
        if ( empty( $app ) ) {
            return '';
        }
        $labels = array(
            'new'       => __( 'New', 'lddfw' ),
            'reviewed'  => __( 'Reviewed', 'lddfw' ),
            'approved'  => __( 'Approved', 'lddfw' ),
            'rejected'  => __( 'Rejected', 'lddfw' ),
            'converted' => __( 'Converted', 'lddfw' ),
        );
        $status_chip_map = array(
            'new'       => 'lddfw-chip-new',
            'reviewed'  => 'lddfw-chip-reviewed',
            'approved'  => 'lddfw-chip-approved',
            'rejected'  => 'lddfw-chip-rejected',
            'converted' => 'lddfw-chip-converted',
        );
        $chip_class = ( isset( $status_chip_map[$app->status] ) ? $status_chip_map[$app->status] : 'lddfw-chip-new' );
        $status_label = ( isset( $labels[$app->status] ) ? $labels[$app->status] : $app->status );
        $date_disp = '';
        if ( !empty( $app->created_date ) ) {
            $ts = strtotime( $app->created_date );
            if ( $ts ) {
                $date_disp = date_i18n( lddfw_date_format( 'date' ) . ' ' . lddfw_date_format( 'time' ), $ts );
            }
        }
        $avatar = ( class_exists( 'LDDFW_Reports' ) ? LDDFW_Reports::lddfw_render_avatar( (string) $app->full_name, (int) $app->application_id ) : '' );
        $values = array();
        $values_table = $wpdb->prefix . 'lddfw_application_field_values';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $values_table ) ) === $values_table ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $values_table is $wpdb->prefix.'lddfw_application_field_values'; safe identifier, never user input.
            $values = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$values_table} WHERE application_id = %d ORDER BY value_id ASC", $application_id ) );
            // db call ok; no-cache ok.
        }
        ob_start();
        ?>
		<div class="lddfw-application-modal-inner">
			<div class="lddfw-application-modal-head">
				<div class="lddfw-application-modal-identity">
					<?php 
        echo $avatar;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- avatar helper returns escaped HTML.
        ?>
					<div class="lddfw-application-modal-titlewrap">
						<strong class="lddfw-application-modal-name"><?php 
        echo esc_html( (string) $app->full_name );
        ?></strong>
						<span class="lddfw-chip <?php 
        echo esc_attr( $chip_class );
        ?>"><?php 
        echo esc_html( $status_label );
        ?></span>
					</div>
				</div>
				<?php 
        if ( '' !== $date_disp ) {
            ?>
					<p class="lddfw-application-modal-meta"><?php 
            echo esc_html__( 'Submitted', 'lddfw' );
            ?>: <?php 
            echo esc_html( $date_disp );
            ?></p>
				<?php 
        }
        ?>
			</div>
			<div class="lddfw-application-modal-body">
				<dl class="lddfw-application-modal-dl">
					<?php 
        if ( !empty( $app->email ) ) {
            ?>
						<dt><?php 
            echo esc_html__( 'Email', 'lddfw' );
            ?></dt>
						<dd><a href="mailto:<?php 
            echo esc_attr( $app->email );
            ?>"><?php 
            echo esc_html( $app->email );
            ?></a></dd>
					<?php 
        }
        ?>
					<?php 
        if ( !empty( $app->phone ) ) {
            ?>
						<dt><?php 
            echo esc_html__( 'Phone', 'lddfw' );
            ?></dt>
						<dd><a href="tel:<?php 
            echo esc_attr( preg_replace( '/\\s+/', '', (string) $app->phone ) );
            ?>"><?php 
            echo esc_html( $app->phone );
            ?></a></dd>
					<?php 
        }
        ?>
					<dt><?php 
        echo esc_html__( 'Notifications', 'lddfw' );
        ?></dt>
					<dd><?php 
        echo self::render_notifications_cell( $app );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?></dd>
					<?php 
        if ( 'converted' === $app->status && !empty( $app->user_id ) ) {
            ?>
						<dt><?php 
            echo esc_html__( 'Driver account', 'lddfw' );
            ?></dt>
						<dd><a href="<?php 
            echo esc_url( get_edit_user_link( $app->user_id ) );
            ?>"><?php 
            echo esc_html__( 'Edit user', 'lddfw' );
            ?></a></dd>
					<?php 
        }
        ?>
					<?php 
        if ( !empty( $app->admin_note ) ) {
            ?>
						<dt><?php 
            echo esc_html__( 'Admin note', 'lddfw' );
            ?></dt>
						<dd><?php 
            echo esc_html( (string) $app->admin_note );
            ?></dd>
					<?php 
        }
        ?>
				</dl>
				<?php 
        if ( '' !== (string) $app->message ) {
            ?>
					<div class="lddfw-application-modal-section">
						<h4><?php 
            echo esc_html__( 'Message', 'lddfw' );
            ?></h4>
						<div class="lddfw-application-modal-message"><?php 
            echo wp_kses_post( nl2br( esc_html( (string) $app->message ) ) );
            ?></div>
					</div>
				<?php 
        }
        ?>
				<?php 
        if ( !empty( $values ) ) {
            ?>
					<div class="lddfw-app-details lddfw-app-details--modal">
						<strong><?php 
            echo esc_html__( 'Submitted fields', 'lddfw' );
            ?></strong>
						<ul class="lddfw-app-kv-list">
							<?php 
            foreach ( $values as $v ) {
                echo self::render_application_field_value_li( $v );
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
						</ul>
					</div>
				<?php 
        }
        ?>
			</div>
		</div>
		<?php 
        return (string) ob_get_clean();
    }

    /**
     * Single list item for a dynamic field value (admin).
     *
     * @param object $v Row from lddfw_application_field_values.
     * @return string HTML.
     */
    protected static function render_application_field_value_li( $v ) {
        $html = '<li class="lddfw-app-kv-item">';
        $html .= '<strong>' . esc_html( $v->field_label ) . ':</strong> ';
        if ( 'file' === $v->field_type && !empty( $v->file_uuid ) && (int) $v->value_id > 0 ) {
            $vid = (int) $v->value_id;
            $url = self::application_file_download_url( $vid );
            $mime = ( isset( $v->file_mime ) ? (string) $v->file_mime : '' );
            $ext = ( isset( $v->file_extension ) ? strtolower( (string) $v->file_extension ) : '' );
            $is_img = in_array( $mime, array('image/jpeg', 'image/png', 'image/webp'), true ) || in_array( $ext, array(
                'jpg',
                'jpeg',
                'png',
                'webp'
            ), true );
            if ( $is_img ) {
                $prev = self::application_file_preview_url( $vid );
                $html .= '<div class="lddfw-app-file-block">';
                $html .= '<a class="lddfw-app-file-preview-link" href="' . esc_url( $prev ) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Open image in new tab', 'lddfw' ) . '">';
                $html .= '<img class="lddfw-app-file-preview-img" src="' . esc_url( $prev ) . '" alt="' . esc_attr( $v->field_label ) . '" loading="lazy" decoding="async" />';
                $html .= '</a>';
                $html .= '<div class="lddfw-app-file-meta">';
                $html .= '<a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html__( 'Download', 'lddfw' ) . '</a> ';
            } else {
                $html .= '<div class="lddfw-app-file-block"><div class="lddfw-app-file-meta">';
                $html .= '<a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html__( 'Download', 'lddfw' ) . '</a> ';
            }
            if ( !empty( $v->file_original_name ) ) {
                $html .= '<span class="description">' . esc_html( (string) $v->file_original_name );
                if ( !empty( $v->file_size ) ) {
                    $html .= ' (' . esc_html( size_format( (int) $v->file_size ) ) . ')';
                }
                $html .= '</span>';
            }
            $html .= '</div></div>';
        } else {
            $display = ( isset( $v->value_text ) ? (string) $v->value_text : '' );
            $maybe = json_decode( $display, true );
            if ( is_array( $maybe ) ) {
                $display = implode( ', ', $maybe );
            }
            $html .= esc_html( $display );
        }
        $html .= '</li>';
        return $html;
    }

    /**
     * Tiny channel status badge (email/sms/whatsapp) derived from notifications_log.
     *
     * @param object $app Application row.
     * @return string HTML.
     */
    protected static function render_notifications_cell( $app ) {
        if ( empty( $app->notifications_log ) ) {
            return '<span class="description">&mdash;</span>';
        }
        $log = json_decode( $app->notifications_log, true );
        if ( !is_array( $log ) ) {
            return '<span class="description">&mdash;</span>';
        }
        // Show most recent per channel.
        $latest = array();
        foreach ( $log as $entry ) {
            $c = ( isset( $entry['channel'] ) ? $entry['channel'] : '' );
            if ( '' === $c ) {
                continue;
            }
            $latest[$c] = $entry;
        }
        $html = '<div class="lddfw-app-notif-icons">';
        $channels = array(
            'email'    => 'email-alt',
            'sms'      => 'smartphone',
            'whatsapp' => 'format-chat',
        );
        foreach ( $channels as $chan => $icon ) {
            if ( !isset( $latest[$chan] ) ) {
                continue;
            }
            $entry = $latest[$chan];
            $status = ( isset( $entry['status'] ) ? $entry['status'] : '' );
            $cls = 'lddfw-notif-' . sanitize_html_class( $status );
            $title = ucfirst( $chan ) . ': ' . $status;
            if ( !empty( $entry['detail'] ) ) {
                $title .= ' (' . $entry['detail'] . ')';
            }
            $html .= '<span class="lddfw-notif-icon ' . esc_attr( $cls ) . '" title="' . esc_attr( $title ) . '"><span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span></span>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Pagination block (tablenav).
     *
     * @param int    $paged  Current page.
     * @param int    $pages  Total pages.
     * @param int    $total  Total items.
     * @param string $tab    Active tab.
     * @param array  $extras Extra query args to preserve.
     * @return void
     */
    protected static function render_pagination(
        $paged,
        $pages,
        $total,
        $tab,
        $extras = array()
    ) {
        if ( $pages <= 1 ) {
            return;
        }
        $base_url = add_query_arg( array_merge( array(
            'page' => 'lddfw-drivers',
            'tab'  => $tab,
        ), $extras ), admin_url( 'admin.php' ) );
        echo '<div class="tablenav bottom"><div class="tablenav-pages">';
        /* translators: %s: formatted list item count */
        $pagination_item_count_label = _n(
            '%s item',
            '%s items',
            $total,
            'lddfw'
        );
        echo '<span class="displaying-num">' . esc_html( sprintf( $pagination_item_count_label, number_format_i18n( $total ) ) ) . '</span>';
        echo paginate_links( array(
            'base'      => add_query_arg( 'paged', '%#%', $base_url ),
            'format'    => '',
            'current'   => $paged,
            'total'     => $pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'type'      => 'plain',
        ) );
        echo '</div></div>';
    }

    /**
     * Count applications by status.
     *
     * @param string $status Status key.
     * @return int
     */
    protected static function count_applications_by_status( $status ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lddfw_applications';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return 0;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is $wpdb->prefix.'lddfw_applications'; safe identifier, never user input.
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
        // db call ok; no-cache ok.
    }

}
