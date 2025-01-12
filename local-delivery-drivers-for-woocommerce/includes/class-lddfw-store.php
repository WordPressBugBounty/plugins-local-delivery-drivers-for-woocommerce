<?php

/**
 * Website store class
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */
/**
 * Website store class.
 *
 * All store functions.
 *
 * @link  http://www.powerfulwp.com
 * @since      1.0.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Store {
    /**
     * Function that return driver seller id.
     *
     * @param int $driver_id driver user id.
     * @since 1.6.0
     * @return string
     */
    public function lddfw_get_driver_seller( $driver_id ) {
        $seller_id = '';
        return $seller_id;
    }

    /**
     * Function that return order seller id.
     *
     * @since 1.6.0
     * @param object $order order.
     * @return string
     */
    public function lddfw_order_seller( $order, $all_sellers = false ) {
        $result = '';
        $array = array();
        global $wpdb;
        $order_id = $order->get_id();
        switch ( LDDFW_MULTIVENDOR ) {
            case 'dokan':
                if ( $all_sellers && $order->get_meta( 'has_sub_order' ) ) {
                    $sub_orders = dokan_get_suborder_ids_by( $order_id );
                    if ( !empty( $sub_orders ) ) {
                        foreach ( $sub_orders as $sub_order ) {
                            $child_order = wc_get_order( $sub_order );
                            $vendor_id = $child_order->get_meta( '_dokan_vendor_id' );
                            if ( !in_array( $vendor_id, $array ) && '' !== $vendor_id ) {
                                $array[$vendor_id] = $vendor_id;
                            }
                        }
                        $result = $array;
                    }
                } else {
                    // Return seller id.
                    $result = $order->get_meta( '_dokan_vendor_id' );
                }
                break;
            case 'wcmp':
                if ( $all_sellers && $order->get_meta( 'has_wcmp_sub_order' ) ) {
                    $sub_orders = get_wcmp_suborders( $order_id, false, false );
                    if ( $sub_orders ) {
                        foreach ( $sub_orders as $sub_order ) {
                            $child_order = wc_get_order( $sub_order );
                            $vendor_id = $child_order->get_meta( '_vendor_id' );
                            if ( !in_array( $vendor_id, $array ) && '' !== $vendor_id ) {
                                $array[$vendor_id] = $vendor_id;
                            }
                        }
                        $result = $array;
                    }
                } else {
                    // Return seller id.
                    $result = $order->get_meta( '_vendor_id' );
                }
                break;
            case 'wcfm':
                $sellers = $wpdb->get_results( $wpdb->prepare( 'select vendor_id from ' . $wpdb->prefix . 'wcfm_marketplace_orders where order_id=%s', array($order_id) ) );
                if ( !empty( $sellers ) ) {
                    if ( $all_sellers ) {
                        foreach ( $sellers as $seller ) {
                            $seller_id = $seller->vendor_id;
                            if ( !in_array( $seller_id, $array ) ) {
                                $array[$seller_id] = $seller_id;
                            }
                        }
                        // Return sellers array.
                        $result = $array;
                    } else {
                        // Return first seller id.
                        $result = $sellers[0]->vendor_id;
                    }
                }
                break;
            default:
                $result = '';
                break;
        }
        return $result;
    }

    /**
     * Pickup option.
     *
     * @param object $order order object.
     * @return statement
     */
    public function get_pickup_type( $order ) {
        /**
         * Pickup option types:
         * store - store/vendor pickup location.
         * customer - customer pickup location.
         * post - saved pickup location.
         */
        $result = 'store';
        // Pickup Filter.
        if ( has_filter( 'lddfw_pickup_type' ) ) {
            $result = apply_filters( 'lddfw_pickup_type', $result, $order );
        }
        return $result;
    }

    /**
     * Pickup phone.
     *
     * @param object $order order object.
     * @param object $seller_id seller number.
     * @return statement
     */
    public function get_pickup_phone( $order, $seller_id ) {
        $phone = $this->lddfw_store_phone( $order, $seller_id );
        // Pickup phone filter.
        if ( has_filter( 'lddfw_pickup_phone' ) ) {
            $phone = apply_filters( 'lddfw_pickup_phone', $phone, $order );
        }
        return $phone;
    }

    /**
     * Pickup address.
     *
     * @since 1.0.0
     * @param string $format address format.
     * @param object $order order object.
     * @param int    $seller_id seller id.
     * @return string
     */
    public function lddfw_pickup_address( $format, $order, $seller_id ) {
        $address = $this->lddfw_store_address( $format );
        return $address;
    }

    /**
     * Store phone.
     *
     * @since 1.6.0
     * @param object $order order object.
     * @param int    $seller_id seller id.
     * @return string
     */
    public function lddfw_store_phone( $order, $seller_id ) {
        $store_phone = get_option( 'lddfw_dispatch_phone_number', '' );
        return $store_phone;
    }

    /**
     * Store address.
     *
     * @since 1.0.0
     * @param string $format address format.
     * @return string
     */
    public function lddfw_store_address( $format ) {
        // main store address.
        $store_address = get_option( 'woocommerce_store_address', '' );
        $store_address_2 = get_option( 'woocommerce_store_address_2', '' );
        $store_city = get_option( 'woocommerce_store_city', '' );
        $store_postcode = get_option( 'woocommerce_store_postcode', '' );
        $store_raw_country = get_option( 'woocommerce_default_country', '' );
        $split_country = explode( ':', $store_raw_country );
        if ( false === strpos( $store_raw_country, ':' ) ) {
            $store_country = $split_country[0];
            $store_state = '';
        } else {
            $store_country = $split_country[0];
            $store_state = $split_country[1];
        }
        if ( '' !== $store_country ) {
            $store_country = WC()->countries->countries[$store_country];
        }
        $array = array(
            'street_1' => $store_address,
            'street_2' => $store_address_2,
            'city'     => $store_city,
            'zip'      => $store_postcode,
            'country'  => $store_country,
            'state'    => $store_state,
        );
        return lddfw_format_address( $format, $array );
    }

}
