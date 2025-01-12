<?php

/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Admin panel metaboxes
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_MetaBoxes {
    /**
     * Is meta boxes saved once?
     *
     * @var boolean
     */
    private static $saved_meta_boxes = false;

    /**
     * Control flag for allowing meta box save.
     *
     * @var boolean
     */
    private static $allow_save_meta = true;

    // Default to true to allow saving unless specified otherwise.
    /**
     * Sets the flag to allow or disallow saving meta boxes.
     *
     * @param boolean $allow Whether to allow saving.
     */
    public static function set_allow_save_meta( $allow ) {
        self::$allow_save_meta = $allow;
    }

    /**
     * Registers a meta box for displaying delivery driver information on WooCommerce Order admin pages.
     * The function conditionally sets the target screen based on whether the high-performance order screen (HPOS) is enabled.
     * If HPOS is enabled, it targets the WooCommerce page screen ID for 'shop-order'; otherwise, it defaults to 'shop_order'.
     * This meta box, titled 'Delivery Driver', is added to the side panel of the order edit screen with default priority.
     */
    public function add_metaboxes() {
        // Determine the correct screen based on whether the high-performance order screen is enabled.
        // This utilizes a conditional check through the lddfw_is_hpos_enabled() function.
        $screen = ( lddfw_is_hpos_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order' );
        add_meta_box(
            'lddfw_metaboxes',
            __( 'Delivery Driver', 'lddfw' ),
            array($this, 'create_metaboxes'),
            $screen,
            'side',
            'default'
        );
    }

    /**
     * Building the metabox.
     */
    public function create_metaboxes() {
        global $post, $theorder;
        // Determine if we're working with an order object or a post object.
        $order = ( lddfw_is_hpos_enabled() && $theorder instanceof WC_Order ? $theorder : wc_get_order( $post->ID ) );
        echo '<input type="hidden" name="lddfw_metaboxes_key" id="lddfw_metaboxes_key" value="' . esc_attr( wp_create_nonce( 'lddfw-save-order' ) ) . '" />';
        $lddfw_driverid = $order->get_meta( 'lddfw_driverid' );
        echo '<div class="lddfw-driver-box">
	<label>' . esc_html( __( 'Driver', 'lddfw' ) ) . '</label>';
        $drivers = LDDFW_Driver::lddfw_get_drivers();
        echo esc_html( LDDFW_Driver::lddfw_driver_drivers_selectbox(
            $drivers,
            $lddfw_driverid,
            $order->get_id(),
            ''
        ) );
        /* driver note */
        $lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
        if ( '' !== $lddfw_driver_note ) {
            echo '<p><label>' . esc_html( __( 'Driver Note', 'lddfw' ) ) . '</label><br>';
            echo $lddfw_driver_note;
            echo '</p>';
        }
        $lddfw_delivered_date = $order->get_meta( 'lddfw_delivered_date' );
        if ( '' !== $lddfw_delivered_date ) {
            echo '<p><label>' . esc_html( __( 'Delivered Date', 'lddfw' ) ) . '</label><br>';
            echo $lddfw_delivered_date;
            echo '</p>';
        }
        $lddfw_failed_attempt_date = $order->get_meta( 'lddfw_failed_attempt_date' );
        if ( '' !== $lddfw_failed_attempt_date ) {
            echo '<p><label>' . esc_html( __( 'Failed Attempt Date', 'lddfw' ) ) . '</label><br>';
            echo $lddfw_failed_attempt_date;
            echo '</p>';
        }
        echo '</div> ';
    }

    /**
     * Save the Metabox Data
     *
     * @param int    $post_id post number.
     * @param object $post post object.
     */
    public function save_metaboxes( $post_id, $post ) {
        if ( !self::$allow_save_meta || self::$saved_meta_boxes ) {
            return;
        }
        self::$saved_meta_boxes = true;
        $post_id = absint( $post_id );
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return;
        }
        // Dont' save meta boxes for revisions or autosaves.
        if ( is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }
        // Check the nonce.
        if ( !isset( $_POST['lddfw_metaboxes_key'] ) || !wp_verify_nonce( $_POST['lddfw_metaboxes_key'], 'lddfw-save-order' ) ) {
            return;
        }
        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
        if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
            return;
        }
        // Check user has permission to edit.
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $order = wc_get_order( $post_id );
        $driver = new LDDFW_Driver();
        if ( isset( $_POST['lddfw_driverid'] ) ) {
            $lddfw_driverid = sanitize_text_field( wp_unslash( $_POST['lddfw_driverid'] ) );
            $lddfw_driver_order_meta['lddfw_driverid'] = $lddfw_driverid;
        }
        foreach ( $lddfw_driver_order_meta as $key => $value ) {
            /**
             * Cycle through the $thccbd_meta array!
             */
            if ( 'revision' === $post->post_type ) {
                /**
                 * Don't store custom data twice
                 */
                return;
            }
            $value = implode( ',', (array) $value );
            if ( 'shop_order' === OrderUtil::get_order_type( $post_id ) ) {
                $driver->assign_delivery_driver( $post_id, $value, 'store' );
            }
            if ( !$value ) {
                /**
                 * Delete if blank
                 */
                $order->delete_meta_data( $key );
                lddfw_update_sync_order( $post_id, $key, '0' );
            }
        }
        $order->save();
        // Remove the flag after saving is done.
        self::$saved_meta_boxes = false;
    }

}
