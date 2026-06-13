<?php

/**
 * Orders page.
 *
 * All the orders functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
/**
 * Orders class.
 *
 * All the orders functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Orders {
    /**
     * Orders count query.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_orders_count_query( $driver_id ) {
        global $wpdb;
        // Get cache.
        $transient_key = 'lddfw-driver-' . $driver_id . '-orders-count-' . date_i18n( 'Y-m-d' );
        $orders_count = get_transient( $transient_key );
        if ( false === $orders_count ) {
            if ( lddfw_is_hpos_enabled() ) {
                // HPOS-enabled environment query.
                $orders_count = $wpdb->get_results( $wpdb->prepare( 'SELECT o.status AS post_status, COUNT(o.id) AS orders
						FROM  ' . $wpdb->prefix . 'wc_orders o
						INNER JOIN  ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
						WHERE o.type = \'shop_order\' AND ldo.driver_id = %d AND
						(
							o.status IN (%s, %s, %s) OR
							( o.status = %s AND CAST(ldo.delivered_date AS DATE) BETWEEN %s AND %s )
						)
						GROUP BY o.status', array(
                    $driver_id,
                    get_option( 'lddfw_driver_assigned_status', '' ),
                    get_option( 'lddfw_out_for_delivery_status', '' ),
                    get_option( 'lddfw_failed_attempt_status', '' ),
                    get_option( 'lddfw_delivered_status', '' ),
                    date_i18n( 'Y-m-d' ),
                    date_i18n( 'Y-m-d' )
                ) ) );
            } else {
                // Non-HPOS environment query
                $orders_count = $wpdb->get_results( $wpdb->prepare( 'SELECT post_status , count(p.ID) as orders  FROM ' . $wpdb->prefix . 'posts p
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
					WHERE
					p.post_type = \'shop_order\' AND driver_id = %d AND
					(
						post_status in (%s,%s,%s) or
						( post_status = %s AND CAST(delivered_date AS DATE) BETWEEN %s AND %s )
					)
					group by post_status', array(
                    $driver_id,
                    get_option( 'lddfw_driver_assigned_status', '' ),
                    get_option( 'lddfw_out_for_delivery_status', '' ),
                    get_option( 'lddfw_failed_attempt_status', '' ),
                    get_option( 'lddfw_delivered_status', '' ),
                    date_i18n( 'Y-m-d' ),
                    date_i18n( 'Y-m-d' )
                ) ) );
                // db call ok.
            }
            // Set cache.
            Set_transient( $transient_key, $orders_count, 30 * MINUTE_IN_SECONDS );
        }
        return $orders_count;
    }

    /**
     * Drivers orders dashboard report.
     *
     * @since 1.0.0
     * @return html
     */
    public function lddfw_drivers_orders_dashboard_report_query() {
        global $wpdb;
        if ( lddfw_is_hpos_enabled() ) {
            // Query adapted for HPOS-enabled environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT
					ldo.driver_id, o.status AS post_status, u.display_name AS driver_name, COUNT(o.id) AS orders
					FROM ' . $wpdb->prefix . 'wc_orders o
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
					INNER JOIN ' . $wpdb->base_prefix . 'users u ON u.id = ldo.driver_id
					WHERE o.type = \'shop_order\' AND (
						o.status IN (%s, %s, %s) OR
						( o.status = %s AND CAST(ldo.delivered_date AS DATE) BETWEEN %s AND %s )
					)
					GROUP BY ldo.driver_id, o.status
					ORDER BY ldo.driver_id', array(
                get_option( 'lddfw_driver_assigned_status', '' ),
                get_option( 'lddfw_out_for_delivery_status', '' ),
                get_option( 'lddfw_failed_attempt_status', '' ),
                get_option( 'lddfw_delivered_status', '' ),
                date_i18n( 'Y-m-d' ),
                date_i18n( 'Y-m-d' )
            ) ) );
        } else {
            // Original query for non-HPOS environments.
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT
				driver_id, post_status, u.display_name driver_name , count(p.ID) as orders
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				INNER JOIN ' . $wpdb->base_prefix . 'users u ON u.id = o.driver_id
				WHERE
				p.post_type = \'shop_order\' AND
				(
					post_status in (%s,%s,%s) OR
					( post_status = %s AND CAST(delivered_date AS DATE) BETWEEN %s AND %s )
				)
				group by driver_id, post_status
				order by driver_id ', array(
                get_option( 'lddfw_driver_assigned_status', '' ),
                get_option( 'lddfw_out_for_delivery_status', '' ),
                get_option( 'lddfw_failed_attempt_status', '' ),
                get_option( 'lddfw_delivered_status', '' ),
                date_i18n( 'Y-m-d' ),
                date_i18n( 'Y-m-d' )
            ) ) );
            // db call ok; no-cache ok.
        }
        return $query;
    }

    /**
     * Status counts for the dashboard KPI cards (orders with a driver assigned).
     *
     * Matches the admin order list filter `lddfw_orders_filter=-1` ("With drivers"):
     * only orders where `lddfw_driverid` exists and is not empty or `-1`.
     * - Driver-assigned, out-for-delivery, failed-attempt: current count regardless of date.
     * - Delivered: today only, joined via the ldddfw_orders sync table for the delivered_date.
     *
     * @since 2.3.1
     * @return array Associative array of status => order count.
     */
    public function lddfw_all_orders_status_counts() {
        global $wpdb;
        $driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
        $out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
        $failed_attempt_status = get_option( 'lddfw_failed_attempt_status', '' );
        $delivered_status = get_option( 'lddfw_delivered_status', '' );
        $today = date_i18n( 'Y-m-d' );
        $counts = array(
            $driver_assigned_status  => 0,
            $out_for_delivery_status => 0,
            $failed_attempt_status   => 0,
            $delivered_status        => 0,
        );
        if ( lddfw_is_hpos_enabled() ) {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT o.status AS post_status, COUNT(o.id) AS orders
					FROM ' . $wpdb->prefix . 'wc_orders o
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON o.id = ldo.order_id
					WHERE o.type = \'shop_order\'
					AND om.meta_value != %s AND om.meta_value != \'\'
					AND (
						o.status IN (%s, %s, %s) OR
						( o.status = %s AND CAST( ldo.delivered_date AS DATE ) = %s )
					)
					GROUP BY o.status', array(
                '-1',
                $driver_assigned_status,
                $out_for_delivery_status,
                $failed_attempt_status,
                $delivered_status,
                $today
            ) ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT p.post_status, COUNT(p.ID) AS orders
					FROM ' . $wpdb->prefix . 'posts p
					INNER JOIN ' . $wpdb->prefix . 'postmeta pm ON p.ID = pm.post_id AND pm.meta_key = \'lddfw_driverid\'
					LEFT JOIN ' . $wpdb->prefix . 'lddfw_orders ldo ON p.ID = ldo.order_id
					WHERE p.post_type = \'shop_order\'
					AND pm.meta_value != %s AND pm.meta_value != \'\'
					AND (
						p.post_status IN (%s, %s, %s) OR
						( p.post_status = %s AND CAST( ldo.delivered_date AS DATE ) = %s )
					)
					GROUP BY p.post_status', array(
                '-1',
                $driver_assigned_status,
                $out_for_delivery_status,
                $failed_attempt_status,
                $delivered_status,
                $today
            ) ) );
            // db call ok; no-cache ok.
        }
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $counts[$row->post_status] = (int) $row->orders;
            }
        }
        return $counts;
    }

    /**
     * Count delivery-pipeline orders in a status but without a driver assigned.
     *
     * Matches the admin order list filter `lddfw_orders_filter=-2` ("Without drivers")
     * for driver-assigned, out-for-delivery, and failed-attempt statuses.
     *
     * @since 2.3.2
     * @return array Associative array of status => order count.
     */
    public function lddfw_delivery_status_orphan_counts() {
        global $wpdb;
        $driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
        $out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
        $failed_attempt_status = get_option( 'lddfw_failed_attempt_status', '' );
        $counts = array(
            $driver_assigned_status  => 0,
            $out_for_delivery_status => 0,
            $failed_attempt_status   => 0,
        );
        if ( '' === $driver_assigned_status && '' === $out_for_delivery_status && '' === $failed_attempt_status ) {
            return $counts;
        }
        if ( lddfw_is_hpos_enabled() ) {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT o.status AS post_status, COUNT(o.id) AS orders
					FROM ' . $wpdb->prefix . 'wc_orders o
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
					WHERE o.type = \'shop_order\'
					AND o.status IN (%s, %s, %s)
					AND ( om.meta_value IS NULL OR om.meta_value = %s OR om.meta_value = %s )
					GROUP BY o.status', array(
                $driver_assigned_status,
                $out_for_delivery_status,
                $failed_attempt_status,
                '-1',
                ''
            ) ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT p.post_status, COUNT(p.ID) AS orders
					FROM ' . $wpdb->prefix . 'posts p
					LEFT JOIN ' . $wpdb->prefix . 'postmeta pm ON p.ID = pm.post_id AND pm.meta_key = \'lddfw_driverid\'
					WHERE p.post_type = \'shop_order\'
					AND p.post_status IN (%s, %s, %s)
					AND ( pm.meta_value IS NULL OR pm.meta_value = %s OR pm.meta_value = %s )
					GROUP BY p.post_status', array(
                $driver_assigned_status,
                $out_for_delivery_status,
                $failed_attempt_status,
                '-1',
                ''
            ) ) );
            // db call ok; no-cache ok.
        }
        if ( !empty( $rows ) ) {
            foreach ( $rows as $row ) {
                if ( isset( $counts[$row->post_status] ) ) {
                    $counts[$row->post_status] = (int) $row->orders;
                }
            }
        }
        return $counts;
    }

    /**
     * Dashboard claim report query.
     *
     * @since 1.0.0
     * @return html
     */
    public function lddfw_claim_orders_dashboard_report_query() {
        global $wpdb;
        if ( lddfw_is_hpos_enabled() ) {
            // Query for HPOS-enabled environments
            $query = $wpdb->get_results( $wpdb->prepare( 'SELECT o.status AS post_status, COUNT(*) AS orders
					FROM ' . $wpdb->prefix . 'wc_orders o
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om1 ON o.id = om1.order_id AND om1.meta_key = \'lddfw_delivered_date\'
					WHERE o.type = \'shop_order\' AND ( om.meta_value IS NULL OR om.meta_value = \'-1\' OR om.meta_value = \'\' ) AND
					(
						o.status IN (%s, %s, %s) OR
						( o.status = %s AND CAST( om1.meta_value AS DATE ) >= %s AND CAST( om1.meta_value AS DATE ) <= %s )
					)
					GROUP BY o.status', array(
                get_option( 'lddfw_driver_assigned_status', '' ),
                get_option( 'lddfw_out_for_delivery_status', '' ),
                get_option( 'lddfw_failed_attempt_status', '' ),
                get_option( 'lddfw_delivered_status', '' ),
                date_i18n( 'Y-m-d' ),
                date_i18n( 'Y-m-d' )
            ) ) );
        } else {
            // Original query for non-HPOS environments
            $query = $wpdb->get_results( $wpdb->prepare( 'select post_status, count(*) as orders from ' . $wpdb->prefix . 'posts p
				left join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
				left join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
				where post_type=\'shop_order\' and ( pm.meta_value is null or pm.meta_value = \'-1\' or pm.meta_value = \'\' ) and
				(
					post_status in (%s,%s,%s) or
					( post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
				)
				group by post_status', array(
                get_option( 'lddfw_driver_assigned_status', '' ),
                get_option( 'lddfw_out_for_delivery_status', '' ),
                get_option( 'lddfw_failed_attempt_status', '' ),
                get_option( 'lddfw_delivered_status', '' ),
                date_i18n( 'Y-m-d' ),
                date_i18n( 'Y-m-d' )
            ) ) );
            // db call ok; no-cache ok.
        }
        return $query;
    }

    /**
     * Assign to driver count query.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @deprecated 1.7.5
     * @return array
     */
    public function lddfw_assign_to_driver_count_query( $driver_id ) {
        global $wpdb;
        if ( lddfw_is_hpos_enabled() ) {
            // Query for HPOS-enabled environments
            $results = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(*) AS orders
					FROM ' . $wpdb->prefix . 'wc_orders o
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
					WHERE o.type = \'shop_order\' AND o.status = %s
					AND om.meta_value = %s GROUP BY o.status', array(get_option( 'lddfw_driver_assigned_status', '' ), $driver_id) ) );
        } else {
            // Original query for non-HPOS environments
            $results = $wpdb->get_results( $wpdb->prepare( 'select count(*) as orders from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
				where post_type=\'shop_order\' and post_status in (%s)
				and pm.meta_value = %s group by post_status', array(get_option( 'lddfw_driver_assigned_status', '' ), $driver_id) ) );
            // db call ok; no-cache ok.
        }
        return $results;
    }

    /**
     * Orders query.
     *
     * @since 1.0.0
     * @param int    $driver_id driver user id.
     * @param int    $status order status.
     * @param string $screen current screen.
     * @return object
     */
    public function lddfw_orders_query( $driver_id, $status, $screen = null ) {
        global $wpdb;
        $result = '';
        if ( 'delivered' === $screen ) {
            global $lddfw_dates, $lddfw_page;
            $limit = 25;
            if ( $lddfw_page === '' ) {
                $lddfw_page = 0;
            }
            $offset = ( $lddfw_page > 0 ? $limit * $lddfw_page - $limit : 0 );
            if ( '' === $lddfw_dates ) {
                $from_date = date_i18n( 'Y-m-d' );
                $to_date = date_i18n( 'Y-m-d' );
            } else {
                $lddfw_dates_array = explode( ',', $lddfw_dates );
                if ( 1 < count( $lddfw_dates_array ) ) {
                    if ( $lddfw_dates_array[0] === $lddfw_dates_array[1] ) {
                        $from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                        $to_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                    } else {
                        $from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                        $to_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[1] ) );
                    }
                } else {
                    $from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                    $to_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
                }
            }
            if ( lddfw_is_hpos_enabled() ) {
                // Query for HPOS-enabled environments.
                $orders = $wpdb->get_results( $wpdb->prepare( 'SELECT o.ID FROM ' . $wpdb->prefix . 'wc_orders o
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders lo ON o.id = lo.order_id
						WHERE o.type = \'shop_order\'
						AND o.status = %s
						AND lo.driver_id = %d
						AND CAST(lo.delivered_date AS DATE) BETWEEN %s AND %s
						GROUP BY o.id
						ORDER BY lo.delivered_date DESC
						LIMIT %d OFFSET %d', array(
                    $status,
                    $driver_id,
                    $from_date,
                    $to_date,
                    $limit,
                    $offset
                ) ) );
            } else {
                // Original query for non-HPOS environments.
                $orders = $wpdb->get_results( $wpdb->prepare( 'SELECT p.ID FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
					ON p.ID = o.order_id
					WHERE
					p.post_type = \'shop_order\'
					AND p.post_status = %s
					AND driver_id = %d
					AND CAST(delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY p.ID
					ORDER BY delivered_date desc
					LIMIT %d OFFSET %d', array(
                    $status,
                    $driver_id,
                    $from_date,
                    $to_date,
                    $limit,
                    $offset
                ) ) );
            }
            if ( lddfw_is_hpos_enabled() ) {
                // Query for HPOS-enabled environments.
                $orders_counter = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(o.id) as orders FROM ' . $wpdb->prefix . 'wc_orders o 
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders lo ON o.id = lo.order_id
						WHERE o.type = \'shop_order\'
						AND o.status = %s
						AND lo.driver_id = %d
						AND CAST(lo.delivered_date AS DATE) BETWEEN %s AND %s', array(
                    $status,
                    $driver_id,
                    $from_date,
                    $to_date
                ) ) );
            } else {
                // Original query for non-HPOS environments.
                $orders_counter = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(p.ID) as orders FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
					ON p.ID = o.order_id
					WHERE
					p.post_type = \'shop_order\'
					AND p.post_status = %s
					AND driver_id = %d
					AND CAST(delivered_date AS DATE) BETWEEN %s AND %s
					', array(
                    $status,
                    $driver_id,
                    $from_date,
                    $to_date
                ) ) );
            }
            if ( !empty( $orders ) ) {
                $result = array($orders, $orders_counter);
            }
        } else {
            if ( lddfw_is_hpos_enabled() ) {
                // Query for HPOS-enabled environments.
                $query = $wpdb->prepare( 'SELECT o.ID FROM ' . $wpdb->prefix . 'wc_orders o 
						INNER JOIN ' . $wpdb->prefix . 'lddfw_orders lo ON o.id = lo.order_id
						WHERE o.type = \'shop_order\'
						AND o.status = %s
						AND lo.driver_id = %d
						GROUP BY o.id
						ORDER BY lo.order_sort, lo.order_shipping_city', array($status, $driver_id) );
            } else {
                // Original query for non-HPOS environments.
                $query = $wpdb->prepare( 'SELECT p.ID FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
					ON p.ID = o.order_id
					WHERE
					p.post_type = \'shop_order\'
					AND p.post_status = %s
					AND driver_id = %d
					GROUP BY p.ID
					ORDER BY order_sort,order_shipping_city
					', array($status, $driver_id) );
            }
            // Execute the query.
            $result = $wpdb->get_results( $query );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is built via $wpdb->prepare(); post-filter value is intentionally trusted (documented extension point).
        }
        return $result;
    }

    /**
     * Out for delivery orders counter.
     *
     * @since 1.0.0
     * @deprecated 1.7.5
     * @param int $driver_id driver user id.
     * @return object
     */
    public function lddfw_out_for_delivery_orders_counter( $driver_id ) {
        $wc_query = $this->lddfw_orders_query( $driver_id, get_option( 'lddfw_out_for_delivery_status', '' ) );
        return $wc_query->found_posts;
    }

    /**
     * Out for delivery orders.
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_out_for_delivery( $driver_id ) {
        $html = '';
        $store = new LDDFW_Store();
        $counter = 0;
        $results = $this->lddfw_orders_query( $driver_id, get_option( 'lddfw_out_for_delivery_status', '' ) );
        if ( !empty( $results ) ) {
            $html .= '<div id="lddfw_orders_table" sort_url="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">';
            $lddfw_order = new LDDFW_Order();
            foreach ( $results as $result ) {
                $orderid = $result->ID;
                $order = wc_get_order( $orderid );
                $seller_id = $store->lddfw_order_seller( $order );
                // Get and format shipping address.
                $shipping_array = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
                $shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );
                // Set address by coordinates.
                $coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
                if ( '' !== $coordinates ) {
                    $shipping_map_address = $coordinates;
                }
                $shipping_address = lddfw_format_address( 'address', $shipping_array );
                // Distance from origin.
                $distance = '';
                $origin_distance = $order->get_meta( '_lddfw_origin_distance' );
                if ( !empty( $origin_distance ) ) {
                    if ( isset( $origin_distance['distance_text'] ) ) {
                        $distance = $origin_distance['distance_text'];
                    }
                }
                ++$counter;
                $html .= '
				<div class="lddfw_box">
					<div class="row">
						<div class="col-12">
							<span class="lddfw_index lddfw_counter">' . $counter . '</span>
							<input style="display:none" orderid="' . esc_attr( $orderid ) . '" type="checkbox" value="' . esc_attr( str_replace( "'", '', $shipping_map_address ) ) . '" class="lddfw_address_chk">';
                $html .= '<a class="btn lddfw_order_view btn-primary btn-sm lddfw_loader" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) ) . '">' . esc_html( __( 'Order details', 'lddfw' ) ) . '</a>';
                $html .= '<div class="lddfw_order_number"><b>' . esc_html( __( 'Order #', 'lddfw' ) ) . $order->get_order_number() . '</b></div>';
                $html .= '<a class="lddfw_order_address lddfw_loader" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) ) . '">' . $shipping_address . '</a>';
                if ( '' !== $distance ) {
                    $html .= '<a class="lddfw_order_distance lddfw_loader" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) ) . '">' . esc_html( __( 'Distance', 'lddfw' ) ) . ': ' . $distance . '</a>';
                }
                // Print coordinates.
                if ( '' !== $coordinates ) {
                    $html .= '<a class="lddfw_order_address lddfw_order_coordinates lddfw_loader" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) ) . '">' . esc_html( $coordinates ) . '</a>';
                }
                $html .= '<div class="lddfw_handle_column"  style="display:none"><button  class="lddfw_sort-up btn btn-secondary "><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-up" class="svg-inline--fa fa-chevron-up fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path></svg></button><button class="btn btn-secondary lddfw_sort-down">
							<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-down" class="svg-inline--fa fa-chevron-down fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544-9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z"></path></svg></button></div>
						</div>
					</div>
				</div>';
            }
            // end while
            $html .= '</div>';
            $origin_html = '';
            $html = $origin_html . $html;
        } else {
            $html .= '<div class="lddfw_box min lddfw_no_orders"><p>' . esc_html( __( 'There are no orders.', 'lddfw' ) ) . '</p></div>';
        }
        return $html;
    }

    /**
     * Failed delivery
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_failed_delivery( $driver_id ) {
        $date_format = lddfw_date_format( 'date' );
        $time_format = lddfw_date_format( 'time' );
        $html = '<div id=\'lddfw_orders_table\' >';
        $counter = 0;
        $results = $this->lddfw_orders_query( $driver_id, get_option( 'lddfw_failed_attempt_status', '' ) );
        if ( !empty( $results ) ) {
            $lddfw_order = new LDDFW_Order();
            foreach ( $results as $result ) {
                $orderid = $result->ID;
                $order = wc_get_order( $orderid );
                // Get and fromat shipping address.
                $shipping_array = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
                $shipping_address = lddfw_format_address( 'address', $shipping_array );
                $delivered_date = $order->get_meta( 'lddfw_delivered_date' );
                $failed_date = $order->get_meta( 'lddfw_failed_attempt_date' );
                // Distance from origin.
                $distance = '';
                $origin_distance = $order->get_meta( '_lddfw_origin_distance' );
                if ( !empty( $origin_distance ) ) {
                    if ( isset( $origin_distance['distance_text'] ) ) {
                        $distance = $origin_distance['distance_text'];
                    }
                }
                ++$counter;
                $html .= '
				<div class="lddfw_box">
					<div class="row">
						<div class="col-12">
							<span class="lddfw_counter">' . $counter . '</span>';
                $html .= '<a class="btn lddfw_order_view btn-primary btn-sm lddfw_loader" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) ) . '">' . esc_html( __( 'Order details', 'lddfw' ) ) . '</a>';
                $html .= '<div class="lddfw_order_number"><b>' . esc_html( __( 'Order #', 'lddfw' ) ) . $order->get_order_number() . '</b></div>';
                $html .= '<a class="lddfw_order_address line lddfw_loader" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . '">' . $shipping_address . '</a>';
                if ( '' !== $distance ) {
                    $html .= '<a class=\'lddfw_order_distance lddfw_loader lddfw_line\' href=\'' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . '\'>' . esc_html( __( 'Distance', 'lddfw' ) ) . ': ' . $distance . '</a>';
                }
                if ( '' !== $delivered_date ) {
                    $html .= '<a class=\'lddfw_order_failed_date lddfw_loader lddfw_line\' href=\'' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . '\'>' . esc_html( __( 'Failed Date', 'lddfw' ) ) . ': ' . gmdate( $date_format . ' ' . $time_format, strtotime( $failed_date ) ) . '</a>';
                }
                $html .= '<input style="display:none" orderid="' . $orderid . '" type="checkbox" value="' . $orderid . '" class="lddfw_address_chk">
						</div>
					</div>
				</div>';
            }
        } else {
            $html .= '<div class="lddfw_box min lddfw_no_orders"><p>' . esc_html( __( 'There are no orders.', 'lddfw' ) ) . '</p></div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Assign to driver
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_assign_to_driver( $driver_id ) {
        $html = '';
        $counter = 0;
        $results = $this->lddfw_orders_query( $driver_id, get_option( 'lddfw_driver_assigned_status', '' ) );
        if ( !empty( $results ) ) {
            // Map view support - lazy geocode cache. Same pattern as the claim
            // screen: only resolve coordinates when the map feature is likely
            // in use (premium + Google key + admin toggle on) so list-only
            // installs never incur a Geocoding bill. The "Preview route"
            // feature already requires the API key, so most assigned-orders
            // installs already have it - this reuses the same `_lddfw_address_geocode`
            // meta cache that LDDFW_Driver / LDDFW_Tracking warm elsewhere.
            $lddfw_assign_geocode_enabled = lddfw_fs()->is__premium_only() && lddfw_fs()->is_plan( 'premium', true ) && '' !== get_option( 'lddfw_google_api_key', '' ) && '0' !== get_option( 'lddfw_assign_show_map', '1' );
            $lddfw_assign_geocode_budget = 20;
            $lddfw_assign_route = ( $lddfw_assign_geocode_enabled ? new LDDFW_Route() : null );
            $lddfw_order = new LDDFW_Order();
            foreach ( $results as $result ) {
                $orderid = $result->ID;
                $order = wc_get_order( $orderid );
                // Get and fromat shipping address.
                $shipping_array = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
                $shipping_address = lddfw_format_address( 'address', $shipping_array );
                // Resolve/warm geocode for the map view. Cheap when meta exists.
                $assign_lat = '';
                $assign_lng = '';
                if ( $lddfw_assign_geocode_enabled ) {
                    $geocode_array = $order->get_meta( '_lddfw_address_geocode' );
                    if ( empty( $geocode_array ) && $lddfw_assign_geocode_budget > 0 ) {
                        --$lddfw_assign_geocode_budget;
                        $lddfw_assign_route->set_order_geocode( $orderid );
                        $fresh_order = wc_get_order( $orderid );
                        if ( $fresh_order ) {
                            $geocode_array = $fresh_order->get_meta( '_lddfw_address_geocode' );
                        }
                    }
                    if ( !empty( $geocode_array ) && is_array( $geocode_array ) && isset( $geocode_array[0] ) && 'ZERO_RESULTS' !== $geocode_array[0] && !empty( $geocode_array[1] ) ) {
                        $coord_parts = explode( ',', $geocode_array[1] );
                        if ( 2 === count( $coord_parts ) ) {
                            $lat_candidate = trim( $coord_parts[0] );
                            $lng_candidate = trim( $coord_parts[1] );
                            if ( is_numeric( $lat_candidate ) && is_numeric( $lng_candidate ) ) {
                                $assign_lat = $lat_candidate;
                                $assign_lng = $lng_candidate;
                            }
                        }
                    }
                }
                ++$counter;
                $assign_order_label = '#' . $order->get_order_number();
                $html .= '
					<div class="lddfw_box lddfw_multi_checkbox"' . ' data-orderid="' . esc_attr( $orderid ) . '"' . ' data-label="' . esc_attr( $assign_order_label ) . '"' . ' data-address="' . esc_attr( wp_strip_all_tags( $shipping_address ) ) . '"' . (( '' !== $assign_lat ? ' data-lat="' . esc_attr( $assign_lat ) . '"' : '' )) . (( '' !== $assign_lng ? ' data-lng="' . esc_attr( $assign_lng ) . '"' : '' )) . '>
						<div class="row">
							<div class="col-12">';
                $order_number_html = '<div class="lddfw_order_number"><b>' . esc_html( __( 'Order #', 'lddfw' ) ) . $order->get_order_number() . '</b></div>';
                $order_button = '';
                $html .= $order_button;
                $html .= $order_number_html;
                $html .= '<div class="lddfw_wrap">
								<div class="custom-control custom-checkbox mr-sm-2 lddfw_order_checkbox">
									<input value="' . $orderid . '" type="checkbox" class="custom-control-input" name="lddfw_order_id" id="lddfw_chk_order_id_' . $orderid . '">
									<label class="custom-control-label" for="lddfw_chk_order_id_' . $orderid . '"></label>
								</div>
								<div class="lddfw_order">
									<div class="lddfw_order_address">' . $shipping_address . '</div>';
                $html .= '</div>
							</div>';
                $html .= '
							</div>
						</div>
					</div>';
            }
        } else {
            $html .= '<div class="lddfw_box min lddfw_no_orders"><p>' . esc_html( __( 'There are no orders.', 'lddfw' ) ) . '</p></div>';
        }
        return $html;
    }

    /**
     * Delivered orders
     *
     * @since 1.0.0
     * @param int $driver_id driver user id.
     * @return html
     */
    public function lddfw_delivered( $driver_id ) {
        $html = '<div id=\'lddfw_orders_table\' >';
        $date_format = lddfw_date_format( 'date' );
        $time_format = lddfw_date_format( 'time' );
        $counter = 0;
        $array = $this->lddfw_orders_query( $driver_id, get_option( 'lddfw_delivered_status', '' ), 'delivered' );
        if ( !empty( $array ) ) {
            $results = $array[0];
            // Pagination.
            $max_per_page = 25;
            global $lddfw_page, $lddfw_dates;
            $base = lddfw_drivers_page_url( 'lddfw_screen=delivered&lddfw_dates=' . $lddfw_dates ) . '&lddfw_page=%#%';
            $pagination = paginate_links( array(
                'base'         => $base,
                'total'        => ceil( $array[1][0]->orders / $max_per_page ),
                'current'      => $lddfw_page,
                'format'       => '&lddfw_page=%#%',
                'show_all'     => false,
                'type'         => 'array',
                'end_size'     => 2,
                'mid_size'     => 0,
                'prev_next'    => true,
                'prev_text'    => sprintf( '<i></i> %1$s', __( '<<', 'lddfw' ) ),
                'next_text'    => sprintf( '%1$s <i></i>', __( '>>', 'lddfw' ) ),
                'add_args'     => false,
                'add_fragment' => '',
            ) );
            if ( !empty( $pagination ) ) {
                $html .= '<div class="pagination text-sm-center"><nav aria-label="Page navigation" style="width:100%"><ul class="pagination justify-content-center">';
                foreach ( $pagination as $page ) {
                    $html .= "<li class='page-item ";
                    if ( strpos( $page, 'current' ) !== false ) {
                        $html .= ' active';
                    }
                    $html .= "'> " . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
                }
                $html .= '</nav></div>';
            }
            // Results.
            $lddfw_order = new LDDFW_Order();
            foreach ( $results as $result ) {
                $orderid = $result->ID;
                $order = wc_get_order( $orderid );
                // Get and fromat shipping address.
                $shipping_array = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
                $shipping_address = lddfw_format_address( 'address', $shipping_array );
                $delivered_date = $order->get_meta( 'lddfw_delivered_date' );
                // Distance from origin.
                $distance = '';
                $origin_distance = $order->get_meta( '_lddfw_origin_distance' );
                if ( !empty( $origin_distance ) ) {
                    if ( isset( $origin_distance['distance_text'] ) ) {
                        $distance = $origin_distance['distance_text'];
                    }
                }
                ++$counter;
                $html .= '
				<div class="lddfw_box">
					<div class="row">
						<div class="col-12">
							<span class="lddfw_counter">' . $counter . '</span>';
                $html .= '<a class="btn lddfw_order_view btn-primary btn-sm lddfw_loader" href="' . esc_url( lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) ) . '">' . esc_html( __( 'Order details', 'lddfw' ) ) . '</a>';
                $html .= '<div class="lddfw_order_number"><b>' . esc_html( __( 'Order #', 'lddfw' ) ) . $order->get_order_number() . '</b></div>';
                $html .= '<a class="lddfw_order_address lddfw_loader lddfw_line" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . '">' . $shipping_address . '</a>';
                if ( '' !== $distance ) {
                    $html .= '<a class="lddfw_order_distance lddfw_loader lddfw_line" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . '">' . esc_html( __( 'Distance', 'lddfw' ) ) . ': ' . $distance . '</a>';
                }
                if ( '' !== $delivered_date ) {
                    $html .= '<a class="lddfw_order_delivered_date lddfw_loader lddfw_line" href="' . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . '">' . esc_html( __( 'Delivered Date', 'lddfw' ) ) . ': ' . gmdate( $date_format . ' ' . $time_format, strtotime( $delivered_date ) ) . '</a>';
                }
                $html .= '<input style="display:none" orderid="' . $orderid . '" type="checkbox" value="' . $orderid . '" class="address_chk">
						</div>
					</div>
				</div>';
            }
            // end while
            if ( !empty( $pagination ) ) {
                $html .= '<div class="pagination text-sm-center"><nav aria-label="Page navigation" style="width:100%"><ul class="pagination justify-content-center">';
                foreach ( $pagination as $page ) {
                    $html .= "<li class='page-item ";
                    if ( strpos( $page, 'current' ) !== false ) {
                        $html .= ' active';
                    }
                    $html .= "'> " . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
                }
                $html .= '</nav></div>';
            }
        } else {
            $html .= '<div class="lddfw_box min lddfw_no_orders"><p>' . esc_html( __( 'There are no orders.', 'lddfw' ) ) . '</p></div>';
        }
        $html .= '</div>';
        return $html;
    }

}
