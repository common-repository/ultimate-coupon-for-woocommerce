<?php
namespace UCFW\Front;

class Additionals
{
    public static $instance;

    /**
     * Singleton Pattern
     *
     * @return object
     */
    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @return void
     */
    private function __construct()
    {
        add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'applyMaximumDiscountAmount' ), 20, 6 );
    }

    public function applyMaximumDiscountAmount( $discount, $discountingAmount, $cartItem, $single, $coupon )
    {
        $couponType = $coupon->get_discount_type();

        if ( 'percent' !== $couponType )
            return $discount;

        $maxDiscount = get_post_meta( $coupon->get_id(), '_ucfw_max_discount', true );

        if ( !is_numeric($maxDiscount) ):
            return $discount;
        endif;

        if ( !is_null($cartItem) && WC()->cart->subtotal_ex_tax )
        {
            $cartItemQuantity = is_null( $cartItem ) ? 1 : $cartItem['quantity'];
            
            if ( wc_prices_include_tax() )
                $discountPercent = ( wc_get_price_including_tax( $cartItem['data'] ) * $cartItemQuantity ) / WC()->cart->subtotal;
            else
                $discountPercent = ( wc_get_price_excluding_tax( $cartItem['data'] ) * $cartItemQuantity ) / WC()->cart->subtotal_ex_tax;            
                
            $discountPercent = round ( $discountPercent, 4 );
            $new             = round( ( $maxDiscount * $discountPercent ), wc_get_price_decimals() );
            $discount        = min( $new, $discount );
        }
        
        $discIncrease = WC()->session->get( 'ucfw_maxdisc_incr', 0 ) + $discount;
        $sessQuantity = WC()->session->get( 'ucfw_maxdisc_items', 0 ) + 1;

        WC()->session->set( 'ucfw_maxdisc_items', $sessQuantity );
        WC()->session->set( 'ucfw_maxdisc_incr',  $discIncrease );

        $cartItems        = WC()->cart->get_cart_contents();
        $cartItemQuantity = count($cartItems);

        if ( $sessQuantity == $cartItemQuantity )
        {
            if ( $discIncrease > $maxDiscount )
                $discount = $discount - ($discIncrease - $maxDiscount);
            else
                $discount = $discount + ($maxDiscount - $discIncrease);

            WC()->session->set( 'ucfw_maxdisc_items', 0 );
            WC()->session->set( 'ucfw_maxdisc_incr',  0 );
        }

        return $discount;
    }
}
