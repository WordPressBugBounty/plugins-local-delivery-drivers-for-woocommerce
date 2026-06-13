<?php

/**
 * Plugin Reports.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
/**
 * Plugin Reports.
 *
 * All the Reports functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Reports {
    /**
     * Drivers status orders.
     *
     * @param int    $driver_id driver user id.
     * @param string $status status.
     * @param array  $array array.
     * @since 1.1.0
     * @return html
     */
    public function driver_status_orders( $driver_id, $status, $array ) {
        $orders = 0;
        foreach ( $array as $row ) {
            if ( '' === $driver_id ) {
                if ( $row->post_status === $status ) {
                    $orders = $row->orders;
                    break;
                }
            } else {
                if ( $row->post_status === $status && $driver_id === $row->driver_id ) {
                    $orders = $row->orders;
                    break;
                }
            }
        }
        return $orders;
    }

    /**
     * Build an admin orders list URL compatible with both HPOS and legacy CPT mode.
     *
     * @param string $status        Order status slug (e.g. 'wc-delivered').
     * @param string $driver_filter Driver filter value (-1 = all, -2 = unassigned, or driver user ID).
     * @param string $from_date     Optional start date (Y-m-d).
     * @param string $to_date       Optional end date (Y-m-d).
     * @return string Absolute admin URL.
     */
    public static function orders_list_url(
        $status = '',
        $driver_filter = '',
        $from_date = '',
        $to_date = ''
    ) {
        if ( lddfw_is_hpos_enabled() ) {
            $url = admin_url( 'admin.php?page=wc-orders' );
            if ( '' !== $status ) {
                $url = add_query_arg( 'status', $status, $url );
            }
        } else {
            $url = admin_url( 'edit.php?post_type=shop_order' );
            if ( '' !== $status ) {
                $url = add_query_arg( 'post_status', $status, $url );
            }
        }
        if ( '' !== $driver_filter ) {
            $url = add_query_arg( 'lddfw_orders_filter', $driver_filter, $url );
        }
        if ( '' !== $from_date ) {
            $url = add_query_arg( 'lddfw_from_date', $from_date, $url );
        }
        if ( '' !== $to_date ) {
            $url = add_query_arg( 'lddfw_to_date', $to_date, $url );
        }
        return $url;
    }

    /**
     * Drivers orders dashboard report.
     *
     * @since 1.1.0
     */
    public function claim_orders_dashboard_report() {
        $orders = new LDDFW_Orders();
        $report_array = $orders->lddfw_claim_orders_dashboard_report_query();
        $lddfw_driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
        $lddfw_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
        $lddfw_failed_attempt_status = get_option( 'lddfw_failed_attempt_status', '' );
        $lddfw_delivered_status = get_option( 'lddfw_delivered_status', '' );
        $processing_status = 0;
        $out_for_delivery_orders = 0;
        $driver_assigned_orders = 0;
        $failed_attempt_orders = 0;
        $delivered_orders = 0;
        $total = 0;
        echo '<div class="lddfw-page-section">';
        echo '<h2 class="lddfw-section-title"><span class="dashicons dashicons-warning"></span>' . esc_html( __( 'Orders without drivers', 'lddfw' ) ) . '</h2>';
        echo '<div class="lddfw-modern-table-wrap">';
        echo '<table class="lddfw-modern-table">
		<thead>
			<tr>
				<th>' . esc_html( __( 'Ready for claim', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Driver assigned', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Delivered today', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Failed delivery', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Total', 'lddfw' ) ) . '</th>
			</tr>
		</thead>
		<tbody>';
        if ( empty( $report_array ) && empty( $processing_status ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="6">' . esc_html( __( 'No orders', 'lddfw' ) ) . '</td></tr>';
        } else {
            $yesterday_counts = $this->lddfw_delivered_counts_for_date( date_i18n( 'Y-m-d', strtotime( '-1 day' ) ) );
            $delivered_delta = self::lddfw_render_delta( $delivered_orders, (int) $yesterday_counts['total'] );
            $claim_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( get_option( 'lddfw_processing_status' ), '-2' ) ) . '">' . (int) $processing_status . '</a>' );
            $assigned_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_driver_assigned_status, '-2' ) ) . '">' . $driver_assigned_orders . '</a>' );
            $out_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_out_for_delivery_status, '-2' ) ) . '">' . $out_for_delivery_orders . '</a>' );
            $delivered_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                $lddfw_delivered_status,
                '-2',
                date_i18n( 'Y-m-d' ),
                date_i18n( 'Y-m-d' )
            ) ) . '">' . $delivered_orders . '</a>' );
            $failed_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_failed_attempt_status, '-2' ) ) . '">' . $failed_attempt_orders . '</a>' );
            echo '<tr>
					<td>' . self::lddfw_render_badge( $claim_link, 'muted' ) . '</td>
					<td class="lddfw-text-center">' . self::lddfw_render_badge( $assigned_link, 'warning' ) . '</td>
					<td class="lddfw-text-center">' . self::lddfw_render_badge( $out_link, 'info' ) . '</td>
					<td class="lddfw-text-center">' . self::lddfw_render_badge( $delivered_link, 'success' ) . ' ' . $delivered_delta . '</td>
					<td class="lddfw-text-center">' . self::lddfw_render_badge( $failed_link, 'danger' ) . '</td>
					<td class="lddfw-text-center">' . self::lddfw_render_badge( lddfw_admin_premium_feature( (string) $total ), 'neutral' ) . '</td>
				</tr>';
        }
        echo '</tbody></table></div></div>';
    }

    /**
     * Drivers orders dashboard report.
     *
     * @since 1.1.0
     */
    public function drivers_orders_dashboard_report() {
        $orders = new LDDFW_Orders();
        $report_array = $orders->lddfw_drivers_orders_dashboard_report_query();
        $lddfw_driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
        $lddfw_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
        $lddfw_failed_attempt_status = get_option( 'lddfw_failed_attempt_status', '' );
        $lddfw_delivered_status = get_option( 'lddfw_delivered_status', '' );
        $last_driver = '';
        $out_for_delivery_orders_total = 0;
        $driver_assigned_orders_total = 0;
        $failed_attempt_orders_total = 0;
        $delivered_orders_total = 0;
        $total = 0;
        $driver_counter = 0;
        echo '<div class="lddfw-page-section">';
        echo '<h2 class="lddfw-section-title"><span class="dashicons dashicons-performance"></span>' . esc_html( __( 'Driver orders', 'lddfw' ) ) . '</h2>';
        echo '<div class="lddfw-modern-table-wrap">';
        echo '<table class="lddfw-modern-table">
	<thead>
		<tr>
			<th>' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Phone', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Driver assigned', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Delivered today', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Failed delivery', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Total', 'lddfw' ) ) . '</th>
		</tr>
	</thead>
	<tbody>';
        if ( empty( $report_array ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="7">' . esc_html( __( 'No orders', 'lddfw' ) ) . '</td></tr>';
        } else {
            $yesterday_counts = $this->lddfw_delivered_counts_for_date( date_i18n( 'Y-m-d', strtotime( '-1 day' ) ) );
            foreach ( $report_array as $row ) {
                $driver_id = $row->driver_id;
                if ( $last_driver !== $driver_id ) {
                    ++$driver_counter;
                    $out_for_delivery_orders = 0;
                    $driver_assigned_orders = 0;
                    $failed_attempt_orders = 0;
                    $delivered_orders = 0;
                    $sub_total = 0;
                    $phone = get_user_meta( $driver_id, 'billing_phone', true );
                    $last_driver = $driver_id;
                    $avatar = self::lddfw_render_avatar( $row->driver_name, (int) $driver_id );
                    $driver_yesterday = ( isset( $yesterday_counts['by_driver'][(int) $driver_id] ) ? (int) $yesterday_counts['by_driver'][(int) $driver_id] : 0 );
                    $delivered_delta = self::lddfw_render_delta( $delivered_orders, $driver_yesterday );
                    $assigned_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_driver_assigned_status, (string) $driver_id ) ) . '">' . $driver_assigned_orders . '</a>' );
                    $out_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_out_for_delivery_status, (string) $driver_id ) ) . '">' . $out_for_delivery_orders . '</a>' );
                    $delivered_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                        $lddfw_delivered_status,
                        (string) $driver_id,
                        date_i18n( 'Y-m-d' ),
                        date_i18n( 'Y-m-d' )
                    ) ) . '">' . $delivered_orders . '</a>' );
                    $failed_link = lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_failed_attempt_status, (string) $driver_id ) ) . '">' . $failed_attempt_orders . '</a>' );
                    echo '<tr>
				<td>' . $avatar . esc_html( $row->driver_name ) . '</td>
				<td class="lddfw-text-center"><a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></td>
				<td class="lddfw-text-center">' . self::lddfw_render_badge( $assigned_link, 'warning' ) . '</td>
				<td class="lddfw-text-center">' . self::lddfw_render_badge( $out_link, 'info' ) . '</td>
				<td class="lddfw-text-center">' . self::lddfw_render_badge( $delivered_link, 'success' ) . ' ' . $delivered_delta . '</td>
				<td class="lddfw-text-center">' . self::lddfw_render_badge( $failed_link, 'danger' ) . '</td>
				<td class="lddfw-text-center">' . self::lddfw_render_badge( lddfw_admin_premium_feature( (string) $sub_total ), 'neutral' ) . '</td>
			</tr>';
                }
            }
        }
        $driver_label = ( 1 < $driver_counter ? __( 'Drivers', 'lddfw' ) : __( 'Driver', 'lddfw' ) );
        echo '</tbody>
	<tfoot>
		<tr>
			<td>' . esc_html( $driver_counter . ' ' . $driver_label ) . '</td>
			<td class="lddfw-text-center"> </td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_driver_assigned_status, '-1' ) ) . '">' . $driver_assigned_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_out_for_delivery_status, '-1' ) ) . '">' . $out_for_delivery_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
            $lddfw_delivered_status,
            '-1',
            date_i18n( 'Y-m-d' ),
            date_i18n( 'Y-m-d' )
        ) ) . '">' . $delivered_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url( $lddfw_failed_attempt_status, '-1' ) ) . '">' . $failed_attempt_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( (string) $total ) . '</td>
		</tr>
	</tfoot>
	</table></div></div>';
    }

    /**
     * Drivers refund query.
     *
     * @param date $fromdate fromdate.
     * @param date $todate todate.
     * @param int  $driver_id driver user id.
     * @deprecated 1.7.5
     * @since 1.1.2
     * @return html
     */
    public function lddfw_drivers_refund_query( $fromdate, $todate, $driver_id = '' ) {
        global $wpdb;
        $driver_query = '';
        if ( '' !== $driver_id ) {
            $driver_query = $wpdb->prepare( 'pm.meta_value = %s and', array($driver_id) );
        }
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $driver_query is built via $wpdb->prepare() above; safe to concatenate.
        if ( lddfw_is_hpos_enabled() ) {
            // For HPOS-enabled environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT om.meta_value as driver_id,
					COALESCE(SUM(omr.meta_value),0) as refund
					FROM ' . $wpdb->prefix . 'wc_orders as o
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om1 ON o.id = om1.order_id AND om1.meta_key = \'lddfw_delivered_date\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders o2 ON o.id = o2.parent_order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta omr ON o2.id = omr.order_id AND omr.meta_key = \'_refund_amount\'
					WHERE ' . $driver_query . ' o.status = %s AND CAST(om1.meta_value AS DATE) >= %s AND CAST(om1.meta_value AS DATE) <= %s
					GROUP BY om.meta_value
					ORDER BY om.meta_value', array(get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate) ) );
        } else {
            // For non-HPOS environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'select pm.meta_value as driver_id,
				COALESCE(SUM( pm5.meta_value ),0) as refund  
				from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
				inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
				left join ' . $wpdb->prefix . 'posts p2 on p.id=p2.post_parent
				left join ' . $wpdb->prefix . 'postmeta pm5 on p2.id=pm5.post_id and pm5.meta_key = \'_refund_amount\'
				where ' . $driver_query . ' p.post_type=\'shop_order\' and
				( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
				group by pm.meta_value
				order by pm.meta_value ', array(get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate) ) );
            // db call ok; no-cache ok.
        }
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $query;
    }

    /**
     * Drivers commissions query.
     *
     * @param date $fromdate fromdate.
     * @param date $todate todate.
     * @param int  $driver_id driver user id.
     * @since 1.1.2
     * @return html
     */
    public function lddfw_drivers_commission_query( $fromdate, $todate, $driver_id = '' ) {
        global $wpdb;
        $driver_query = '';
        if ( '' !== $driver_id ) {
            $driver_query = $wpdb->prepare( ' AND driver_id = %d ', array($driver_id) );
        }
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $driver_query is built via $wpdb->prepare() above; safe to concatenate.
        if ( lddfw_is_hpos_enabled() ) {
            // Query for HPOS-enabled environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT 
					o.driver_id,
					COALESCE(SUM(o.driver_commission),0) as commission,
						COUNT(o.order_id) as orders,
						COALESCE(SUM(o.order_total - o.order_refund_amount),0) as orders_total,
						COALESCE(SUM(o.order_refund_amount),0) as refunds_total,
						COALESCE(SUM(o.order_shipping_amount),0) as shipping_total
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status = %s
					' . $driver_query . '
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY o.driver_id
					ORDER BY o.driver_id', array(get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate) ) );
        } else {
            // For non-HPOS environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT 
				 driver_id,
				COALESCE(SUM( driver_commission ),0) as commission ,
				count(p.ID) as orders,
				COALESCE(SUM( order_total - order_refund_amount ),0)  as orders_total ,
				COALESCE(SUM( order_refund_amount ),0) as refunds_total ,
				COALESCE(SUM( order_shipping_amount   ),0) as shipping_total
			 	FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
				ON p.ID = o.order_id
				WHERE
				p.post_type = \'shop_order\'
				AND p.post_status = %s
				' . $driver_query . '
				AND CAST(delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY driver_id
				ORDER BY driver_id
			    ', array(get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate) ) );
            // db call ok; no-cache ok.
        }
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $query;
    }

    /**
     * Drivers commissions query.
     *
     * @param date $fromdate fromdate.
     * @param date $todate todate.
     * @param int  $driver_id driver user id.
     * @since 1.1.2
     * @return html
     */
    public function payment_methods_query( $fromdate, $todate, $driver_id = '' ) {
        global $wpdb;
        $driver_query = '';
        if ( '' !== $driver_id ) {
            $driver_query = $wpdb->prepare( ' AND driver_id = %d ', array($driver_id) );
        }
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $driver_query is built via $wpdb->prepare() above; safe to concatenate.
        if ( lddfw_is_hpos_enabled() ) {
            // Adjusted query for HPOS-enabled environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT 
						o.driver_id,
						IFNULL(om2.meta_value, \'\') AS order_payment_method,
						COUNT(*) AS orders,
						COALESCE(SUM(om1.meta_value - IFNULL(om3.meta_value, 0)),0) AS orders_total
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om1 ON wo.id = om1.order_id AND om1.meta_key = \'order_total\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om2 ON wo.id = om2.order_id AND om2.meta_key = \'_payment_method_title\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om3 ON wo.id = om3.order_id AND om3.meta_key = \'order_refund_amount\'
					WHERE wo.status = %s
					' . $driver_query . '
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY o.driver_id, om2.meta_value
					ORDER BY o.driver_id, om2.meta_value', array(get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate) ) );
        } else {
            // Original query for non-HPOS environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT 
				driver_id,
				IFNULL( order_payment_method, \'\') as order_payment_method ,
				COUNT(*) AS orders,
				COALESCE(SUM( order_total - order_refund_amount ),0)  as orders_total
			 	FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
				ON p.ID = o.order_id
				WHERE
				p.post_type = \'shop_order\'
				AND p.post_status = %s
				' . $driver_query . '
				AND CAST(delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY driver_id,order_payment_method
				ORDER BY driver_id, order_payment_method
			    ', array(get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate) ) );
            // db call ok; no-cache ok.
        }
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $query;
    }

    /**
     * Drivers commissions report.
     *
     * @since 1.1.0
     */
    public function drivers_commissions_report() {
        $currency_symbol = lddfw_currency_symbol();
        list( $lddfw_dates_range_from, $lddfw_dates_range_to, $lddfw_dates_range ) = $this->lddfw_get_report_range();
        // Commission query.
        $report_array = $this->lddfw_drivers_commission_query( $lddfw_dates_range_from, $lddfw_dates_range_to );
        // Payment methods.
        $payments_report_array = $this->payment_methods_query( $lddfw_dates_range_from, $lddfw_dates_range_to );
        $gateways = WC()->payment_gateways->payment_gateways();
        $payment_options = array();
        // Create array of payment methods from query.
        foreach ( $payments_report_array as $row ) {
            $payment_method_id = $row->order_payment_method;
            $payment_title = ( empty( $gateways[$payment_method_id]->title ) ? esc_attr( __( 'No payment', 'lddfw' ) ) : $gateways[$payment_method_id]->title );
            if ( !in_array( $payment_method_id, $payment_options ) ) {
                $payment_options[$payment_method_id] = $payment_title;
            }
        }
        // Aggregate totals for KPI cards (top of page, show only when premium can display numbers).
        $kpi_orders = 0;
        $kpi_revenue = 0;
        $kpi_shipping = 0;
        $kpi_commission = 0;
        echo '<h2 class="lddfw-section-title"><span class="dashicons dashicons-chart-pie"></span>' . esc_html( __( 'Driver commissions', 'lddfw' ) ) . '</h2>';
        // KPI cards row.
        $revenue_html = ( function_exists( 'wc_price' ) ? wc_price( $kpi_revenue ) : esc_html( $currency_symbol . $kpi_revenue ) );
        $shipping_html = ( function_exists( 'wc_price' ) ? wc_price( $kpi_shipping ) : esc_html( $currency_symbol . $kpi_shipping ) );
        $commission_html = ( function_exists( 'wc_price' ) ? wc_price( $kpi_commission ) : esc_html( $currency_symbol . $kpi_commission ) );
        echo '<div class="lddfw-stat-cards">';
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( (string) $kpi_orders ),
            __( 'Total Orders', 'lddfw' ),
            'cart',
            'info'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $revenue_html ),
            __( 'Orders Revenue', 'lddfw' ),
            'money-alt',
            'success'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $shipping_html ),
            __( 'Shipping Total', 'lddfw' ),
            'location-alt',
            'warning'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $commission_html ),
            __( 'Total Commission', 'lddfw' ),
            'awards',
            'success'
        );
        echo '</div>';
        echo self::lddfw_render_filter_bar( array(
            'tab'   => 'commissions',
            'from'  => $lddfw_dates_range_from,
            'to'    => $lddfw_dates_range_to,
            'range' => $lddfw_dates_range,
        ) );
        echo '<div class="lddfw-modern-table-wrap">';
        echo '
	<table class="lddfw-modern-table">
	<thead>
		<tr>
			<th>' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Orders', 'lddfw' ) ) . '</th>';
        $coulmn_counter = 0;
        foreach ( $payment_options as $payment_id => $payment_name ) {
            if ( '' !== $payment_name ) {
                echo '<th class="lddfw-text-center">' . esc_html( $payment_name ) . '</th>';
                $coulmn_counter++;
            }
        }
        echo '
			<th class="lddfw-text-center">' . esc_html( __( 'Orders Total', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Refunds', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Shipping Total', 'lddfw' ) ) . '</th>
			<th class="lddfw-text-center">' . esc_html( __( 'Commission', 'lddfw' ) ) . '</th>
		</tr>
	</thead>
	<tbody>';
        $last_driver = '';
        $commission = 0;
        $orders_price = 0;
        $refunds_price = 0;
        $shipping_price = 0;
        $orders_counter = 0;
        $driver_counter = 0;
        $commission_total = 0;
        $orders_counter_total = 0;
        $orders_total = 0;
        $refunds_total = 0;
        $shipping_total = 0;
        $total_payments_array = array();
        if ( empty( $report_array ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="' . esc_attr( $coulmn_counter + 6 ) . '">' . esc_html( __( 'No orders', 'lddfw' ) ) . '</td></tr>';
        } else {
            foreach ( $report_array as $row ) {
                $driver_id = $row->driver_id;
                if ( $last_driver !== $driver_id ) {
                    $driver = get_userdata( $driver_id );
                    $driver_name = ( !empty( $driver ) ? $driver->display_name : '' );
                    ++$driver_counter;
                    $last_driver = $driver_id;
                    $avatar = self::lddfw_render_avatar( $driver_name, (int) $driver_id );
                    echo '
				<tr>
					<td>' . $avatar . esc_html( $driver_name ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                        get_option( 'lddfw_delivered_status' ),
                        (string) $driver_id,
                        $lddfw_dates_range_from,
                        $lddfw_dates_range_to
                    ) ) . '">' . $orders_counter . '</a>' ) . '</td>';
                    foreach ( $payment_options as $payment_id => $payment_name ) {
                        echo '<td class="lddfw-text-center">';
                        if ( !empty( $payments_report_array ) ) {
                            foreach ( $payments_report_array as $payment_row ) {
                                if ( $payment_row->driver_id === $driver_id && $payment_row->order_payment_method === $payment_id ) {
                                    echo lddfw_admin_premium_feature( wc_price( $payment_row->orders_total ) );
                                    if ( array_key_exists( $payment_id, $total_payments_array ) ) {
                                        $total_payments_array[$payment_id] += $payment_row->orders_total;
                                    } else {
                                        $total_payments_array[$payment_id] = $payment_row->orders_total;
                                    }
                                    break;
                                }
                            }
                        }
                        echo '</td>';
                    }
                    echo '
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                        get_option( 'lddfw_delivered_status' ),
                        (string) $driver_id,
                        $lddfw_dates_range_from,
                        $lddfw_dates_range_to
                    ) ) . '">' . wc_price( $orders_price ) . '</a>' ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( $refunds_price > 0 ? '<span class="lddfw-refund-amount">' . wc_price( $refunds_price ) . '</span>' : wc_price( 0 ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                        get_option( 'lddfw_delivered_status' ),
                        (string) $driver_id,
                        $lddfw_dates_range_from,
                        $lddfw_dates_range_to
                    ) ) . '">' . wc_price( $shipping_price ) . '</a>' ) . '</td>
					<td class="lddfw-text-center"><strong>' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                        get_option( 'lddfw_delivered_status' ),
                        (string) $driver_id,
                        $lddfw_dates_range_from,
                        $lddfw_dates_range_to
                    ) ) . '">' . wc_price( $commission ) . '</a>' ) . '</strong></td>
				</tr>';
                }
            }
        }
        echo '</tbody>';
        if ( function_exists( 'wc_price' ) ) {
            echo '<tfoot>
			<tr>
			<td>' . esc_html( $driver_counter . ' ' . __( 'Drivers', 'lddfw' ) ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                get_option( 'lddfw_delivered_status' ),
                '-1',
                $lddfw_dates_range_from,
                $lddfw_dates_range_to
            ) ) . '">' . $orders_counter_total . '</a>' ) . '</td>';
            foreach ( $payment_options as $payment_id => $payment_name ) {
                echo '<td class="lddfw-text-center">';
                if ( !empty( $total_payments_array[$payment_id] ) ) {
                    echo lddfw_admin_premium_feature( wc_price( $total_payments_array[$payment_id] ) );
                }
                echo '</td>';
            }
            echo '
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                get_option( 'lddfw_delivered_status' ),
                '-1',
                $lddfw_dates_range_from,
                $lddfw_dates_range_to
            ) ) . '">' . wc_price( $orders_total ) . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( $refunds_total > 0 ? '<span class="lddfw-refund-amount">' . wc_price( $refunds_total ) . '</span>' : wc_price( 0 ) ) ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                get_option( 'lddfw_delivered_status' ),
                '-1',
                $lddfw_dates_range_from,
                $lddfw_dates_range_to
            ) ) . '">' . wc_price( $shipping_total ) . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="' . esc_url( self::orders_list_url(
                get_option( 'lddfw_delivered_status' ),
                '-1',
                $lddfw_dates_range_from,
                $lddfw_dates_range_to
            ) ) . '">' . wc_price( $commission_total ) . '</a>' ) . '</td>
			</tr>
		</tfoot>';
        }
        echo '</table></div>';
    }

    /**
     * Admin dashboard screen.
     *
     * @since 1.1.0
     */
    public function screen_dashboard() {
        $is_premium = lddfw_fs()->is__premium_only() && lddfw_fs()->can_use_premium_code();
        echo '<div class="wrap">
		<h1 class="wp-heading-inline">' . esc_html( __( 'Dashboard', 'lddfw' ) ) . '</h1>
		  ' . LDDFW_Admin::lddfw_admin_plugin_bar() . '
		  <hr class="wp-header-end">';
        // 1. Attention area: urgent alerts
        echo $this->stuck_orders_alert();
        // 2. Operational KPI row (live status at a glance).
        echo $this->dashboard_kpi_cards();
        // 3. Today's earnings (premium financial summary).
        echo $this->todays_earnings_summary();
        // 4. Drivers & Applications summary - actionable, now placed up-top.
        echo $this->drivers_summary_card();
        // 5. Per-driver breakdown table (wide, full-width).
        echo $this->drivers_orders_dashboard_report();
        // Note: claim_orders_dashboard_report() ("Orders without drivers")
        // was removed from the dashboard in 2.3.x - its single data row
        // (Unassigned / Driver assigned / Out for delivery / Delivered
        // today / Failed) is already surfaced in dashboard_kpi_cards()
        // and the Drivers orders tfoot. The method is kept for backward
        // compatibility and can still be called directly.
        // 5b. Driver panel + Driver mobile app - matched two-column grid.
        // Both cards use the same internal structure (top action/badges row
        // + QR panel below), so they align as a visually-balanced pair.
        // Collapses to a single column on narrow screens via the shared
        // `.lddfw-dashboard-grid` media rules.
        echo '<div class="lddfw-dashboard-grid lddfw-dashboard-grid--apps">';
        echo '<div class="lddfw-dashboard-grid__col">' . $this->driver_panel_quick_links() . '</div>';
        echo '<div class="lddfw-dashboard-grid__col">' . $this->app_downloads_section() . '</div>';
        echo '</div>';
        // 6. Two-column grid: today's payments on the left, recent reviews
        // on the right (premium). If the reviews feed is unavailable (non-
        // premium), payments uses the available column and the grid
        // collapses naturally. The full driver_panel_qr_widget() card is
        // no longer part of the dashboard - its data now lives in the
        // compact driver_panel_quick_links() row above. The method is
        // kept for backward compatibility.
        echo '<div class="lddfw-dashboard-grid">';
        echo '<div class="lddfw-dashboard-grid__col">' . $this->todays_payments_breakdown() . '</div>';
        if ( $is_premium ) {
            echo '<div class="lddfw-dashboard-grid__col">' . $this->recent_reviews_feed__premium_only() . '</div>';
        }
        echo '</div>';
        // 8. Marketing banners moved to the bottom so real data comes first.
        // The SMS banners self-suppress based on their own conditions. The
        // App CTA banner was superseded by app_downloads_section() above -
        // its method is kept on LDDFW_Admin for backward compatibility.
        echo LDDFW_Admin::lddfw_sms_cta_banner();
        echo LDDFW_Admin::lddfw_powerfulwp_cta_banner();
        echo '</div>';
    }

    /**
     * Compact "Drivers & Applications" summary card for the Dashboard.
     *
     * Replaces the full drivers table that used to live on the Dashboard –
     * the full list now lives on the dedicated Drivers & Applications page.
     * This card shows the key headline numbers (total / available / can-claim
     * drivers, pending applications) and links straight to each tab.
     *
     * @since 2.3.0
     * @return string HTML.
     */
    public function drivers_summary_card() {
        $drivers = LDDFW_Driver::lddfw_get_drivers();
        $total_drivers = 0;
        $available_drivers = 0;
        $claim_drivers = 0;
        foreach ( $drivers as $driver ) {
            $driver_id = (int) $driver->ID;
            $account = get_user_meta( $driver_id, 'lddfw_driver_account', true );
            if ( '' === $account ) {
                update_user_meta( $driver_id, 'lddfw_driver_account', '1' );
                $account = '1';
            }
            if ( '1' !== (string) $account ) {
                continue;
            }
            $total_drivers++;
            if ( '1' === (string) get_user_meta( $driver_id, 'lddfw_driver_availability', true ) ) {
                $available_drivers++;
            }
            if ( '1' === (string) get_user_meta( $driver_id, 'lddfw_driver_claim', true ) ) {
                $claim_drivers++;
            }
        }
        $pending_apps = 0;
        $apps_available = false;
        $drivers_url = admin_url( 'admin.php?page=lddfw-drivers&tab=drivers' );
        $apps_url = admin_url( 'admin.php?page=lddfw-drivers&tab=applications' );
        $html = '<div class="lddfw-page-section">';
        $html .= '<div class="lddfw-section-title-bar">';
        $html .= '<h2 class="lddfw-section-title lddfw-section-title--inline"><span class="dashicons dashicons-admin-users"></span>' . esc_html__( 'Drivers & Applications', 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-section-actions">';
        $html .= '<a href="' . esc_url( $drivers_url ) . '" class="page-title-action">' . esc_html__( 'Manage drivers', 'lddfw' ) . '</a>';
        if ( $apps_available ) {
            $html .= '<a href="' . esc_url( $apps_url ) . '" class="page-title-action">' . esc_html__( 'View applications', 'lddfw' ) . '</a>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="lddfw-stat-cards lddfw-stat-cards--drivers">';
        $html .= self::lddfw_render_kpi_card(
            '<a href="' . esc_url( $drivers_url ) . '">' . esc_html( number_format_i18n( $total_drivers ) ) . '</a>',
            __( 'Active drivers', 'lddfw' ),
            'admin-users',
            'info'
        );
        $html .= self::lddfw_render_kpi_card(
            esc_html( number_format_i18n( $available_drivers ) ),
            __( 'Available now', 'lddfw' ),
            'yes-alt',
            ( $available_drivers > 0 ? 'success' : 'muted' )
        );
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Return the driver-badge map keyed by driver_id.
     *
     * Computes up to one badge per driver from four criteria. Priority:
     *   Top Rated > Most Deliveries > Perfect Week > Cash King.
     * Cached 5 min.
     *
     * @since 2.3.0
     * @return array<int,array{icon:string,label:string,title:string,variant:string}>
     */
    public function get_driver_badges() {
        $cache_key = 'lddfw_driver_badges';
        $cached = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }
        $badges = array();
        $today = date_i18n( 'Y-m-d' );
        $week = date_i18n( 'Y-m-d', strtotime( '-7 days' ) );
        global $wpdb;
        $delivered_status = get_option( 'lddfw_delivered_status', '' );
        $failed_status = get_option( 'lddfw_failed_attempt_status', '' );
        // 2. Most Deliveries today.
        if ( '' !== $delivered_status ) {
            if ( lddfw_is_hpos_enabled() ) {
                $row = $wpdb->get_row( $wpdb->prepare(
                    'SELECT ldo.driver_id, COUNT(*) AS cnt
						FROM ' . $wpdb->prefix . 'wc_orders o
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
						WHERE o.type = %s AND o.status = %s
						AND ldo.driver_id > 0
						AND CAST(ldo.delivered_date AS DATE) = %s
						GROUP BY ldo.driver_id
						ORDER BY cnt DESC
						LIMIT 1',
                    'shop_order',
                    $delivered_status,
                    $today
                ) );
            } else {
                $row = $wpdb->get_row( $wpdb->prepare(
                    'SELECT ldo.driver_id, COUNT(*) AS cnt
						FROM ' . $wpdb->prefix . 'posts p
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON p.ID = ldo.order_id
						WHERE p.post_type = %s AND p.post_status = %s
						AND ldo.driver_id > 0
						AND CAST(ldo.delivered_date AS DATE) = %s
						GROUP BY ldo.driver_id
						ORDER BY cnt DESC
						LIMIT 1',
                    'shop_order',
                    $delivered_status,
                    $today
                ) );
            }
            if ( !empty( $row ) && (int) $row->cnt > 0 ) {
                $did = (int) $row->driver_id;
                if ( !isset( $badges[$did] ) ) {
                    $badges[$did] = array(
                        'icon'    => 'awards',
                        'label'   => __( 'Most Deliveries', 'lddfw' ),
                        'title'   => sprintf( 
                            /* translators: %d: delivery count */
                            __( '%d deliveries today', 'lddfw' ),
                            (int) $row->cnt
                         ),
                        'variant' => 'success',
                    );
                }
            }
        }
        // 3. Perfect Week - last 7 days, zero failed, min 5 delivered.
        if ( '' !== $delivered_status ) {
            if ( lddfw_is_hpos_enabled() ) {
                $rows = $wpdb->get_results( $wpdb->prepare(
                    'SELECT ldo.driver_id,
						SUM(CASE WHEN o.status = %s THEN 1 ELSE 0 END) AS delivered,
						SUM(CASE WHEN o.status = %s THEN 1 ELSE 0 END) AS failed
						FROM ' . $wpdb->prefix . 'wc_orders o
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
						WHERE o.type = %s AND ldo.driver_id > 0
						AND CAST(ldo.delivered_date AS DATE) >= %s
						GROUP BY ldo.driver_id
						HAVING delivered >= 5 AND failed = 0',
                    $delivered_status,
                    $failed_status,
                    'shop_order',
                    $week
                ) );
            } else {
                $rows = $wpdb->get_results( $wpdb->prepare(
                    'SELECT ldo.driver_id,
						SUM(CASE WHEN p.post_status = %s THEN 1 ELSE 0 END) AS delivered,
						SUM(CASE WHEN p.post_status = %s THEN 1 ELSE 0 END) AS failed
						FROM ' . $wpdb->prefix . 'posts p
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON p.ID = ldo.order_id
						WHERE p.post_type = %s AND ldo.driver_id > 0
						AND CAST(ldo.delivered_date AS DATE) >= %s
						GROUP BY ldo.driver_id
						HAVING delivered >= 5 AND failed = 0',
                    $delivered_status,
                    $failed_status,
                    'shop_order',
                    $week
                ) );
            }
            if ( !empty( $rows ) ) {
                foreach ( $rows as $r ) {
                    $did = (int) $r->driver_id;
                    if ( !isset( $badges[$did] ) ) {
                        $badges[$did] = array(
                            'icon'    => 'shield',
                            'label'   => __( 'Perfect Week', 'lddfw' ),
                            'title'   => __( 'No failed deliveries in the last 7 days', 'lddfw' ),
                            'variant' => 'info',
                        );
                    }
                }
            }
        }
        // 4. Cash King - most cash collected today (COD).
        if ( '' !== $delivered_status ) {
            if ( lddfw_is_hpos_enabled() ) {
                $row = $wpdb->get_row( $wpdb->prepare(
                    'SELECT ldo.driver_id, SUM(o.total_amount) AS cash
						FROM ' . $wpdb->prefix . 'wc_orders o
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
						WHERE o.type = %s AND o.status = %s
						AND o.payment_method = %s
						AND ldo.driver_id > 0
						AND CAST(ldo.delivered_date AS DATE) = %s
						GROUP BY ldo.driver_id
						ORDER BY cash DESC
						LIMIT 1',
                    'shop_order',
                    $delivered_status,
                    'cod',
                    $today
                ) );
            } else {
                $row = $wpdb->get_row( $wpdb->prepare(
                    'SELECT ldo.driver_id, SUM(ldo.order_total) AS cash
						FROM ' . $wpdb->prefix . 'posts p
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON p.ID = ldo.order_id
						WHERE p.post_type = %s AND p.post_status = %s
						AND ldo.order_payment_method = %s
						AND ldo.driver_id > 0
						AND CAST(ldo.delivered_date AS DATE) = %s
						GROUP BY ldo.driver_id
						ORDER BY cash DESC
						LIMIT 1',
                    'shop_order',
                    $delivered_status,
                    'cod',
                    $today
                ) );
            }
            if ( !empty( $row ) && (float) $row->cash > 0 ) {
                $did = (int) $row->driver_id;
                if ( !isset( $badges[$did] ) ) {
                    $badges[$did] = array(
                        'icon'    => 'money-alt',
                        'label'   => __( 'Cash King', 'lddfw' ),
                        'title'   => __( 'Most cash collected today', 'lddfw' ),
                        'variant' => 'neutral',
                    );
                }
            }
        }
        set_transient( $cache_key, $badges, 5 * MINUTE_IN_SECONDS );
        return $badges;
    }

    /**
     * Render a single driver badge chip.
     *
     * @param array $badge Badge info (icon, label, title, variant).
     * @since 2.3.0
     * @return string
     */
    public static function lddfw_render_driver_badge( $badge ) {
        if ( empty( $badge ) || empty( $badge['label'] ) ) {
            return '';
        }
        $variant = ( isset( $badge['variant'] ) ? $badge['variant'] : 'neutral' );
        $icon = ( isset( $badge['icon'] ) ? $badge['icon'] : 'star-filled' );
        $title = ( isset( $badge['title'] ) ? $badge['title'] : $badge['label'] );
        return '<span class="lddfw-driver-badge lddfw-driver-badge--' . esc_attr( $variant ) . '" title="' . esc_attr( $title ) . '">' . '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>' . esc_html( $badge['label'] ) . '</span>';
    }

    /**
     * Render the Driver Panel QR Code widget.
     *
     * QR image is painted client-side by the bundled qrcode-js library
     * (no external HTTP requests). Includes copy-link + download buttons.
     *
     * @since 2.3.0
     * @return string
     */
    public function driver_panel_qr_widget() {
        $url = lddfw_drivers_page_url( '' );
        if ( empty( $url ) ) {
            return '';
        }
        $html = '<div class="lddfw-page-section">';
        $html .= '<h2 class="lddfw-section-title"><span class="dashicons dashicons-smartphone"></span>' . esc_html__( 'Driver panel QR code', 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-qr-card">';
        $html .= '<div class="lddfw-qr-card__image" data-lddfw-qr="' . esc_attr( $url ) . '" data-size="180"></div>';
        $html .= '<div class="lddfw-qr-card__info">';
        $html .= '<p class="lddfw-qr-card__label">' . esc_html__( 'Scan to open the driver panel', 'lddfw' ) . '</p>';
        $html .= '<input type="text" readonly class="lddfw-qr-card__url" value="' . esc_attr( $url ) . '" />';
        $html .= '<div class="lddfw-qr-card__actions">';
        $html .= '<button type="button" class="button lddfw-qr-copy">' . esc_html__( 'Copy link', 'lddfw' ) . '</button> ';
        $html .= '<button type="button" class="button lddfw-qr-download">' . esc_html__( 'Download QR', 'lddfw' ) . '</button>';
        $html .= '</div>';
        $html .= '<p class="description">' . esc_html__( 'Drivers can scan the QR with any phone camera to open the mobile-friendly driver panel.', 'lddfw' ) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Compact "Driver panel" quick-links row for the Dashboard.
     *
     * A lightweight replacement for the full-height driver_panel_qr_widget()
     * card. Layout (per user feedback 2.3.x): buttons sit together on the
     * top row, and the QR code is always visible on the bottom-left with a
     * short description next to it - no toggle, no hidden panel.
     *
     * JS backward-compat: reuses the existing `.lddfw-qr-card`,
     * `.lddfw-qr-card__url`, `.lddfw-qr-card__image`, `.lddfw-qr-copy`,
     * `.lddfw-qr-download` selectors so the pre-existing handlers in
     * admin/js/lddfw-admin.js continue to work unchanged.
     *
     * @since 2.3.0
     * @return string HTML.
     */
    public function driver_panel_quick_links() {
        $url = lddfw_drivers_page_url( '' );
        if ( empty( $url ) ) {
            return '';
        }
        // Inline stroke-based SVG icons (16x16, currentColor). Keeps the
        // dashboard free of additional CDN assets and renders crisply at
        // any zoom level.
        $icon_open = '<svg class="lddfw-quicklink__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>';
        $icon_copy = '<svg class="lddfw-quicklink__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
        $icon_dl = '<svg class="lddfw-quicklink__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>';
        $html = '<div class="lddfw-page-section lddfw-page-section--card">';
        $html .= '<h2 class="lddfw-section-title"><span class="dashicons dashicons-smartphone"></span>' . esc_html__( 'Driver panel', 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-qr-card lddfw-quicklinks-card">';
        $html .= '<div class="lddfw-quicklinks lddfw-quicklinks--actions">';
        $html .= '<a class="lddfw-quicklink lddfw-quicklink--primary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . $icon_open . '<span>' . esc_html__( 'Open driver panel', 'lddfw' ) . '</span></a>';
        $html .= '<button type="button" class="lddfw-quicklink lddfw-qr-copy" data-copied-text="' . esc_attr__( 'Copied!', 'lddfw' ) . '">' . $icon_copy . '<span>' . esc_html__( 'Copy link', 'lddfw' ) . '</span></button>';
        $html .= '<button type="button" class="lddfw-quicklink lddfw-qr-download">' . $icon_dl . '<span>' . esc_html__( 'Download QR', 'lddfw' ) . '</span></button>';
        // Hidden readonly input required by the existing `.lddfw-qr-copy` JS
        // handler (reads its .val()). Not keyboard-reachable.
        $html .= '<input type="text" readonly class="lddfw-qr-card__url" value="' . esc_attr( $url ) . '" aria-hidden="true" tabindex="-1" />';
        $html .= '</div>';
        $html .= '<div class="lddfw-qr-panel lddfw-qr-panel--single">';
        $html .= '<div class="lddfw-qr-panel__codes">';
        $html .= '<div class="lddfw-qr-panel__code">';
        $html .= '<div class="lddfw-qr-card__image" data-lddfw-qr="' . esc_attr( $url ) . '" data-size="140"></div>';
        $html .= '<span class="lddfw-qr-panel__caption">' . esc_html__( 'Web panel', 'lddfw' ) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<p class="description">' . esc_html__( 'Scan with any phone camera to open the mobile-friendly driver panel in the browser.', 'lddfw' ) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render the "Driver mobile app" download section for the Dashboard.
     *
     * Always visible - whether or not the "App for Delivery Drivers" add-on
     * is active - because the install QR codes and store badges are useful
     * to share with drivers in both cases (upsell before install, onboarding
     * after).
     *
     * Layout mirrors driver_panel_quick_links() exactly so the two sections
     * align as a matched pair in the Dashboard's two-column grid: top row
     * of action buttons (here: official Google Play + App Store badges) and
     * a bottom cluster of QR codes (Android + iOS) with a short description.
     * Store URLs are filterable so they can be pointed at regional store
     * listings or an enterprise MDM link without touching plugin code:
     *   - `lddfw_app_google_play_url`
     *   - `lddfw_app_apple_store_url`
     *
     * @since 2.3.0
     * @return string HTML.
     */
    public function app_downloads_section() {
        /**
         * Filters the Google Play store URL used for the Dashboard app row.
         *
         * @since 2.3.0
         * @param string $url Default URL.
         */
        $google_url = apply_filters( 'lddfw_app_google_play_url', 'https://play.google.com/store/apps/details?id=com.delivery.drivers' );
        /**
         * Filters the Apple App Store URL used for the Dashboard app row.
         *
         * @since 2.3.0
         * @param string $url Default URL.
         */
        $apple_url = apply_filters( 'lddfw_app_apple_store_url', 'https://apps.apple.com/us/app/delivery-drivers-app/id6739819854' );
        $plugin_url = plugins_url() . '/' . LDDFW_FOLDER;
        $google_badge = $plugin_url . '/public/images/google-play-badge.png';
        $apple_badge = $plugin_url . '/public/images/app-store-badge.png';
        $html = '<div class="lddfw-page-section lddfw-page-section--card">';
        $html .= '<h2 class="lddfw-section-title"><span class="dashicons dashicons-smartphone"></span>' . esc_html__( 'Driver mobile app', 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-qr-card lddfw-quicklinks-card lddfw-app-downloads">';
        // Top row - official store badges (images straight from Google/Apple
        // brand guidelines, bundled with the plugin so they render even when
        // the App for Delivery Drivers add-on is not installed).
        $html .= '<div class="lddfw-quicklinks lddfw-app-badges">';
        $html .= '<a class="lddfw-app-badge" href="' . esc_url( $google_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Get it on Google Play', 'lddfw' ) . '">';
        $html .= '<img src="' . esc_url( $google_badge ) . '" alt="' . esc_attr__( 'Get it on Google Play', 'lddfw' ) . '" />';
        $html .= '</a>';
        $html .= '<a class="lddfw-app-badge" href="' . esc_url( $apple_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Download on the App Store', 'lddfw' ) . '">';
        $html .= '<img src="' . esc_url( $apple_badge ) . '" alt="' . esc_attr__( 'Download on the App Store', 'lddfw' ) . '" />';
        $html .= '</a>';
        $html .= '</div>';
        $html .= '<div class="lddfw-qr-panel lddfw-qr-panel--multi">';
        $html .= '<div class="lddfw-qr-panel__codes">';
        $html .= '<div class="lddfw-qr-panel__code">';
        $html .= '<div class="lddfw-qr-card__image" data-lddfw-qr="' . esc_attr( $google_url ) . '" data-size="140"></div>';
        $html .= '<span class="lddfw-qr-panel__caption">' . esc_html__( 'Android', 'lddfw' ) . '</span>';
        $html .= '</div>';
        $html .= '<div class="lddfw-qr-panel__code">';
        $html .= '<div class="lddfw-qr-card__image" data-lddfw-qr="' . esc_attr( $apple_url ) . '" data-size="140"></div>';
        $html .= '<span class="lddfw-qr-panel__caption">' . esc_html__( 'iOS', 'lddfw' ) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<p class="description">' . esc_html__( 'Give drivers a native mobile app with push notifications, background GPS tracking, and a faster on-the-road experience. Point a phone camera at the code to install it.', 'lddfw' ) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * AJAX: preview broadcast recipient list.
     *
     * Returns { count, names[] } for a given channel + filter. Capped at 10 names.
     *
     * @since 2.3.0
     * @return void
     */
    public static function broadcast_preview_ajax() {
        check_ajax_referer( 'lddfw-nonce', 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Unauthorized', 'lddfw' ),
            ) );
        }
        $filter = ( isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'all' );
        $list = self::resolve_broadcast_recipients( $filter );
        $names = array();
        foreach ( array_slice( $list, 0, 10 ) as $u ) {
            $names[] = $u->display_name;
        }
        wp_send_json_success( array(
            'count' => count( $list ),
            'names' => $names,
        ) );
    }

    /**
     * AJAX: send a broadcast to the resolved recipients (premium).
     *
     * Batches to 100 drivers per request to stay under shared-host SMS limits.
     *
     * @since 2.3.0
     * @return void
     */
    public static function broadcast_send_ajax() {
        check_ajax_referer( 'lddfw-nonce', 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Unauthorized', 'lddfw' ),
            ) );
        }
        if ( !lddfw_fs()->is__premium_only() || !lddfw_fs()->can_use_premium_code() ) {
            wp_send_json_error( array(
                'message' => __( 'Premium Feature', 'lddfw' ),
            ) );
        }
        $filter = ( isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : 'all' );
        $channel = ( isset( $_POST['channel'] ) ? sanitize_text_field( wp_unslash( $_POST['channel'] ) ) : 'sms' );
        $message = ( isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '' );
        if ( !in_array( $channel, array('sms', 'whatsapp'), true ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid channel', 'lddfw' ),
            ) );
        }
        if ( '' === trim( $message ) ) {
            wp_send_json_error( array(
                'message' => __( 'Message is empty', 'lddfw' ),
            ) );
        }
        $recipients = self::resolve_broadcast_recipients( $filter );
        $recipients = array_slice( $recipients, 0, 100 );
        $sent = 0;
        $failed = 0;
        $errors = array();
        foreach ( $recipients as $user ) {
            $phone = get_user_meta( $user->ID, 'billing_phone', true );
            if ( empty( $phone ) ) {
                $failed++;
                continue;
            }
            $text = lddfw_replace_broadcast_tags( $message, (int) $user->ID );
            $ok = false;
            try {
                if ( 'sms' === $channel && class_exists( 'LDDFW_SMS' ) ) {
                    $sms = new LDDFW_SMS();
                    if ( method_exists( $sms, 'lddfw_send_sms' ) ) {
                        $sms->lddfw_send_sms( $phone, $text );
                        $ok = true;
                    }
                } elseif ( 'whatsapp' === $channel && class_exists( 'LDDFW_Whatsapp' ) ) {
                    $wa = new LDDFW_Whatsapp();
                    if ( method_exists( $wa, 'lddfw_send_whatsapp' ) ) {
                        $wa->lddfw_send_whatsapp( $phone, $text );
                        $ok = true;
                    }
                }
            } catch ( Exception $e ) {
                $errors[] = $user->display_name . ': ' . $e->getMessage();
            }
            if ( $ok ) {
                $sent++;
            } else {
                $failed++;
            }
        }
        //error_log( sprintf( '[LDDFW] Broadcast %s to %d drivers: sent=%d failed=%d', $channel, count( $recipients ), $sent, $failed ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        wp_send_json_success( array(
            'sent'   => $sent,
            'failed' => $failed,
            'errors' => array_slice( $errors, 0, 10 ),
        ) );
    }

    /**
     * Resolve the list of broadcast recipient WP_Users based on a filter.
     *
     * @param string $filter 'all' or 'available'.
     * @return WP_User[]
     */
    protected static function resolve_broadcast_recipients( $filter ) {
        $drivers = LDDFW_Driver::lddfw_get_drivers();
        if ( empty( $drivers ) ) {
            return array();
        }
        $out = array();
        foreach ( $drivers as $driver ) {
            $account = get_user_meta( $driver->ID, 'lddfw_driver_account', true );
            if ( '1' !== $account && '' !== $account ) {
                continue;
            }
            if ( 'available' === $filter ) {
                $availability = get_user_meta( $driver->ID, 'lddfw_driver_availability', true );
                if ( '1' !== $availability ) {
                    continue;
                }
            }
            $out[] = $driver;
        }
        return $out;
    }

    /**
     * AJAX: dismiss the Getting Started checklist for the current user.
     *
     * @since 2.3.0
     * @return void
     */
    public static function dismiss_checklist_ajax() {
        check_ajax_referer( 'lddfw-nonce', 'nonce' );
        if ( !current_user_can( 'read' ) || !get_current_user_id() ) {
            wp_send_json_error( array(
                'message' => __( 'Unauthorized', 'lddfw' ),
            ) );
        }
        update_user_meta( get_current_user_id(), 'lddfw_gsc_dismissed', 1 );
        wp_send_json_success();
    }

    /**
     * Render the dashboard KPI stat cards row.
     *
     * Aggregates totals from the same queries used by the tables below so the
     * cards always match what's displayed. Premium-gated numbers fall back to
     * zero (or dash) on the free build.
     *
     * @since 2.3.0
     * @return string
     */
    public function dashboard_kpi_cards() {
        $orders_obj = new LDDFW_Orders();
        $driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
        $out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
        $failed_attempt_status = get_option( 'lddfw_failed_attempt_status', '' );
        $delivered_status = get_option( 'lddfw_delivered_status', '' );
        $delivered_today = 0;
        $out_for_delivery = 0;
        $driver_assigned = 0;
        $failed_today = 0;
        $unassigned = 0;
        $orphan_counts = array();
        $processing_status = get_option( 'lddfw_processing_status', '' );
        $unassigned_value = (string) $unassigned;
        if ( $unassigned > 0 && '' !== $processing_status ) {
            $unassigned_value = '<a href="' . esc_url( self::orders_list_url( $processing_status, '-2' ) ) . '">' . (int) $unassigned . '</a>';
        }
        $delivered_link = (string) $delivered_today;
        if ( $delivered_today > 0 && '' !== $delivered_status ) {
            $delivered_link = '<a href="' . esc_url( self::orders_list_url(
                $delivered_status,
                '-1',
                date_i18n( 'Y-m-d' ),
                date_i18n( 'Y-m-d' )
            ) ) . '">' . (int) $delivered_today . '</a>';
        }
        $out_link = (string) $out_for_delivery;
        if ( $out_for_delivery > 0 && '' !== $out_for_delivery_status ) {
            $out_link = '<a href="' . esc_url( self::orders_list_url( $out_for_delivery_status, '-1' ) ) . '">' . (int) $out_for_delivery . '</a>';
        }
        $assigned_link = (string) $driver_assigned;
        if ( $driver_assigned > 0 && '' !== $driver_assigned_status ) {
            $assigned_link = '<a href="' . esc_url( self::orders_list_url( $driver_assigned_status, '-1' ) ) . '">' . (int) $driver_assigned . '</a>';
        }
        $failed_link = (string) $failed_today;
        if ( $failed_today > 0 && '' !== $failed_attempt_status ) {
            $failed_link = '<a href="' . esc_url( self::orders_list_url( $failed_attempt_status, '-1' ) ) . '">' . (int) $failed_today . '</a>';
        }
        $html = '<h2 class="lddfw-section-title"><span class="dashicons dashicons-chart-bar"></span>' . esc_html__( 'Live order pipeline', 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-stat-cards">';
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $unassigned_value ),
            __( 'Unassigned', 'lddfw' ),
            'warning',
            ( $unassigned > 0 ? 'muted' : 'success' )
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $assigned_link ),
            __( 'Driver Assigned', 'lddfw' ),
            'businessman',
            'warning'
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $out_link ),
            __( 'Out for Delivery', 'lddfw' ),
            'location',
            'info'
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $delivered_link ),
            __( 'Delivered Today', 'lddfw' ),
            'yes-alt',
            'success'
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $failed_link ),
            __( 'Failed Today', 'lddfw' ),
            'dismiss',
            'danger'
        );
        $html .= '</div>';
        $html .= $this->dashboard_orphan_orders_notice( $orphan_counts );
        return $html;
    }

    /**
     * Warning notice for delivery-status orders that have no driver assigned.
     *
     * @param array $orphan_counts Status => count from lddfw_delivery_status_orphan_counts().
     * @since 2.3.2
     * @return string HTML, empty when there are no orphans.
     */
    protected function dashboard_orphan_orders_notice( $orphan_counts ) {
        if ( empty( $orphan_counts ) || !is_array( $orphan_counts ) ) {
            return '';
        }
        $status_labels = array(
            get_option( 'lddfw_driver_assigned_status', '' )  => __( 'Driver Assigned', 'lddfw' ),
            get_option( 'lddfw_out_for_delivery_status', '' ) => __( 'Out for Delivery', 'lddfw' ),
            get_option( 'lddfw_failed_attempt_status', '' )   => __( 'Failed delivery', 'lddfw' ),
        );
        $parts = array();
        $total = 0;
        foreach ( $orphan_counts as $status => $count ) {
            $count = (int) $count;
            if ( $count <= 0 || '' === $status ) {
                continue;
            }
            $total += $count;
            $label = ( isset( $status_labels[$status] ) ? $status_labels[$status] : $status );
            $parts[] = '<a href="' . esc_url( self::orders_list_url( $status, '-2' ) ) . '">' . esc_html( $label ) . ' (' . $count . ')</a>';
        }
        if ( $total <= 0 || empty( $parts ) ) {
            return '';
        }
        return '<div class="lddfw-stuck-alert lddfw-stuck-alert--warn lddfw-orphan-orders-notice">' . '<div class="lddfw-stuck-alert-icon"><span class="dashicons dashicons-warning"></span></div>' . '<div class="lddfw-stuck-alert-body">' . '<span class="lddfw-stuck-alert-title">' . esc_html( sprintf( 
            /* translators: %d: number of orders */
            _n(
                '%d order in a delivery status has no driver assigned',
                '%d orders in delivery statuses have no driver assigned',
                $total,
                'lddfw'
            ),
            $total
         ) ) . '</span>' . '<span class="lddfw-stuck-alert-meta">' . implode( '<span class="lddfw-stuck-sep">|</span>', $parts ) . '</span>' . '</div>' . '</div>';
    }

    /**
     * Render a today-vs-yesterday trend delta indicator.
     *
     * Used next to KPI numbers to give trend context. `$reverse=true` flips
     * the color semantics for metrics where "less is better" (e.g. failures).
     *
     * @param int|float $today     Today's value.
     * @param int|float $yesterday Yesterday's value.
     * @param bool      $reverse   Whether higher is worse (default false).
     * @since 2.3.0
     * @return string HTML.
     */
    public static function lddfw_render_delta( $today, $yesterday, $reverse = false ) {
        $today = (float) $today;
        $yesterday = (float) $yesterday;
        if ( $today === $yesterday ) {
            return '<span class="lddfw-delta lddfw-delta--same" title="' . esc_attr__( 'No change vs. yesterday', 'lddfw' ) . '">-</span>';
        }
        if ( 0.0 === $yesterday ) {
            return '<span class="lddfw-delta lddfw-delta--new" title="' . esc_attr__( 'No data yesterday', 'lddfw' ) . '">' . esc_html__( 'New', 'lddfw' ) . '</span>';
        }
        $pct = ($today - $yesterday) / $yesterday * 100;
        $is_up = $today > $yesterday;
        $good = ( $reverse ? !$is_up : $is_up );
        $class = ( $good ? 'lddfw-delta--up' : 'lddfw-delta--down' );
        $arrow = ( $is_up ? '&uarr;' : '&darr;' );
        $sign = ( $is_up ? '+' : '' );
        $label = $sign . number_format_i18n( abs( $pct ), 0 ) . '%';
        $title = sprintf( 
            /* translators: 1: today value, 2: yesterday value */
            esc_attr__( 'Today: %1$s, Yesterday: %2$s', 'lddfw' ),
            (string) $today,
            (string) $yesterday
         );
        return '<span class="lddfw-delta ' . esc_attr( $class ) . '" title="' . $title . '">' . $arrow . ' ' . esc_html( $label ) . '</span>';
    }

    /**
     * Count delivered orders for a given date.
     *
     * Returns both a grand total and a per-driver breakdown so both the
     * "Orders without drivers" table and the "Drivers orders" table can
     * compute yesterday-vs-today deltas in one query per date.
     *
     * Supports HPOS and legacy. Cached 5 min per date under a transient key.
     *
     * @param string $date Y-m-d date.
     * @since 2.3.0
     * @return array { 'total' => int, 'by_driver' => array<int,int> }
     */
    public function lddfw_delivered_counts_for_date( $date ) {
        $date = sanitize_text_field( (string) $date );
        if ( '' === $date ) {
            return array(
                'total'     => 0,
                'by_driver' => array(),
            );
        }
        $cache_key = 'lddfw_delivered_counts_' . md5( $date );
        $cached = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }
        global $wpdb;
        $delivered_status = get_option( 'lddfw_delivered_status', '' );
        $out = array(
            'total'     => 0,
            'by_driver' => array(),
        );
        if ( '' === $delivered_status ) {
            set_transient( $cache_key, $out, 5 * MINUTE_IN_SECONDS );
            return $out;
        }
        if ( lddfw_is_hpos_enabled() ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT COALESCE(ldo.driver_id, 0) AS driver_id, COUNT(*) AS orders
					FROM ' . $wpdb->prefix . 'wc_orders o
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
					WHERE o.type = %s AND o.status = %s
					AND CAST(ldo.delivered_date AS DATE) = %s
					GROUP BY ldo.driver_id',
                'shop_order',
                $delivered_status,
                $date
            ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                'SELECT COALESCE(ldo.driver_id, 0) AS driver_id, COUNT(*) AS orders
					FROM ' . $wpdb->prefix . 'posts p
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON p.ID = ldo.order_id
					WHERE p.post_type = %s AND p.post_status = %s
					AND CAST(ldo.delivered_date AS DATE) = %s
					GROUP BY ldo.driver_id',
                'shop_order',
                $delivered_status,
                $date
            ) );
        }
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $count = (int) $row->orders;
                $out['total'] += $count;
                $out['by_driver'][(int) $row->driver_id] = $count;
            }
        }
        set_transient( $cache_key, $out, 5 * MINUTE_IN_SECONDS );
        return $out;
    }

    /**
     * Render the "stuck orders" alert banner.
     *
     * Returns an empty string if no orders are stuck - the caller can echo
     * directly. Counts are cached for 2 minutes to avoid re-running the
     * query on every admin page load.
     *
     * @since 2.3.0
     * @return string
     */
    public function stuck_orders_alert() {
        $stuck_assigned_hours = 4;
        $stuck_out_hours = 6;
        $cache_key = 'lddfw_stuck_orders_alert';
        $cached = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            $counts = $cached;
        } else {
            $counts = array(
                'assigned' => 0,
                'out'      => 0,
            );
            $assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
            $out_status = get_option( 'lddfw_out_for_delivery_status', '' );
            if ( '' !== $assigned_status ) {
                $counts['assigned'] = (int) $this->count_orders_stuck_in_status( $assigned_status, $stuck_assigned_hours );
            }
            if ( '' !== $out_status ) {
                $counts['out'] = (int) $this->count_orders_stuck_in_status( $out_status, $stuck_out_hours );
            }
            set_transient( $cache_key, $counts, 2 * MINUTE_IN_SECONDS );
        }
        if ( empty( $counts['assigned'] ) && empty( $counts['out'] ) ) {
            return '';
        }
        $variant_class = '';
        if ( $counts['assigned'] > 10 || $counts['out'] > 5 ) {
            $variant_class = '';
        } else {
            $variant_class = ' lddfw-stuck-alert--warn';
        }
        $parts = array();
        if ( $counts['assigned'] > 0 ) {
            $url = self::orders_list_url( get_option( 'lddfw_driver_assigned_status', '' ) );
            $parts[] = '<a href="' . esc_url( $url ) . '">' . sprintf( 
                /* translators: 1: number of orders, 2: hours */
                esc_html( _n(
                    '%1$d order assigned over %2$d hours',
                    '%1$d orders assigned over %2$d hours',
                    $counts['assigned'],
                    'lddfw'
                ) ),
                (int) $counts['assigned'],
                (int) $stuck_assigned_hours
             ) . '</a>';
        }
        if ( $counts['out'] > 0 ) {
            $url = self::orders_list_url( get_option( 'lddfw_out_for_delivery_status', '' ) );
            $parts[] = '<a href="' . esc_url( $url ) . '">' . sprintf( 
                /* translators: 1: number of orders, 2: hours */
                esc_html( _n(
                    '%1$d order out for delivery over %2$d hours',
                    '%1$d orders out for delivery over %2$d hours',
                    $counts['out'],
                    'lddfw'
                ) ),
                (int) $counts['out'],
                (int) $stuck_out_hours
             ) . '</a>';
        }
        return '<div class="lddfw-stuck-alert' . $variant_class . '">' . '<div class="lddfw-stuck-alert-icon"><span class="dashicons dashicons-warning"></span></div>' . '<div class="lddfw-stuck-alert-body">' . '<span class="lddfw-stuck-alert-title">' . esc_html__( 'Some orders look stuck', 'lddfw' ) . '</span>' . '<span class="lddfw-stuck-alert-meta">' . implode( '<span class="lddfw-stuck-sep">|</span>', $parts ) . '</span>' . '</div>' . '</div>';
    }

    /**
     * Count orders that have been sitting in a given status for more than N hours.
     *
     * Supports both HPOS and legacy WooCommerce post storage. Uses the most
     * appropriate "last modified" column for each storage engine.
     *
     * @param string $status Order status slug (including `wc-` prefix).
     * @param int    $hours  Threshold in hours.
     * @since 2.3.0
     * @return int
     */
    protected function count_orders_stuck_in_status( $status, $hours ) {
        global $wpdb;
        $hours = max( 1, (int) $hours );
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $sql is built from $wpdb->prefix + literal strings only; no user input.
        if ( lddfw_is_hpos_enabled() ) {
            $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wc_orders
					WHERE type = %s AND status = %s
					AND date_updated_gmt IS NOT NULL
					AND date_updated_gmt < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d HOUR)';
            return (int) $wpdb->get_var( $wpdb->prepare(
                $sql,
                'shop_order',
                $status,
                $hours
            ) );
        }
        $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'posts
				WHERE post_type = %s AND post_status = %s
				AND post_modified_gmt < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d HOUR)';
        return (int) $wpdb->get_var( $wpdb->prepare(
            $sql,
            'shop_order',
            $status,
            $hours
        ) );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Render the Plugin Health Check card.
     *
     * Runs a series of low-cost configuration checks, displays each with a
     * green/yellow/red dashicon, and suggests a "fix" link for items that
     * aren't fully green. Results are cached for 5 minutes.
     *
     * @since 2.3.0
     * @return string
     */
    public function plugin_health_check() {
        $cache_key = 'lddfw_health_check';
        $cached = get_transient( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            $rows = $cached;
        } else {
            $rows = $this->run_health_checks();
            set_transient( $cache_key, $rows, 5 * MINUTE_IN_SECONDS );
        }
        $ok_count = 0;
        $warn_count = 0;
        $fail_count = 0;
        foreach ( $rows as $row ) {
            if ( 'ok' === $row['status'] ) {
                $ok_count++;
            } elseif ( 'warn' === $row['status'] ) {
                $warn_count++;
            } else {
                $fail_count++;
            }
        }
        $summary_class = 'lddfw-health-check-summary-badge--ok';
        $summary_label = esc_html__( 'All good', 'lddfw' );
        if ( $fail_count > 0 ) {
            $summary_class = 'lddfw-health-check-summary-badge--fail';
            $summary_label = sprintf( 
                /* translators: %d: number of failing checks */
                esc_html( _n(
                    '%d issue',
                    '%d issues',
                    $fail_count,
                    'lddfw'
                ) ),
                $fail_count
             );
        } elseif ( $warn_count > 0 ) {
            $summary_class = 'lddfw-health-check-summary-badge--warn';
            $summary_label = sprintf( 
                /* translators: %d: number of warnings */
                esc_html( _n(
                    '%d warning',
                    '%d warnings',
                    $warn_count,
                    'lddfw'
                ) ),
                $warn_count
             );
        }
        $open_attr = ( $fail_count > 0 || $warn_count > 0 ? ' open' : '' );
        $html = '<details class="lddfw-health-check"' . $open_attr . '>';
        $html .= '<summary>';
        $html .= '<span class="dashicons dashicons-shield"></span> ';
        $html .= esc_html__( 'Plugin health check', 'lddfw' );
        $html .= '<span class="lddfw-health-check-summary-badge ' . esc_attr( $summary_class ) . '">' . $summary_label . '</span>';
        $html .= '</summary>';
        $html .= '<div class="lddfw-health-check-body">';
        foreach ( $rows as $row ) {
            $icon_class = 'lddfw-health-check-icon--ok';
            $dashicon = 'yes-alt';
            if ( 'warn' === $row['status'] ) {
                $icon_class = 'lddfw-health-check-icon--warn';
                $dashicon = 'warning';
            } elseif ( 'fail' === $row['status'] ) {
                $icon_class = 'lddfw-health-check-icon--fail';
                $dashicon = 'no';
            }
            $fix_link = '';
            if ( 'ok' !== $row['status'] && !empty( $row['fix_url'] ) && !empty( $row['fix_label'] ) ) {
                $fix_link = '<a class="lddfw-health-check-fix-link" href="' . esc_url( $row['fix_url'] ) . '">' . esc_html( $row['fix_label'] ) . ' &rarr;</a>';
            }
            $html .= '<div class="lddfw-health-check-row">';
            $html .= '<span class="lddfw-health-check-icon ' . esc_attr( $icon_class ) . '"><span class="dashicons dashicons-' . esc_attr( $dashicon ) . '"></span></span>';
            $html .= '<span class="lddfw-health-check-label">' . esc_html( $row['label'] ) . '</span>';
            $html .= $fix_link;
            $html .= '</div>';
        }
        $html .= '</div></details>';
        return $html;
    }

    /**
     * Execute the health checks and return a list of rows.
     *
     * Each row: array( 'label' => string, 'status' => ok|warn|fail, 'fix_url' => string, 'fix_label' => string )
     *
     * @since 2.3.0
     * @return array
     */
    protected function run_health_checks() {
        $rows = array();
        // 1. Google Maps client API key.
        $client_key = get_option( 'lddfw_google_api_key', '' );
        $rows[] = array(
            'label'     => esc_html__( 'Google Maps client API key', 'lddfw' ),
            'status'    => ( '' !== $client_key ? 'ok' : 'warn' ),
            'fix_url'   => admin_url( 'admin.php?page=lddfw-settings' ),
            'fix_label' => esc_html__( 'Add key', 'lddfw' ),
        );
        // 2. Google Maps server API key.
        $server_key = get_option( 'lddfw_google_api_key_server', '' );
        $rows[] = array(
            'label'     => esc_html__( 'Google Maps server API key', 'lddfw' ),
            'status'    => ( '' !== $server_key ? 'ok' : 'warn' ),
            'fix_url'   => admin_url( 'admin.php?page=lddfw-settings' ),
            'fix_label' => esc_html__( 'Add key', 'lddfw' ),
        );
        // 4. Driver panel page exists.
        $drivers_page_id = (int) get_option( 'lddfw_delivery_drivers_page', 0 );
        $drivers_ok = $drivers_page_id > 0 && 'publish' === get_post_status( $drivers_page_id );
        $rows[] = array(
            'label'     => esc_html__( 'Driver panel page is published', 'lddfw' ),
            'status'    => ( $drivers_ok ? 'ok' : 'fail' ),
            'fix_url'   => admin_url( 'admin.php?page=lddfw-settings' ),
            'fix_label' => esc_html__( 'Configure', 'lddfw' ),
        );
        // 5. All four order statuses configured.
        $statuses = array(
            get_option( 'lddfw_driver_assigned_status', '' ),
            get_option( 'lddfw_out_for_delivery_status', '' ),
            get_option( 'lddfw_failed_attempt_status', '' ),
            get_option( 'lddfw_delivered_status', '' )
        );
        $statuses_ok = !in_array( '', $statuses, true );
        $rows[] = array(
            'label'     => esc_html__( 'All four order statuses are configured', 'lddfw' ),
            'status'    => ( $statuses_ok ? 'ok' : 'fail' ),
            'fix_url'   => admin_url( 'admin.php?page=lddfw-settings' ),
            'fix_label' => esc_html__( 'Configure', 'lddfw' ),
        );
        // 6. At least one active driver.
        $active = 0;
        $all = LDDFW_Driver::lddfw_get_drivers();
        if ( !empty( $all ) ) {
            foreach ( $all as $driver ) {
                $account = get_user_meta( $driver->ID, 'lddfw_driver_account', true );
                if ( '1' === $account || '' === $account ) {
                    $active++;
                }
            }
        }
        $rows[] = array(
            'label'     => esc_html__( 'At least one active driver', 'lddfw' ),
            'status'    => ( $active > 0 ? 'ok' : 'warn' ),
            'fix_url'   => admin_url( 'admin.php?page=lddfw-drivers' ),
            'fix_label' => esc_html__( 'Add driver', 'lddfw' ),
        );
        return $rows;
    }

    /**
     * Render the Today's Earnings stat cards.
     *
     * Aggregates today's totals from the commission query and renders them
     * as a row of green stat cards under the monitoring KPI cards.
     *
     * @since 2.3.0
     * @return string
     */
    public function todays_earnings_summary() {
        $today = date_i18n( 'Y-m-d' );
        $orders_count = 0;
        $orders_total = 0.0;
        $shipping_total = 0.0;
        $commission = 0.0;
        $revenue_html = ( function_exists( 'wc_price' ) ? wc_price( $orders_total ) : esc_html( (string) $orders_total ) );
        $shipping_html = ( function_exists( 'wc_price' ) ? wc_price( $shipping_total ) : esc_html( (string) $shipping_total ) );
        $commission_html = ( function_exists( 'wc_price' ) ? wc_price( $commission ) : esc_html( (string) $commission ) );
        $html = '<h2 class="lddfw-section-title"><span class="dashicons dashicons-money-alt"></span>' . esc_html__( "Today's earnings", 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-stat-cards">';
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( (string) $orders_count ),
            __( "Today's Orders", 'lddfw' ),
            'cart',
            'info'
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $revenue_html ),
            __( 'Revenue', 'lddfw' ),
            'money-alt',
            'success'
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $shipping_html ),
            __( 'Shipping', 'lddfw' ),
            'location-alt',
            'warning'
        );
        $html .= self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $commission_html ),
            __( 'Commission', 'lddfw' ),
            'awards',
            'success'
        );
        $html .= '</div>';
        return $html;
    }

    /**
     * Render today's payment method breakdown as a compact table.
     *
     * Groups today's delivered orders by gateway for cash reconciliation.
     *
     * @since 2.3.0
     * @return string
     */
    public function todays_payments_breakdown() {
        $today = date_i18n( 'Y-m-d' );
        $html = '<div class="lddfw-page-section lddfw-payments-table">';
        $html .= '<h2 class="lddfw-section-title"><span class="dashicons dashicons-tickets-alt"></span>' . esc_html__( "Today's payments", 'lddfw' ) . '</h2>';
        $html .= '<div class="lddfw-modern-table-wrap">';
        $html .= '<table class="lddfw-modern-table">';
        $html .= '<thead><tr>';
        $html .= '<th>' . esc_html__( 'Payment method', 'lddfw' ) . '</th>';
        $html .= '<th class="lddfw-text-center">' . esc_html__( 'Orders', 'lddfw' ) . '</th>';
        $html .= '<th class="lddfw-text-center">' . esc_html__( 'Total', 'lddfw' ) . '</th>';
        $html .= '</tr></thead><tbody>';
        $can_show = lddfw_fs()->is__premium_only() && lddfw_fs()->can_use_premium_code();
        $rows = ( $can_show ? $this->payment_methods_query( $today, $today ) : array() );
        if ( empty( $rows ) ) {
            $html .= '<tr class="lddfw-empty-row"><td colspan="3">' . esc_html__( 'No payments today', 'lddfw' ) . '</td></tr>';
        } else {
            $gateways = ( function_exists( 'WC' ) ? WC()->payment_gateways->payment_gateways() : array() );
            $agg = array();
            foreach ( $rows as $row ) {
                $pid = $row->order_payment_method;
                if ( !isset( $agg[$pid] ) ) {
                    $title = ( isset( $gateways[$pid] ) && !empty( $gateways[$pid]->title ) ? $gateways[$pid]->title : __( 'No payment', 'lddfw' ) );
                    $agg[$pid] = array(
                        'title'  => $title,
                        'orders' => 0,
                        'total'  => 0,
                    );
                }
                $agg[$pid]['orders'] += (int) $row->orders;
                $agg[$pid]['total'] += (float) $row->orders_total;
            }
            foreach ( $agg as $bucket ) {
                $total_html = ( function_exists( 'wc_price' ) ? wc_price( $bucket['total'] ) : esc_html( (string) $bucket['total'] ) );
                $html .= '<tr>';
                $html .= '<td>' . esc_html( $bucket['title'] ) . '</td>';
                $html .= '<td class="lddfw-text-center">' . (int) $bucket['orders'] . '</td>';
                $html .= '<td class="lddfw-text-center"><strong>' . lddfw_admin_premium_feature( $total_html ) . '</strong></td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table></div></div>';
        return $html;
    }

    /**
     * Admin reports screen - tab router.
     *
     * Accepts `?tab=` with a whitelisted slug. Defaults to `commissions`
     * so existing bookmarks / URLs keep rendering the same report.
     * (`load-lddfw-dashboard_page_lddfw-reports`, before admin-header) so the file is not prefixed with HTML.
     *
     * @since 1.1.0
     */
    public function screen_reports() {
        $allowed_tabs = array(
            'commissions',
            'ratings',
            'performance',
            'cities',
            'trends',
            'failed'
        );
        $tab = ( isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'commissions' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( !in_array( $tab, $allowed_tabs, true ) ) {
            $tab = 'commissions';
        }
        echo '<div class="wrap">
		<h1 class="wp-heading-inline">' . esc_html( __( 'Reports', 'lddfw' ) ) . '</h1>
		  ' . LDDFW_Admin::lddfw_admin_plugin_bar() . '
		  <hr class="wp-header-end">';
        echo self::lddfw_render_reports_nav( $tab );
        switch ( $tab ) {
            case 'ratings':
                if ( method_exists( $this, 'ratings_reviews_report__premium_only' ) && lddfw_fs()->is__premium_only() ) {
                    $this->ratings_reviews_report__premium_only();
                } else {
                    $this->drivers_commissions_report();
                }
                break;
            case 'performance':
                if ( method_exists( $this, 'driver_performance_report__premium_only' ) && lddfw_fs()->is__premium_only() ) {
                    $this->driver_performance_report__premium_only();
                } else {
                    $this->drivers_commissions_report();
                }
                break;
            case 'cities':
                $this->sales_by_city_report();
                break;
            case 'trends':
                $this->daily_trends_report();
                break;
            case 'failed':
                $this->failed_deliveries_report();
                break;
            case 'commissions':
            default:
                $this->drivers_commissions_report();
                break;
        }
        echo '
		</div>';
    }

    /**
     * Render the Reports page tab navigation.
     *
     * Standard WP `.nav-tab-wrapper` markup, with an extra `.lddfw-reports-nav`
     * class so we can fine-tune spacing in CSS. Preserves the current date range
     * across tab switches so filters stick.
     *
     * @since 2.3.0
     * @param string $active Active tab slug.
     * @return string HTML.
     */
    public static function lddfw_render_reports_nav( $active ) {
        $base = admin_url( 'admin.php?page=lddfw-reports' );
        // Carry over any existing date-range query args so filters persist.
        $carry = array();
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET params for admin report tab navigation.
        foreach ( array('lddfw_dates_range', 'lddfw_dates_range_from', 'lddfw_dates_range_to') as $k ) {
            if ( isset( $_GET[$k] ) ) {
                $carry[$k] = sanitize_text_field( wp_unslash( $_GET[$k] ) );
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $is_premium = lddfw_fs()->is__premium_only();
        $tabs = array(
            'commissions' => array(
                'label'   => __( 'Commissions', 'lddfw' ),
                'icon'    => 'chart-pie',
                'premium' => false,
            ),
            'performance' => array(
                'label'   => __( 'Driver Performance', 'lddfw' ),
                'icon'    => 'awards',
                'premium' => true,
            ),
            'ratings'     => array(
                'label'   => __( 'Ratings & Reviews', 'lddfw' ),
                'icon'    => 'star-filled',
                'premium' => true,
            ),
            'cities'      => array(
                'label'   => __( 'Sales by City', 'lddfw' ),
                'icon'    => 'location-alt',
                'premium' => false,
            ),
            'trends'      => array(
                'label'   => __( 'Daily Trends', 'lddfw' ),
                'icon'    => 'chart-line',
                'premium' => false,
            ),
            'failed'      => array(
                'label'   => __( 'Failed Deliveries', 'lddfw' ),
                'icon'    => 'warning',
                'premium' => false,
            ),
        );
        $html = '<h2 class="nav-tab-wrapper lddfw-reports-nav">';
        foreach ( $tabs as $slug => $tab ) {
            if ( $tab['premium'] && !$is_premium ) {
                continue;
            }
            $url = esc_url( add_query_arg( array_merge( $carry, array(
                'tab' => $slug,
            ) ), $base ) );
            $cls = 'nav-tab' . (( $active === $slug ? ' nav-tab-active' : '' ));
            $icon = '<span class="dashicons dashicons-' . esc_attr( $tab['icon'] ) . '"></span> ';
            $html .= '<a href="' . $url . '" class="' . esc_attr( $cls ) . '">' . $icon . esc_html( $tab['label'] ) . '</a>';
        }
        $html .= '</h2>';
        return $html;
    }

    /**
     * Render the shared date-range filter bar used by every report tab.
     *
     * Extracted from `drivers_commissions_report()` so every tab gets the
     * same date picker + Export CSV button without duplicating markup.
     *
     * @since 2.3.0
     * @param array $args {
     *   @type string $tab            Current tab slug (kept in the form as a hidden field).
     *   @type string $from           Current "from" date (Y-m-d).
     *   @type string $to             Current "to" date (Y-m-d).
     *   @type string $range          Selected range key (today/yesterday/...).
     * }
     * @return string HTML.
     */
    public static function lddfw_render_filter_bar( $args = array() ) {
        $tab = ( isset( $args['tab'] ) ? (string) $args['tab'] : 'commissions' );
        $from = ( isset( $args['from'] ) ? (string) $args['from'] : date_i18n( 'Y-m-d' ) );
        $to = ( isset( $args['to'] ) ? (string) $args['to'] : date_i18n( 'Y-m-d' ) );
        $range = ( isset( $args['range'] ) ? (string) $args['range'] : 'today' );
        $current_week = get_weekstartend( date_i18n( 'Y-m-d' ), '' );
        $current_start_week = gmdate( 'Y-m-d', $current_week['start'] );
        $current_end_week = gmdate( 'Y-m-d', $current_week['end'] );
        $previous_start_week = gmdate( 'Y-m-d', strtotime( $current_start_week . ' -7 day' ) );
        $previous_end_week = gmdate( 'Y-m-d', strtotime( $current_end_week . ' -7 day' ) );
        $html = '<div class="lddfw-filter-bar">';
        $html .= '<form method="GET" action="">';
        $html .= '<div id="lddfw_dates_range_select">' . esc_html__( 'Dates', 'lddfw' );
        $html .= '<select class="custom-select custom-select-lg" name="lddfw_dates_range" id="lddfw_dates_range" data="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">';
        $html .= '<option ' . selected( $range, 'today', false ) . ' fromdate="' . date_i18n( 'Y-m-d' ) . '" todate="' . date_i18n( 'Y-m-d' ) . '" value="today">' . esc_html__( 'Today', 'lddfw' ) . '</option>';
        $html .= '<option ' . selected( $range, 'yesterday', false ) . ' fromdate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" value="yesterday">' . esc_html__( 'Yesterday', 'lddfw' ) . '</option>';
        $html .= '<option ' . selected( $range, 'thisweek', false ) . ' fromdate="' . $current_start_week . '" todate="' . $current_end_week . '" value="thisweek">' . esc_html__( 'This week', 'lddfw' ) . '</option>';
        $html .= '<option ' . selected( $range, 'lastweek', false ) . ' fromdate="' . $previous_start_week . '" todate="' . $previous_end_week . '" value="lastweek">' . esc_html__( 'Last week', 'lddfw' ) . '</option>';
        $html .= '<option ' . selected( $range, 'thismonth', false ) . ' fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '" value="thismonth">' . esc_html__( 'This month', 'lddfw' ) . '</option>';
        $html .= '<option ' . selected( $range, 'lastmonth', false ) . ' fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '" value="lastmonth">' . esc_html__( 'Last month', 'lddfw' ) . '</option>';
        $html .= '<option ' . selected( $range, 'custom', false ) . ' value="custom">' . esc_html__( 'Custom', 'lddfw' ) . '</option>';
        $html .= '</select></div>';
        $html .= '<input type="hidden" name="page" value="lddfw-reports">';
        $html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
        $html .= '<div id="lddfw_dates_custom_range" style="display:none">';
        $html .= esc_html__( 'From', 'lddfw' ) . ' <input type="text" value="' . esc_attr( $from ) . '" class="lddfw-datepicker" name="lddfw_dates_range_from" id="lddfw_dates_range_from"> ';
        $html .= esc_html__( 'To', 'lddfw' ) . ' <input type="text" value="' . esc_attr( $to ) . '" class="lddfw-datepicker" name="lddfw_dates_range_to" id="lddfw_dates_range_to">';
        $html .= '</div>';
        $html .= '<input type="submit" name="submit" id="lddfw_dates_range_submit" class="button button-primary" value="' . esc_attr__( 'Send', 'lddfw' ) . '">';
        $html .= '<div class="lddfw-filter-bar-range-label">' . esc_html__( 'From', 'lddfw' ) . ' <b>' . esc_html( gmdate( lddfw_date_format( 'date' ), strtotime( $from ) ) ) . '</b> ' . esc_html__( 'To', 'lddfw' ) . ' <b>' . esc_html( gmdate( lddfw_date_format( 'date' ), strtotime( $to ) ) ) . '</b></div>';
        $html .= '</form></div>';
        return $html;
    }

    /**
     * Read + sanitize the date-range GET params used by every report tab.
     *
     * Returns `array( $from, $to, $range )` with safe defaults (today).
     *
     * @since 2.3.0
     * @return array
     */
    protected function lddfw_get_report_range() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET params for admin report date range display.
        $range = ( isset( $_GET['lddfw_dates_range'] ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_dates_range'] ) ) : 'today' );
        $from = ( isset( $_GET['lddfw_dates_range_from'] ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_dates_range_from'] ) ) : date_i18n( 'Y-m-d' ) );
        $to = ( isset( $_GET['lddfw_dates_range_to'] ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_dates_range_to'] ) ) : date_i18n( 'Y-m-d' ) );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        // Validate dates; fall back to today if not Y-m-d.
        if ( !preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $from ) ) {
            $from = date_i18n( 'Y-m-d' );
        }
        if ( !preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $to ) ) {
            $to = date_i18n( 'Y-m-d' );
        }
        return array($from, $to, $range);
    }

    /**
     * Drivers dashboard report.
     *
     * @since 1.1.0
     */
    public function drivers_dashboard_report() {
        $drivers = LDDFW_Driver::lddfw_get_drivers();
        $badges = $this->get_driver_badges();
        echo '<div class="lddfw-page-section">';
        echo '<h2 class="lddfw-section-title" style="margin-bottom: 0;"><span class="dashicons dashicons-admin-users"></span>' . esc_html( __( 'Active drivers', 'lddfw' ) ) . '
		<a href="user-new.php" class="page-title-action" style="margin-left:10px;">' . esc_html( __( 'Add new driver', 'lddfw' ) ) . '</a>
		<a href="users.php?role=driver" class="page-title-action" style="margin-left:6px;">' . esc_html( __( 'All drivers', 'lddfw' ) ) . '</a>
		</h2>';
        echo '<div class="lddfw-modern-table-wrap">';
        echo '<table class="lddfw-modern-table">
		<thead>
			<tr>
				<th>' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</th>
				<th>' . esc_html( __( 'Phone', 'lddfw' ) ) . '</th>
				<th>' . esc_html( __( 'Email', 'lddfw' ) ) . '</th>
				<th>' . esc_html( __( 'Address', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Availability', 'lddfw' ) ) . '</th>
				<th class="lddfw-text-center">' . esc_html( __( 'Claim orders', 'lddfw' ) ) . '</th>
			</tr>
		</thead>
		<tbody>';
        $total_driver = 0;
        if ( empty( $drivers ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="6">' . esc_html( __( 'No drivers', 'lddfw' ) ) . '</td></tr>';
        } else {
            foreach ( $drivers as $driver ) {
                $driver_id = $driver->ID;
                $lddfw_driver_account = get_user_meta( $driver_id, 'lddfw_driver_account', true );
                if ( '' === $lddfw_driver_account ) {
                    update_user_meta( $driver_id, 'lddfw_driver_account', '1' );
                    $lddfw_driver_account = get_user_meta( $driver_id, 'lddfw_driver_account', true );
                }
                if ( '1' === $lddfw_driver_account ) {
                    $email = $driver->user_email;
                    $full_name = $driver->display_name;
                    $availability = get_user_meta( $driver_id, 'lddfw_driver_availability', true );
                    $driver_claim = get_user_meta( $driver_id, 'lddfw_driver_claim', true );
                    $phone = get_user_meta( $driver_id, 'billing_phone', true );
                    $billing_address_1 = get_user_meta( $driver_id, 'billing_address_1', true );
                    $billing_address_2 = get_user_meta( $driver_id, 'billing_address_2', true );
                    $billing_city = get_user_meta( $driver_id, 'billing_city', true );
                    $billing_company = get_user_meta( $driver_id, 'billing_company', true );
                    $availability_icon = '';
                    $driver_claim_icon = '';
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
                    $total_driver++;
                    $avatar = self::lddfw_render_avatar( $full_name, (int) $driver_id );
                    $badge_html = ( isset( $badges[(int) $driver_id] ) ? ' ' . self::lddfw_render_driver_badge( $badges[(int) $driver_id] ) : '' );
                    echo '<tr>
				<td>' . $avatar . '<a href="' . esc_url( get_edit_user_link( $driver_id ) ) . '">' . esc_html( $full_name ) . '</a>' . $badge_html . '</td>
				<td><a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></td>
				<td><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td>
				<td>' . esc_html( $billing_address ) . '</td>
				<td class="lddfw-text-center">' . lddfw_admin_premium_feature( $availability_icon ) . '</td>
				<td class="lddfw-text-center">' . lddfw_admin_premium_feature( $driver_claim_icon ) . '</td>
			</tr>';
                }
            }
        }
        $driver_label = ( 1 < $total_driver ? __( 'Drivers', 'lddfw' ) : __( 'Driver', 'lddfw' ) );
        echo '</tbody>
			<tfoot>
				<tr>
					<td>' . esc_html( $total_driver . ' ' . $driver_label ) . '</td>
					<td></td>
					<td></td>
					<td></td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_available_counter"></span> ' . esc_html( __( 'Available', 'lddfw' ) ) . ' |  <span id="lddfw_unavailable_counter"></span> ' . esc_html( __( 'Unavailable', 'lddfw' ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_claim_counter"></span> ' . esc_html( __( 'Can claim', 'lddfw' ) ) . ' | <span id="lddfw_unclaim_counter"></span> ' . esc_html( __( 'Can\'t claim', 'lddfw' ) ) ) . '</td>
				</tr>
			</tfoot>
		</table></div></div>';
    }

    /**
     * Render a single KPI stat card.
     *
     * @param string $value     Displayed number or formatted string.
     * @param string $label     Small label beneath the value.
     * @param string $dashicon  Dashicon slug (without the 'dashicons-' prefix).
     * @param string $variant   Icon color variant: success, warning, danger, info, muted.
     * @since 2.3.0
     * @return string
     */
    public static function lddfw_render_kpi_card(
        $value,
        $label,
        $dashicon = 'chart-bar',
        $variant = 'success'
    ) {
        $allowed_variants = array(
            'success',
            'warning',
            'danger',
            'info',
            'muted'
        );
        if ( !in_array( $variant, $allowed_variants, true ) ) {
            $variant = 'success';
        }
        return '<div class="lddfw-stat-card">' . '<div class="lddfw-stat-icon lddfw-stat-icon--' . esc_attr( $variant ) . '">' . '<span class="dashicons dashicons-' . esc_attr( $dashicon ) . '"></span>' . '</div>' . '<div class="lddfw-stat-body">' . '<span class="lddfw-stat-value">' . $value . '</span>' . '<span class="lddfw-stat-label">' . esc_html( $label ) . '</span>' . '</div>' . '</div>';
    }

    /**
     * Render a status badge (colored pill).
     *
     * @param string $content Inner content (number, anchor tag, etc.).
     * @param string $variant success, warning, danger, info, muted, neutral.
     * @since 2.3.0
     * @return string
     */
    public static function lddfw_render_badge( $content, $variant = 'neutral' ) {
        $allowed = array(
            'success',
            'warning',
            'danger',
            'info',
            'muted',
            'neutral'
        );
        if ( !in_array( $variant, $allowed, true ) ) {
            $variant = 'neutral';
        }
        return '<span class="lddfw-badge lddfw-badge-' . esc_attr( $variant ) . '">' . $content . '</span>';
    }

    /**
     * Render a circular driver avatar with initials or photo.
     *
     * @param string $name          Driver display name.
     * @param int    $user_id       Driver user ID (used for deterministic color and photo lookup).
     * @param string $extra_classes Additional CSS classes (e.g. 'lddfw-avatar-sm').
     * @param string $title         Optional title attribute for the avatar span.
     * @since 2.3.0
     * @return string
     */
    public static function lddfw_render_avatar(
        $name,
        $user_id = 0,
        $extra_classes = '',
        $title = ''
    ) {
        $name = trim( (string) $name );
        $initial = '';
        if ( '' !== $name ) {
            $parts = preg_split( '/\\s+/', $name );
            $first = mb_substr( $parts[0], 0, 1 );
            $second = ( count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '' );
            $initial = mb_strtoupper( $first . $second );
        }
        if ( '' === $initial ) {
            $initial = '?';
        }
        $color_index = ( $user_id > 0 ? abs( (int) $user_id ) % 8 : 0 );
        $title_attr = ( '' !== $title ? ' title="' . esc_attr( $title ) . '"' : '' );
        $photo_url = '';
        if ( $user_id > 0 ) {
            $image_id = get_user_meta( $user_id, 'lddfw_driver_image', true );
            if ( intval( $image_id ) > 0 ) {
                $photo_src = wp_get_attachment_image_src( $image_id, 'thumbnail' );
                if ( is_array( $photo_src ) && !empty( $photo_src[0] ) ) {
                    $photo_url = $photo_src[0];
                }
            }
        }
        $extra = ( '' !== $extra_classes ? ' ' . esc_attr( $extra_classes ) : '' );
        if ( '' !== $photo_url ) {
            return '<span class="lddfw-avatar lddfw-avatar--photo lddfw-avatar-c' . esc_attr( $color_index ) . $extra . '"' . $title_attr . ' aria-hidden="true"><img src="' . esc_url( $photo_url ) . '" alt="' . esc_attr( $name ) . '"></span>';
        }
        return '<span class="lddfw-avatar lddfw-avatar-c' . esc_attr( $color_index ) . $extra . '"' . $title_attr . ' aria-hidden="true">' . esc_html( $initial ) . '</span>';
    }

    /**
     * Wrap a table in a modern styled container.
     *
     * @param string $table_html Raw <table>...</table> html.
     * @since 2.3.0
     * @return string
     */
    public static function lddfw_wrap_table( $table_html ) {
        return '<div class="lddfw-modern-table-wrap">' . $table_html . '</div>';
    }

    /**
     * CSV export dispatcher - routes to per-tab exporter based on the active tab.
     *
     * Keeps the legacy `?lddfw_export=csv` URL working (defaults to commissions)
     * while allowing every new Reports tab to hand back its own CSV.
     *
     * @since 2.3.0
     * @param string $tab Active tab slug.
     * @return void Exits after sending the file.
     */
    public function lddfw_export_csv( $tab = 'commissions' ) {
        if ( !current_user_can( 'edit_pages' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'lddfw' ) );
        }
        check_admin_referer( 'lddfw_export_csv' );
        list( $from, $to ) = $this->lddfw_get_report_range();
        switch ( $tab ) {
            case 'ratings':
                $this->export_ratings_csv( $from, $to );
                break;
            case 'performance':
                $this->export_performance_csv( $from, $to );
                break;
            case 'cities':
                $this->export_cities_csv( $from, $to );
                break;
            case 'trends':
                $this->export_trends_csv( $from, $to );
                break;
            case 'failed':
                $this->export_failed_csv( $from, $to );
                break;
            case 'commissions':
            default:
                $this->export_commissions_csv( $from, $to );
                break;
        }
    }

    /**
     * Backward-compat alias for the old commissions CSV method.
     *
     * Kept so any external caller that referenced the previous method name
     * continues to work. Forwards to the new dispatcher.
     *
     * @since 2.3.0
     * @return void
     */
    public function lddfw_export_commissions_csv() {
        $this->lddfw_export_csv( 'commissions' );
    }

    /**
     * Open a CSV output stream with proper headers and UTF-8 BOM.
     *
     * @since 2.3.0
     * @param string $filename Filename for the attachment.
     * @return resource File handle ready for fputcsv().
     */
    protected function lddfw_csv_open( $filename ) {
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        $output = fopen( 'php://output', 'w' );
        // BOM so Excel/Numbers picks UTF-8 correctly.
        fwrite( $output, chr( 0xef ) . chr( 0xbb ) . chr( 0xbf ) );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        return $output;
    }

    /**
     * CSV export - Commissions (includes new Refunds column).
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return void
     */
    protected function export_commissions_csv( $from, $to ) {
        $rows = $this->lddfw_drivers_commission_query( $from, $to );
        $output = $this->lddfw_csv_open( 'lddfw-commissions-' . $from . '-to-' . $to . '.csv' );
        fputcsv( $output, array(
            __( 'Driver ID', 'lddfw' ),
            __( 'Driver', 'lddfw' ),
            __( 'Orders', 'lddfw' ),
            __( 'Orders Total', 'lddfw' ),
            __( 'Refunds', 'lddfw' ),
            __( 'Shipping Total', 'lddfw' ),
            __( 'Commission', 'lddfw' )
        ) );
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $driver = get_userdata( $row->driver_id );
                $driver_name = ( $driver && !empty( $driver->display_name ) ? $driver->display_name : '' );
                fputcsv( $output, array(
                    $row->driver_id,
                    $driver_name,
                    $row->orders,
                    $row->orders_total,
                    ( isset( $row->refunds_total ) ? $row->refunds_total : 0 ),
                    $row->shipping_total,
                    $row->commission
                ) );
            }
        }
        fclose( $output );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        exit;
    }

    /**
     * CSV export - Sales by City.
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return void
     */
    protected function export_cities_csv( $from, $to ) {
        $rows = $this->lddfw_sales_by_city_query( $from, $to );
        $output = $this->lddfw_csv_open( 'lddfw-sales-by-city-' . $from . '-to-' . $to . '.csv' );
        fputcsv( $output, array(
            __( 'City', 'lddfw' ),
            __( 'Orders', 'lddfw' ),
            __( 'Revenue', 'lddfw' ),
            __( 'Avg Order Value', 'lddfw' )
        ) );
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $city = ( '' === $row->city ? __( '(No city)', 'lddfw' ) : $row->city );
                $aov = ( $row->orders > 0 ? $row->revenue / $row->orders : 0 );
                fputcsv( $output, array(
                    $city,
                    $row->orders,
                    $row->revenue,
                    number_format(
                        $aov,
                        2,
                        '.',
                        ''
                    )
                ) );
            }
        }
        fclose( $output );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        exit;
    }

    /**
     * CSV export - Daily Trends.
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return void
     */
    protected function export_trends_csv( $from, $to ) {
        $rows = $this->lddfw_daily_trends_query( $from, $to );
        $output = $this->lddfw_csv_open( 'lddfw-daily-trends-' . $from . '-to-' . $to . '.csv' );
        fputcsv( $output, array(
            __( 'Date', 'lddfw' ),
            __( 'Orders', 'lddfw' ),
            __( 'Revenue', 'lddfw' ),
            __( 'Commission', 'lddfw' ),
            __( 'Avg Order Value', 'lddfw' )
        ) );
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $aov = ( $row->orders > 0 ? $row->revenue / $row->orders : 0 );
                fputcsv( $output, array(
                    $row->day,
                    $row->orders,
                    $row->revenue,
                    $row->commission,
                    number_format(
                        $aov,
                        2,
                        '.',
                        ''
                    )
                ) );
            }
        }
        fclose( $output );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        exit;
    }

    /**
     * CSV export - Failed Deliveries.
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return void
     */
    protected function export_failed_csv( $from, $to ) {
        $rows = $this->lddfw_failed_deliveries_query( $from, $to );
        $output = $this->lddfw_csv_open( 'lddfw-failed-deliveries-' . $from . '-to-' . $to . '.csv' );
        fputcsv( $output, array(
            __( 'Driver ID', 'lddfw' ),
            __( 'Driver', 'lddfw' ),
            __( 'Failed', 'lddfw' ),
            __( 'Last Failure', 'lddfw' ),
            __( 'Top Reason', 'lddfw' )
        ) );
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $driver = get_userdata( $row->driver_id );
                $driver_name = ( $driver && !empty( $driver->display_name ) ? $driver->display_name : '' );
                fputcsv( $output, array(
                    $row->driver_id,
                    $driver_name,
                    $row->failed,
                    $row->last_failure,
                    ( isset( $row->top_reason ) ? $row->top_reason : '' )
                ) );
            }
        }
        fclose( $output );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        exit;
    }

    /**
     * CSV export - Ratings & Reviews (premium).
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return void
     */
    protected function export_ratings_csv( $from, $to ) {
        if ( !lddfw_fs()->is__premium_only() || !lddfw_fs()->can_use_premium_code() ) {
            wp_die( esc_html__( 'Premium Feature.', 'lddfw' ) );
        }
        $rows = $this->lddfw_ratings_query( $from, $to );
        $output = $this->lddfw_csv_open( 'lddfw-ratings-' . $from . '-to-' . $to . '.csv' );
        fputcsv( $output, array(
            __( 'Driver ID', 'lddfw' ),
            __( 'Driver', 'lddfw' ),
            __( 'Reviews', 'lddfw' ),
            __( 'Avg Rating', 'lddfw' ),
            '5',
            '4',
            '3',
            '2',
            '1',
            __( 'Last Review', 'lddfw' )
        ) );
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $driver = get_userdata( $row->driver_id );
                $driver_name = ( $driver && !empty( $driver->display_name ) ? $driver->display_name : '' );
                fputcsv( $output, array(
                    $row->driver_id,
                    $driver_name,
                    $row->reviews,
                    number_format(
                        (float) $row->avg_rating,
                        2,
                        '.',
                        ''
                    ),
                    $row->r5,
                    $row->r4,
                    $row->r3,
                    $row->r2,
                    $row->r1,
                    $row->last_review
                ) );
            }
        }
        fclose( $output );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        exit;
    }

    /**
     * CSV export - Driver Performance (premium).
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return void
     */
    protected function export_performance_csv( $from, $to ) {
        if ( !lddfw_fs()->is__premium_only() || !lddfw_fs()->can_use_premium_code() ) {
            wp_die( esc_html__( 'Premium Feature.', 'lddfw' ) );
        }
        $rows = $this->lddfw_performance_query( $from, $to );
        $output = $this->lddfw_csv_open( 'lddfw-driver-performance-' . $from . '-to-' . $to . '.csv' );
        fputcsv( $output, array(
            __( 'Driver ID', 'lddfw' ),
            __( 'Driver', 'lddfw' ),
            __( 'Assigned', 'lddfw' ),
            __( 'Delivered', 'lddfw' ),
            __( 'Failed', 'lddfw' ),
            __( 'Success %', 'lddfw' ),
            __( 'Avg Rating', 'lddfw' ),
            __( 'Revenue', 'lddfw' ),
            __( 'Commission', 'lddfw' )
        ) );
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $driver = get_userdata( $row->driver_id );
                $driver_name = ( $driver && !empty( $driver->display_name ) ? $driver->display_name : '' );
                $completed = (int) $row->delivered + (int) $row->failed;
                $success = ( $completed > 0 ? round( $row->delivered / $completed * 100, 1 ) : 0 );
                fputcsv( $output, array(
                    $row->driver_id,
                    $driver_name,
                    $row->assigned,
                    $row->delivered,
                    $row->failed,
                    $success,
                    number_format(
                        (float) $row->avg_rating,
                        2,
                        '.',
                        ''
                    ),
                    $row->revenue,
                    $row->commission
                ) );
            }
        }
        fclose( $output );
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream; WP_Filesystem cannot handle stream wrappers.
        exit;
    }

    // =========================================================================
    // Plan 2 - Report queries
    // =========================================================================
    /**
     * Query: Sales grouped by shipping city (delivered orders only).
     *
     * Uses existing `order_shipping_city` column on the sync table.
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return array<int,\stdClass> Rows: { city, orders, revenue }.
     */
    public function lddfw_sales_by_city_query( $from, $to ) {
        global $wpdb;
        $status = get_option( 'lddfw_delivered_status', '' );
        if ( '' === $status ) {
            return array();
        }
        if ( lddfw_is_hpos_enabled() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT
						COALESCE(o.order_shipping_city, \'\') as city,
						COUNT(o.order_id) as orders,
						COALESCE(SUM(o.order_total - o.order_refund_amount),0) as revenue
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status = %s
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY city
					ORDER BY revenue DESC', array($status, $from, $to) ) );
        }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT
					COALESCE(o.order_shipping_city, \'\') as city,
					COUNT(p.ID) as orders,
					COALESCE(SUM(o.order_total - o.order_refund_amount),0) as revenue
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				WHERE p.post_type = \'shop_order\'
				AND p.post_status = %s
				AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY city
				ORDER BY revenue DESC', array($status, $from, $to) ) );
    }

    /**
     * Query: Daily delivered order counts + revenue + commission.
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return array<int,\stdClass> Rows: { day, orders, revenue, commission }.
     */
    public function lddfw_daily_trends_query( $from, $to ) {
        global $wpdb;
        $status = get_option( 'lddfw_delivered_status', '' );
        if ( '' === $status ) {
            return array();
        }
        if ( lddfw_is_hpos_enabled() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT
						CAST(o.delivered_date AS DATE) as day,
						COUNT(o.order_id) as orders,
						COALESCE(SUM(o.order_total - o.order_refund_amount),0) as revenue,
						COALESCE(SUM(o.driver_commission),0) as commission
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status = %s
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY day
					ORDER BY day ASC', array($status, $from, $to) ) );
        }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT
					CAST(o.delivered_date AS DATE) as day,
					COUNT(p.ID) as orders,
					COALESCE(SUM(o.order_total - o.order_refund_amount),0) as revenue,
					COALESCE(SUM(o.driver_commission),0) as commission
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				WHERE p.post_type = \'shop_order\'
				AND p.post_status = %s
				AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY day
				ORDER BY day ASC', array($status, $from, $to) ) );
    }

    /**
     * Query: Failed deliveries grouped by driver.
     *
     * Filters by the configured `lddfw_failed_attempt_status` and uses the
     * `lddfw_failed_attempt_date` order meta to scope to the date range,
     * falling back to the order date when the meta is missing.
     *
     * @since 2.3.0
     * @param string $from From date Y-m-d.
     * @param string $to   To date Y-m-d.
     * @return array<int,\stdClass> Rows: { driver_id, failed, last_failure, top_reason }.
     */
    public function lddfw_failed_deliveries_query( $from, $to ) {
        global $wpdb;
        $status = get_option( 'lddfw_failed_attempt_status', '' );
        if ( '' === $status ) {
            return array();
        }
        if ( lddfw_is_hpos_enabled() ) {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT
						COALESCE(o.driver_id,0) as driver_id,
						COUNT(wo.id) as failed,
						MAX(COALESCE(fdm.meta_value, wo.date_created_gmt)) as last_failure
					FROM ' . $wpdb->prefix . 'wc_orders wo
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta fdm ON wo.id = fdm.order_id AND fdm.meta_key = \'lddfw_failed_attempt_date\'
					WHERE wo.type = %s AND wo.status = %s
					AND CAST(COALESCE(fdm.meta_value, wo.date_created_gmt) AS DATE) BETWEEN %s AND %s
					GROUP BY driver_id
					ORDER BY failed DESC', array(
                'shop_order',
                $status,
                $from,
                $to
            ) ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT
						COALESCE(o.driver_id,0) as driver_id,
						COUNT(p.ID) as failed,
						MAX(COALESCE(fdm.meta_value, p.post_date)) as last_failure
					FROM ' . $wpdb->prefix . 'posts p
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'postmeta fdm ON p.ID = fdm.post_id AND fdm.meta_key = \'lddfw_failed_attempt_date\'
					WHERE p.post_type = %s AND p.post_status = %s
					AND CAST(COALESCE(fdm.meta_value, p.post_date) AS DATE) BETWEEN %s AND %s
					GROUP BY driver_id
					ORDER BY failed DESC', array(
                'shop_order',
                $status,
                $from,
                $to
            ) ) );
        }
        // Augment each row with its top reason (separate lightweight query
        // per driver - keeps the main query simple and index-friendly).
        if ( !empty( $rows ) ) {
            foreach ( $rows as &$row ) {
                $row->top_reason = $this->lddfw_driver_top_failure_reason( (int) $row->driver_id, $from, $to );
            }
        }
        return ( $rows ? $rows : array() );
    }

    /**
     * Helper: compute the most-used failure reason for a driver in a range.
     *
     * Reads `lddfw_driver_note` order meta for failed orders and matches
     * against configured reasons. Returns localized string or empty.
     *
     * @since 2.3.0
     * @param int    $driver_id Driver user ID (0 = no driver assigned).
     * @param string $from      From date.
     * @param string $to        To date.
     * @return string Top reason label or empty string.
     */
    protected function lddfw_driver_top_failure_reason( $driver_id, $from, $to ) {
        $breakdown = $this->lddfw_failed_reason_breakdown_query( $from, $to, $driver_id );
        if ( empty( $breakdown ) ) {
            return '';
        }
        // Find the entry with the highest count.
        $top_key = '';
        $top_count = 0;
        foreach ( $breakdown as $reason => $count ) {
            if ( $count > $top_count ) {
                $top_count = $count;
                $top_key = $reason;
            }
        }
        return $top_key;
    }

    /**
     * Query: Failure reason breakdown - groups driver_note values into buckets
     * matching the configured `lddfw_failed_delivery_reason_1..5` options.
     * Unmatched custom notes roll up as "Other".
     *
     * @since 2.3.0
     * @param string $from      From date.
     * @param string $to        To date.
     * @param int    $driver_id Optional driver filter (0 = all drivers).
     * @return array<string,int> Reason => count (preserves reason order).
     */
    public function lddfw_failed_reason_breakdown_query( $from, $to, $driver_id = 0 ) {
        global $wpdb;
        $status = get_option( 'lddfw_failed_attempt_status', '' );
        if ( '' === $status ) {
            return array();
        }
        $configured = array();
        for ($i = 1; $i <= 5; $i++) {
            $val = (string) get_option( 'lddfw_failed_delivery_reason_' . $i, '' );
            if ( '' !== $val ) {
                $configured[] = $val;
            }
        }
        $driver_clause = '';
        $args = array(
            'shop_order',
            $status,
            $from,
            $to
        );
        if ( $driver_id > 0 ) {
            $driver_clause = ' AND o.driver_id = %d ';
            $args[] = (int) $driver_id;
        }
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $driver_clause is an optional prepared SQL fragment; $args count matches dynamically.
        if ( lddfw_is_hpos_enabled() ) {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT COALESCE(nm.meta_value, \'\') as reason, COUNT(*) as c
					FROM ' . $wpdb->prefix . 'wc_orders wo
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta fdm ON wo.id = fdm.order_id AND fdm.meta_key = \'lddfw_failed_attempt_date\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta nm ON wo.id = nm.order_id AND nm.meta_key = \'lddfw_driver_note\'
					WHERE wo.type = %s AND wo.status = %s
					AND CAST(COALESCE(fdm.meta_value, wo.date_created_gmt) AS DATE) BETWEEN %s AND %s
					' . $driver_clause . '
					GROUP BY reason', $args ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT COALESCE(nm.meta_value, \'\') as reason, COUNT(*) as c
					FROM ' . $wpdb->prefix . 'posts p
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'postmeta fdm ON p.ID = fdm.post_id AND fdm.meta_key = \'lddfw_failed_attempt_date\'
					LEFT JOIN ' . $wpdb->prefix . 'postmeta nm ON p.ID = nm.post_id AND nm.meta_key = \'lddfw_driver_note\'
					WHERE p.post_type = %s AND p.post_status = %s
					AND CAST(COALESCE(fdm.meta_value, p.post_date) AS DATE) BETWEEN %s AND %s
					' . $driver_clause . '
					GROUP BY reason', $args ) );
        }
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $out = array();
        foreach ( $configured as $reason ) {
            $out[$reason] = 0;
        }
        $out[__( 'Other', 'lddfw' )] = 0;
        if ( empty( $rows ) ) {
            return $out;
        }
        foreach ( $rows as $r ) {
            $reason = (string) $r->reason;
            if ( in_array( $reason, $configured, true ) ) {
                $out[$reason] += (int) $r->c;
            } else {
                $out[__( 'Other', 'lddfw' )] += (int) $r->c;
            }
        }
        return $out;
    }

    /**
     * Query: last N failed orders (for the collapsible list on the Failed tab).
     *
     * @since 2.3.0
     * @param string $from From date.
     * @param string $to   To date.
     * @param int    $limit Max rows. Default 20.
     * @return array<int,\stdClass>
     */
    public function lddfw_recent_failed_orders( $from, $to, $limit = 20 ) {
        global $wpdb;
        $status = get_option( 'lddfw_failed_attempt_status', '' );
        if ( '' === $status ) {
            return array();
        }
        $limit = max( 1, (int) $limit );
        if ( lddfw_is_hpos_enabled() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT wo.id as order_id,
						COALESCE(fdm.meta_value, wo.date_created_gmt) as failed_date,
						COALESCE(o.driver_id,0) as driver_id,
						COALESCE(o.order_shipping_city, \'\') as city,
						COALESCE(nm.meta_value, \'\') as reason
					FROM ' . $wpdb->prefix . 'wc_orders wo
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta fdm ON wo.id = fdm.order_id AND fdm.meta_key = \'lddfw_failed_attempt_date\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta nm ON wo.id = nm.order_id AND nm.meta_key = \'lddfw_driver_note\'
					WHERE wo.type = %s AND wo.status = %s
					AND CAST(COALESCE(fdm.meta_value, wo.date_created_gmt) AS DATE) BETWEEN %s AND %s
					ORDER BY failed_date DESC
					LIMIT %d', array(
                'shop_order',
                $status,
                $from,
                $to,
                $limit
            ) ) );
        }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT p.ID as order_id,
					COALESCE(fdm.meta_value, p.post_date) as failed_date,
					COALESCE(o.driver_id,0) as driver_id,
					COALESCE(o.order_shipping_city, \'\') as city,
					COALESCE(nm.meta_value, \'\') as reason
				FROM ' . $wpdb->prefix . 'posts p
				LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				LEFT JOIN ' . $wpdb->prefix . 'postmeta fdm ON p.ID = fdm.post_id AND fdm.meta_key = \'lddfw_failed_attempt_date\'
				LEFT JOIN ' . $wpdb->prefix . 'postmeta nm ON p.ID = nm.post_id AND nm.meta_key = \'lddfw_driver_note\'
				WHERE p.post_type = %s AND p.post_status = %s
				AND CAST(COALESCE(fdm.meta_value, p.post_date) AS DATE) BETWEEN %s AND %s
				ORDER BY failed_date DESC
				LIMIT %d', array(
            'shop_order',
            $status,
            $from,
            $to,
            $limit
        ) ) );
    }

    /**
     * Query: Per-driver ratings aggregates (premium).
     *
     * @since 2.3.0
     * @param string $from From date.
     * @param string $to   To date.
     * @return array<int,\stdClass>
     */
    public function lddfw_ratings_query( $from, $to ) {
        global $wpdb;
        $status = get_option( 'lddfw_delivered_status', '' );
        if ( '' === $status ) {
            return array();
        }
        if ( lddfw_is_hpos_enabled() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT o.driver_id,
						COUNT(o.driver_rating) as reviews,
						AVG(o.driver_rating) as avg_rating,
						SUM(CASE WHEN o.driver_rating = 5 THEN 1 ELSE 0 END) as r5,
						SUM(CASE WHEN o.driver_rating = 4 THEN 1 ELSE 0 END) as r4,
						SUM(CASE WHEN o.driver_rating = 3 THEN 1 ELSE 0 END) as r3,
						SUM(CASE WHEN o.driver_rating = 2 THEN 1 ELSE 0 END) as r2,
						SUM(CASE WHEN o.driver_rating = 1 THEN 1 ELSE 0 END) as r1,
						MAX(o.review_date) as last_review
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status = %s
					AND o.driver_rating IS NOT NULL AND o.driver_rating > 0
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY o.driver_id
					ORDER BY avg_rating DESC, reviews DESC', array($status, $from, $to) ) );
        }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT o.driver_id,
					COUNT(o.driver_rating) as reviews,
					AVG(o.driver_rating) as avg_rating,
					SUM(CASE WHEN o.driver_rating = 5 THEN 1 ELSE 0 END) as r5,
					SUM(CASE WHEN o.driver_rating = 4 THEN 1 ELSE 0 END) as r4,
					SUM(CASE WHEN o.driver_rating = 3 THEN 1 ELSE 0 END) as r3,
					SUM(CASE WHEN o.driver_rating = 2 THEN 1 ELSE 0 END) as r2,
					SUM(CASE WHEN o.driver_rating = 1 THEN 1 ELSE 0 END) as r1,
					MAX(o.review_date) as last_review
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				WHERE p.post_type = \'shop_order\'
				AND p.post_status = %s
				AND o.driver_rating IS NOT NULL AND o.driver_rating > 0
				AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY o.driver_id
				ORDER BY avg_rating DESC, reviews DESC', array($status, $from, $to) ) );
    }

    /**
     * Query: Most recent reviews in a date range (premium).
     *
     * @since 2.3.0
     * @param string $from From date.
     * @param string $to   To date.
     * @param int    $limit Max rows.
     * @return array<int,\stdClass>
     */
    public function lddfw_recent_reviews_in_range( $from, $to, $limit = 10 ) {
        global $wpdb;
        $status = get_option( 'lddfw_delivered_status', '' );
        if ( '' === $status ) {
            return array();
        }
        $limit = max( 1, (int) $limit );
        if ( lddfw_is_hpos_enabled() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT o.order_id, o.driver_id, o.driver_rating, o.review_comment, o.review_date
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status = %s
					AND o.driver_rating IS NOT NULL AND o.driver_rating > 0
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					ORDER BY o.review_date DESC
					LIMIT %d', array(
                $status,
                $from,
                $to,
                $limit
            ) ) );
        }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT o.order_id, o.driver_id, o.driver_rating, o.review_comment, o.review_date
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				WHERE p.post_type = \'shop_order\'
				AND p.post_status = %s
				AND o.driver_rating IS NOT NULL AND o.driver_rating > 0
				AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
				ORDER BY o.review_date DESC
				LIMIT %d', array(
            $status,
            $from,
            $to,
            $limit
        ) ) );
    }

    /**
     * Query: Per-driver performance aggregates (premium).
     *
     * Groups by driver and aggregates counts for each lifecycle status plus
     * revenue/commission/rating. Uses the `lddfw_orders` sync table which is
     * updated when order statuses change so counts reflect the current state.
     *
     * @since 2.3.0
     * @param string $from From date.
     * @param string $to   To date.
     * @return array<int,\stdClass>
     */
    public function lddfw_performance_query( $from, $to ) {
        global $wpdb;
        $delivered = get_option( 'lddfw_delivered_status', '' );
        $failed = get_option( 'lddfw_failed_attempt_status', '' );
        $ofd = get_option( 'lddfw_out_for_delivery_status', '' );
        $assigned = get_option( 'lddfw_driver_assigned_status', '' );
        if ( lddfw_is_hpos_enabled() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT o.driver_id,
						SUM(CASE WHEN wo.status IN (%s,%s,%s,%s) THEN 1 ELSE 0 END) as assigned,
						SUM(CASE WHEN wo.status = %s THEN 1 ELSE 0 END) as delivered,
						SUM(CASE WHEN wo.status = %s THEN 1 ELSE 0 END) as failed,
						COALESCE(AVG(NULLIF(o.driver_rating,0)),0) as avg_rating,
						COALESCE(SUM(CASE WHEN wo.status = %s THEN (o.order_total - o.order_refund_amount) ELSE 0 END),0) as revenue,
						COALESCE(SUM(CASE WHEN wo.status = %s THEN o.driver_commission ELSE 0 END),0) as commission
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status IN (%s,%s,%s,%s)
					AND CAST(wo.date_created_gmt AS DATE) BETWEEN %s AND %s
					AND o.driver_id > 0
					GROUP BY o.driver_id
					ORDER BY delivered DESC', array(
                $assigned,
                $ofd,
                $delivered,
                $failed,
                $delivered,
                $failed,
                $delivered,
                $delivered,
                $assigned,
                $ofd,
                $delivered,
                $failed,
                $from,
                $to
            ) ) );
        }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT o.driver_id,
					SUM(CASE WHEN p.post_status IN (%s,%s,%s,%s) THEN 1 ELSE 0 END) as assigned,
					SUM(CASE WHEN p.post_status = %s THEN 1 ELSE 0 END) as delivered,
					SUM(CASE WHEN p.post_status = %s THEN 1 ELSE 0 END) as failed,
					COALESCE(AVG(NULLIF(o.driver_rating,0)),0) as avg_rating,
					COALESCE(SUM(CASE WHEN p.post_status = %s THEN (o.order_total - o.order_refund_amount) ELSE 0 END),0) as revenue,
					COALESCE(SUM(CASE WHEN p.post_status = %s THEN o.driver_commission ELSE 0 END),0) as commission
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				WHERE p.post_type = \'shop_order\'
				AND p.post_status IN (%s,%s,%s,%s)
				AND CAST(p.post_date AS DATE) BETWEEN %s AND %s
				AND o.driver_id > 0
				GROUP BY o.driver_id
				ORDER BY delivered DESC', array(
            $assigned,
            $ofd,
            $delivered,
            $failed,
            $delivered,
            $failed,
            $delivered,
            $delivered,
            $assigned,
            $ofd,
            $delivered,
            $failed,
            $from,
            $to
        ) ) );
    }

    // =========================================================================
    // Plan 2 - Report renderers
    // =========================================================================
    /**
     * Render report: Sales by City.
     *
     * @since 2.3.0
     * @return void
     */
    public function sales_by_city_report() {
        list( $from, $to, $range ) = $this->lddfw_get_report_range();
        $rows = $this->lddfw_sales_by_city_query( $from, $to );
        $total_orders = 0;
        $total_revenue = 0;
        $top_revenue = null;
        $top_orders = null;
        foreach ( $rows as $row ) {
            $total_orders += (int) $row->orders;
            $total_revenue += (float) $row->revenue;
            if ( null === $top_revenue || $row->revenue > $top_revenue->revenue ) {
                $top_revenue = $row;
            }
            if ( null === $top_orders || $row->orders > $top_orders->orders ) {
                $top_orders = $row;
            }
        }
        $aov = ( $total_orders > 0 ? $total_revenue / $total_orders : 0 );
        $city_count = count( $rows );
        $top_city_rev = ( $top_revenue ? ( '' === $top_revenue->city ? __( '(No city)', 'lddfw' ) : $top_revenue->city ) : '-' );
        $top_city_ord = ( $top_orders ? ( '' === $top_orders->city ? __( '(No city)', 'lddfw' ) : $top_orders->city ) : '-' );
        $aov_html = ( function_exists( 'wc_price' ) ? wc_price( $aov ) : esc_html( (string) $aov ) );
        echo '<h2 class="lddfw-section-title"><span class="dashicons dashicons-location-alt"></span>' . esc_html__( 'Sales by City', 'lddfw' ) . '</h2>';
        echo '<div class="lddfw-stat-cards">';
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( (string) $city_count ),
            __( 'Total Cities', 'lddfw' ),
            'admin-site-alt3',
            'info'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( esc_html( $top_city_rev ) ),
            __( 'Top City (Revenue)', 'lddfw' ),
            'money-alt',
            'success'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( esc_html( $top_city_ord ) ),
            __( 'Top City (Orders)', 'lddfw' ),
            'cart',
            'warning'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $aov_html ),
            __( 'Avg Order Value', 'lddfw' ),
            'chart-line',
            'info'
        );
        echo '</div>';
        echo self::lddfw_render_filter_bar( array(
            'tab'   => 'cities',
            'from'  => $from,
            'to'    => $to,
            'range' => $range,
        ) );
        echo '<div class="lddfw-modern-table-wrap"><table class="lddfw-modern-table">
		<thead><tr>
			<th>' . esc_html__( 'City', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Orders', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Revenue', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Avg Order Value', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( '% of Orders', 'lddfw' ) . '</th>
		</tr></thead><tbody>';
        if ( empty( $rows ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="5">' . esc_html__( 'No orders', 'lddfw' ) . '</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $city = ( '' === $row->city ? __( '(No city)', 'lddfw' ) : $row->city );
                $row_aov = ( $row->orders > 0 ? $row->revenue / $row->orders : 0 );
                $pct = ( $total_orders > 0 ? round( $row->orders / $total_orders * 100, 1 ) : 0 );
                echo '<tr>
					<td><span class="dashicons dashicons-location"></span> ' . esc_html( $city ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( (string) (int) $row->orders ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( function_exists( 'wc_price' ) ? wc_price( $row->revenue ) : esc_html( (string) $row->revenue ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( function_exists( 'wc_price' ) ? wc_price( $row_aov ) : esc_html( (string) $row_aov ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( esc_html( $pct ) . '%' ) . '</td>
				</tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    /**
     * Render report: Daily Trends.
     *
     * @since 2.3.0
     * @return void
     */
    public function daily_trends_report() {
        list( $from, $to, $range ) = $this->lddfw_get_report_range();
        $rows = $this->lddfw_daily_trends_query( $from, $to );
        $total_orders = 0;
        $total_rev = 0;
        $best = null;
        $worst = null;
        $max_orders = 0;
        foreach ( $rows as $row ) {
            $total_orders += (int) $row->orders;
            $total_rev += (float) $row->revenue;
            if ( null === $best || $row->orders > $best->orders ) {
                $best = $row;
            }
            if ( null === $worst || $row->orders < $worst->orders ) {
                $worst = $row;
            }
            if ( $row->orders > $max_orders ) {
                $max_orders = (int) $row->orders;
            }
        }
        $day_count = count( $rows );
        $avg_ord = ( $day_count > 0 ? round( $total_orders / $day_count, 1 ) : 0 );
        $avg_rev = ( $day_count > 0 ? $total_rev / $day_count : 0 );
        $date_fmt = lddfw_date_format( 'date' );
        echo '<h2 class="lddfw-section-title"><span class="dashicons dashicons-chart-line"></span>' . esc_html__( 'Daily Trends', 'lddfw' ) . '</h2>';
        echo '<div class="lddfw-stat-cards">';
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( ( $best ? esc_html( gmdate( $date_fmt, strtotime( $best->day ) ) . ' (' . (int) $best->orders . ')' ) : '-' ) ),
            __( 'Best Day', 'lddfw' ),
            'yes-alt',
            'success'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( ( $worst ? esc_html( gmdate( $date_fmt, strtotime( $worst->day ) ) . ' (' . (int) $worst->orders . ')' ) : '-' ) ),
            __( 'Worst Day', 'lddfw' ),
            'warning',
            'warning'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( (string) $avg_ord ),
            __( 'Avg Orders / Day', 'lddfw' ),
            'cart',
            'info'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( ( function_exists( 'wc_price' ) ? wc_price( $avg_rev ) : esc_html( (string) $avg_rev ) ) ),
            __( 'Avg Revenue / Day', 'lddfw' ),
            'money-alt',
            'success'
        );
        echo '</div>';
        echo self::lddfw_render_filter_bar( array(
            'tab'   => 'trends',
            'from'  => $from,
            'to'    => $to,
            'range' => $range,
        ) );
        // Pure-CSS bar chart. Hidden on free (free users see stars in the data table below instead).
        if ( !empty( $rows ) && !(function_exists( 'lddfw_is_free' ) && lddfw_is_free()) ) {
            echo '<div class="lddfw-bar-chart" role="img" aria-label="' . esc_attr__( 'Daily orders chart', 'lddfw' ) . '">';
            foreach ( $rows as $row ) {
                $h = ( $max_orders > 0 ? max( 2, (int) round( $row->orders / $max_orders * 100 ) ) : 0 );
                $lbl = gmdate( 'M j', strtotime( $row->day ) );
                $tip = esc_attr( gmdate( $date_fmt, strtotime( $row->day ) ) . ' · ' . (int) $row->orders . ' · ' . wp_strip_all_tags( ( function_exists( 'wc_price' ) ? wc_price( $row->revenue ) : (string) $row->revenue ) ) );
                echo '<div class="lddfw-bar-chart__col" title="' . $tip . '">';
                echo '<span class="lddfw-bar-chart__count">' . (int) $row->orders . '</span>';
                echo '<span class="lddfw-bar-chart__fill" style="height:' . (int) $h . '%"></span>';
                echo '<span class="lddfw-bar-chart__label">' . esc_html( $lbl ) . '</span>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '<div class="lddfw-modern-table-wrap"><table class="lddfw-modern-table">
		<thead><tr>
			<th>' . esc_html__( 'Date', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Orders', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Revenue', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Commission', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Avg Order Value', 'lddfw' ) . '</th>
		</tr></thead><tbody>';
        if ( empty( $rows ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="5">' . esc_html__( 'No orders', 'lddfw' ) . '</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $row_aov = ( $row->orders > 0 ? $row->revenue / $row->orders : 0 );
                echo '<tr>
					<td>' . esc_html( gmdate( $date_fmt, strtotime( $row->day ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( (string) (int) $row->orders ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( function_exists( 'wc_price' ) ? wc_price( $row->revenue ) : esc_html( (string) $row->revenue ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( function_exists( 'wc_price' ) ? wc_price( $row->commission ) : esc_html( (string) $row->commission ) ) ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( ( function_exists( 'wc_price' ) ? wc_price( $row_aov ) : esc_html( (string) $row_aov ) ) ) . '</td>
				</tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    /**
     * Render report: Failed Deliveries.
     *
     * @since 2.3.0
     * @return void
     */
    public function failed_deliveries_report() {
        list( $from, $to, $range ) = $this->lddfw_get_report_range();
        $rows = $this->lddfw_failed_deliveries_query( $from, $to );
        $reasons = $this->lddfw_failed_reason_breakdown_query( $from, $to );
        $recent_orders = $this->lddfw_recent_failed_orders( $from, $to, 20 );
        // KPI aggregates.
        $total_failed = 0;
        $top_driver = null;
        foreach ( $rows as $row ) {
            $total_failed += (int) $row->failed;
            if ( null === $top_driver || $row->failed > $top_driver->failed ) {
                $top_driver = $row;
            }
        }
        // Compute failure rate: failed / (failed + delivered) in the same range.
        $delivered_rows = $this->lddfw_drivers_commission_query( $from, $to );
        $total_delivered = 0;
        foreach ( $delivered_rows as $dr ) {
            $total_delivered += (int) $dr->orders;
        }
        $total_completed = $total_failed + $total_delivered;
        $fail_rate = ( $total_completed > 0 ? round( $total_failed / $total_completed * 100, 1 ) : 0 );
        // Top reason.
        $top_reason = '';
        $top_reason_n = 0;
        foreach ( $reasons as $r => $n ) {
            if ( $n > $top_reason_n ) {
                $top_reason_n = $n;
                $top_reason = $r;
            }
        }
        $top_driver_label = '-';
        if ( $top_driver ) {
            $d = get_userdata( (int) $top_driver->driver_id );
            $top_driver_label = ( $d && !empty( $d->display_name ) ? $d->display_name . ' (' . (int) $top_driver->failed . ')' : __( 'Unassigned', 'lddfw' ) . ' (' . (int) $top_driver->failed . ')' );
        }
        echo '<h2 class="lddfw-section-title"><span class="dashicons dashicons-warning"></span>' . esc_html__( 'Failed Deliveries', 'lddfw' ) . '</h2>';
        echo '<div class="lddfw-stat-cards">';
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( (string) $total_failed ),
            __( 'Total Failed', 'lddfw' ),
            'no-alt',
            'danger'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( $fail_rate . '%' ),
            __( 'Failure Rate', 'lddfw' ),
            'chart-line',
            'warning'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( ( $top_reason ? esc_html( $top_reason ) : '-' ) ),
            __( 'Top Reason', 'lddfw' ),
            'info',
            'info'
        );
        echo self::lddfw_render_kpi_card(
            lddfw_admin_premium_feature( esc_html( $top_driver_label ) ),
            __( 'Most Failures', 'lddfw' ),
            'admin-users',
            'warning'
        );
        echo '</div>';
        echo self::lddfw_render_filter_bar( array(
            'tab'   => 'failed',
            'from'  => $from,
            'to'    => $to,
            'range' => $range,
        ) );
        // Reason breakdown bar list. Hidden on free (reasons/counts are premium insights).
        if ( !(function_exists( 'lddfw_is_free' ) && lddfw_is_free()) ) {
            echo '<div class="lddfw-page-section">';
            echo '<h3 class="lddfw-subsection-title">' . esc_html__( 'Reason Breakdown', 'lddfw' ) . '</h3>';
            echo '<div class="lddfw-reason-bars">';
            $max_reason = 0;
            foreach ( $reasons as $c ) {
                if ( $c > $max_reason ) {
                    $max_reason = $c;
                }
            }
            if ( 0 === $max_reason ) {
                echo '<p class="lddfw-empty-msg">' . esc_html__( 'No failed deliveries in this range.', 'lddfw' ) . '</p>';
            } else {
                foreach ( $reasons as $reason => $count ) {
                    $pct = ( $max_reason > 0 ? (int) round( $count / $max_reason * 100 ) : 0 );
                    $pct_total = ( $total_failed > 0 ? round( $count / $total_failed * 100, 1 ) : 0 );
                    echo '<div class="lddfw-reason-bar">';
                    echo '<div class="lddfw-reason-bar__label">' . esc_html( $reason ) . '</div>';
                    echo '<div class="lddfw-reason-bar__track"><div class="lddfw-reason-bar__fill" style="width:' . (int) $pct . '%"></div></div>';
                    echo '<div class="lddfw-reason-bar__count">' . (int) $count . ' <span class="lddfw-reason-bar__pct">(' . esc_html( (string) $pct_total ) . '%)</span></div>';
                    echo '</div>';
                }
            }
            echo '</div></div>';
        }
        // Driver table.
        echo '<h3 class="lddfw-subsection-title">' . esc_html__( 'By Driver', 'lddfw' ) . '</h3>';
        echo '<div class="lddfw-modern-table-wrap"><table class="lddfw-modern-table">
		<thead><tr>
			<th>' . esc_html__( 'Driver', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Failed', 'lddfw' ) . '</th>
			<th class="lddfw-text-center">' . esc_html__( 'Last Failure', 'lddfw' ) . '</th>
			<th>' . esc_html__( 'Top Reason', 'lddfw' ) . '</th>
		</tr></thead><tbody>';
        if ( empty( $rows ) ) {
            echo '<tr class="lddfw-empty-row"><td colspan="4">' . esc_html__( 'No failed deliveries', 'lddfw' ) . '</td></tr>';
        } else {
            $date_fmt = lddfw_date_format( 'date' );
            foreach ( $rows as $row ) {
                $driver = ( (int) $row->driver_id > 0 ? get_userdata( (int) $row->driver_id ) : null );
                $driver_name = ( $driver && !empty( $driver->display_name ) ? $driver->display_name : __( 'Unassigned', 'lddfw' ) );
                $avatar = self::lddfw_render_avatar( $driver_name, (int) $row->driver_id );
                $last = ( $row->last_failure ? gmdate( $date_fmt, strtotime( $row->last_failure ) ) : '-' );
                echo '<tr>
					<td>' . $avatar . esc_html( $driver_name ) . '</td>
					<td class="lddfw-text-center">' . self::lddfw_render_badge( lddfw_admin_premium_feature( (string) (int) $row->failed ), 'danger' ) . '</td>
					<td class="lddfw-text-center">' . lddfw_admin_premium_feature( esc_html( $last ) ) . '</td>
					<td>' . lddfw_admin_premium_feature( esc_html( ( isset( $row->top_reason ) ? $row->top_reason : '' ) ) ) . '</td>
				</tr>';
            }
        }
        echo '</tbody></table></div>';
        // Collapsible recent failed orders. Hidden on free (reveals specific order IDs / drivers).
        if ( !empty( $recent_orders ) && !(function_exists( 'lddfw_is_free' ) && lddfw_is_free()) ) {
            echo '<div class="lddfw-failed-orders-toggle">';
            echo '<details><summary><span class="dashicons dashicons-list-view"></span> ' . esc_html( sprintf( 
                /* translators: %d: count */
                __( 'Last %d failed orders', 'lddfw' ),
                count( $recent_orders )
             ) ) . '</summary>';
            echo '<div class="lddfw-modern-table-wrap"><table class="lddfw-modern-table">
			<thead><tr>
				<th>' . esc_html__( 'Order #', 'lddfw' ) . '</th>
				<th>' . esc_html__( 'Date', 'lddfw' ) . '</th>
				<th>' . esc_html__( 'Driver', 'lddfw' ) . '</th>
				<th>' . esc_html__( 'City', 'lddfw' ) . '</th>
				<th>' . esc_html__( 'Reason', 'lddfw' ) . '</th>
			</tr></thead><tbody>';
            $date_fmt = lddfw_date_format( 'date' );
            foreach ( $recent_orders as $ord ) {
                $driver = ( (int) $ord->driver_id > 0 ? get_userdata( (int) $ord->driver_id ) : null );
                $driver_name = ( $driver && !empty( $driver->display_name ) ? $driver->display_name : __( 'Unassigned', 'lddfw' ) );
                $order_url = admin_url( 'post.php?post=' . (int) $ord->order_id . '&action=edit' );
                $when = ( $ord->failed_date ? gmdate( $date_fmt, strtotime( $ord->failed_date ) ) : '-' );
                $city = ( '' === $ord->city ? '-' : $ord->city );
                echo '<tr>
					<td><a href="' . esc_url( $order_url ) . '">#' . (int) $ord->order_id . '</a></td>
					<td>' . esc_html( $when ) . '</td>
					<td>' . esc_html( $driver_name ) . '</td>
					<td>' . esc_html( $city ) . '</td>
					<td>' . esc_html( $ord->reason ) . '</td>
				</tr>';
            }
            echo '</tbody></table></div></details></div>';
        }
    }

    /**
     * Render a color-coded rating chip (emoji + numeric score).
     *
     * Uses the same palette as the customer-facing review UI so ratings are
     * visually consistent across the plugin.
     *
     * @since 2.3.0
     * @param float|int $rating Rating 0-5.
     * @return string HTML.
     */
    public static function lddfw_render_rating_chip( $rating ) {
        $r = (float) $rating;
        if ( $r <= 0 ) {
            return '<span class="lddfw-muted">-</span>';
        }
        if ( $r >= 4.5 ) {
            $v = 'excellent';
            $e = '😍';
        } elseif ( $r >= 3.5 ) {
            $v = 'good';
            $e = '🙂';
        } elseif ( $r >= 2.5 ) {
            $v = 'neutral';
            $e = '😐';
        } elseif ( $r >= 1.5 ) {
            $v = 'bad';
            $e = '🙁';
        } else {
            $v = 'awful';
            $e = '😡';
        }
        return '<span class="lddfw-rating-chip lddfw-rating-chip--' . esc_attr( $v ) . '">' . $e . ' ' . esc_html( number_format( $r, 1 ) ) . '</span>';
    }

}
