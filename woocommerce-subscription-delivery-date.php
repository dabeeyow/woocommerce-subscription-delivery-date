<?php
/*
Plugin Name: WooCommerce Subscription Delivery Date
Description: Adds a delivery date field to WooCommerce subscription products.
Version: 1.0
Author: John Dave Canilao
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the main class file.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcs-delivery-date.php';

// Initialize the plugin.
add_action( 'plugins_loaded', ['WCS_Delivery_Date', 'init'] );

add_action( 'wp_enqueue_scripts', 'enqueue_cart_delivery_date_script' );
function enqueue_cart_delivery_date_script() {
    if ( is_product() || is_cart() ) {
        wp_enqueue_script( 'wcs-delivery-date-frontend', plugin_dir_url(__FILE__) . 'js/frontend.js', array('jquery'), null, true );

        // Pass cart items data to JavaScript
        $cart_items = array();
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $delivery_date = get_post_meta( $product_id, '_delivery_date', true );
            $wcs = new WCS_Delivery_Date();
            $dates = $wcs->calculate_next_dates( $delivery_date );

            $cart_items[$cart_item_key] = array(
                'product_id' => $product_id,
                'delivery_dates' => $dates,
                'selected_date' => $cart_item['delivery_date'],
            );
        }
        wp_localize_script( 'wcs-delivery-date-frontend', 'cartDeliveryDates', array(
            'cart_items' => $cart_items,
            'ajax_url' => admin_url('admin-ajax.php'),
        ) );
    }
}

// AJAX handler to update the delivery date in the cart
add_action( 'wp_ajax_update_delivery_date', 'update_delivery_date' );
add_action( 'wp_ajax_nopriv_update_delivery_date', 'update_delivery_date' );
function update_delivery_date() {
    if ( isset( $_POST['cart_item_key'] ) && isset( $_POST['delivery_date'] ) ) {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        $delivery_date = sanitize_text_field($_POST['delivery_date']);

        if ( isset( WC()->cart->cart_contents[$cart_item_key] ) ) {
            WC()->cart->cart_contents[$cart_item_key]['delivery_date'] = $delivery_date;
            WC()->cart->set_session();
            
            wp_send_json_success( array( 'message' => 'Delivery date updated.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Cart item not found.' ) );
        }
    } else {
        wp_send_json_error( array( 'message' => 'Invalid request.' ) );
    }
}

// Ensure WooCommerce cart fragments are enabled
add_action( 'wp_enqueue_scripts', 'ensure_woocommerce_cart_fragments' );
function ensure_woocommerce_cart_fragments() {
    if ( is_cart() ) {
        wp_enqueue_script( 'wc-cart-fragments' );
    }
}