<?php
class WCS_Delivery_Date {
    
    public static function init() {
        add_action( 'woocommerce_product_options_general_product_data', [__CLASS__, 'add_delivery_date_field'] );
        add_action( 'woocommerce_process_product_meta', [__CLASS__, 'save_delivery_date_field'] );
        add_action( 'woocommerce_before_add_to_cart_button', [__CLASS__, 'display_delivery_dates'], 25 );
        add_filter( 'woocommerce_get_item_data', [__CLASS__, 'display_delivery_date_in_cart'], 10, 2 );
        add_filter( 'woocommerce_add_cart_item_data', [__CLASS__, 'add_delivery_date_to_cart_item'] );
        add_action( 'woocommerce_cart_item_updated', [__CLASS__, 'update_delivery_date_in_cart'], 10, 2 );
        add_action( 'woocommerce_update_cart_action_cart_updated', [__CLASS__, 'update_delivery_date_in_cart'] );
    }

    public static function add_delivery_date_field() {
        echo '<div class="options_group">';
        woocommerce_wp_text_input([
            'id' => '_delivery_date',
            'label' => __( 'Delivery Date', 'woocommerce' ),
            'placeholder' => __( 'e.g., 3rd day of every 2 weeks', 'woocommerce' ),
            'desc_tip' => 'true',
            'description' => __( 'Enter the recurring delivery date.', 'woocommerce' )
        ]);
        echo '</div>';
    }

    public static function save_delivery_date_field( $post_id ) {
        $delivery_date = sanitize_text_field( $_POST['_delivery_date'] );
        update_post_meta( $post_id, '_delivery_date', $delivery_date );
    }

    public static function display_delivery_dates() {
        global $product;

        if ( class_exists( 'WC_Subscriptions_Product' ) ) {
            if ( WC_Subscriptions_Product::is_subscription( $product->get_id() ) ) {
                $delivery_date = get_post_meta($product->get_id(), '_delivery_date', true);
                if ( $delivery_date ) {
                    // Calculate and display the next three recurring dates.
                    $dates = self::calculate_next_dates($delivery_date);
                    if ( !empty( $dates ) ) {
                        echo '<div class="delivery-dates">';
                        echo '<label for="delivery_date">' . __('Select Delivery Date:', 'woocommerce') . '</label>';
                        echo '<select id="delivery_date" name="delivery_date">';
                        foreach ( $dates as $date ) {
                            echo '<option value="' . esc_attr( $date ) . '">' . esc_html( $date ) . '</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                    }
                }
            }
        }
    }

    public static function calculate_next_dates( $delivery_date ) {
        $dates = [];
        $current_date = new DateTime();
        $interval = null;
    
        if ( preg_match( '/^(\d+)(st|nd|rd|th) day of every (\d+) (week|weeks|month|months)$/', $delivery_date, $matches ) ) {
            $day = (int) $matches[1];
            $unit = (int) $matches[3];
            $period = $matches[4];
    
            if ( $period === 'week' || $period === 'weeks' ) {
                // Calculate for weekly period
                $interval = new DateInterval( 'P' . $unit . 'W' );
                while ( count($dates) < 3 ) {
                    // Find the next occurrence of the specified day of the week
                    $next_date = clone $current_date;
                    $next_date->modify( "next " . self::get_weekday_by_day_number($day) );
                    if ( $next_date < $current_date ) {
                        $next_date->add( $interval );
                    }
                    $dates[] = $next_date->format( 'Y-m-d' );
                    $current_date = clone $next_date;
                    $current_date->add($interval);
                }
            } elseif ( $period === 'month' || $period === 'months' ) {
                // Calculate for monthly period
                $interval = new DateInterval( 'P' . $unit . 'M' );
                while ( count( $dates ) < 3 ) {
                    $next_date = clone $current_date;
                    $next_date->setDate( $next_date->format('Y'), $next_date->format('m'), $day );
    
                    if ($next_date < $current_date) {
                        $next_date->add($interval);
                    }
    
                    $dates[] = $next_date->format('Y-m-d');
                    $current_date = clone $next_date;
                    $current_date->add($interval);
                }
            }
        } elseif ( preg_match('/^(\d+)(st|nd|rd|th) day of every month$/', $delivery_date, $matches ) ) {
            // Handle the "1st day of every month" case specifically
            $day = (int) $matches[1];
            $interval = new DateInterval( 'P1M' );
            while (count($dates) < 3) {
                $next_date = clone $current_date;
                $next_date->setDate( $next_date->format('Y'), $next_date->format('m'), $day );
    
                if ($next_date < $current_date) {
                    $next_date->add( $interval );
                }
    
                $dates[] = $next_date->format( 'Y-m-d' );
                $current_date = clone $next_date;
                $current_date->add( $interval );
            }
        }
    
        return $dates;
    }
    
    private static function get_weekday_by_day_number( $day_number ) {
        $days = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
        return $days[ ( $day_number - 1 ) % 7 ];
    }
    
    public static function add_delivery_date_to_cart_item( $cart_item_data ) {
        if ( isset( $_POST['delivery_date'] ) ) {
            $cart_item_data['delivery_date'] = sanitize_text_field( $_POST['delivery_date'] );
        }
        return $cart_item_data;
    }

    public static function display_delivery_date_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['delivery_date'] ) ) {
            $item_data[] = [
                'name' => __( 'Delivery Date', 'woocommerce' ),
                'value' => wc_clean( $cart_item['delivery_date'] )
            ];
        }
        return $item_data;
    }

    public static function update_delivery_date_in_cart( $cart_item_data, $cart_item_key ) {
        if ( isset( $_POST['cart'][$cart_item_key]['delivery_date'] ) ) {
            $cart_item_data['delivery_date'] = sanitize_text_field( $_POST['cart'][$cart_item_key]['delivery_date'] );
        }
        return $cart_item_data;
    }
}