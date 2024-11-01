<?php
namespace UCFW\Front;

class URL
{
    public static $instance;
    private $helper;

    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();
        
        return self::$instance;
    }

    private function __construct()
    {
        $this->helper = \UCFW\Helper::getInstance();
      
        add_action( 'wp_loaded',                     array( $this, 'saveToSession' ) );
        add_action( 'woocommerce_add_to_cart',       array( $this, 'addToCart' ), 10, 0);
        add_action( 'woocommerce_cart_item_removed', array( $this, 'onItemRemoved' ), 10, 2 );
    }

    public function saveToSession()
    {
        global $wpdb;

        $couponID    = '';
        $currentSlug = add_query_arg( null, null );

        if ( !preg_match('/\/coupon\/([a-zA-Z0-9_-]+)$/', $currentSlug, $matches) )
            return;
        
        $code   = sanitize_text_field($matches[1]);
        $coupon = new \WC_Coupon( $code );

        if ( $coupon->get_date_created() )
            $couponID = $coupon->get_id();
        else
        {
            $couponID = $wpdb->get_var( $wpdb->prepare("
                SELECT post_id 
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = '%s' 
                AND meta_value = '%s'", 
            '_ucfw_url_custom_code', $code ) );

            wp_reset_postdata();
            $code = get_the_title( $couponID );
        }
        
        if ( !empty($couponID) && 'yes' === get_post_meta( $couponID, '_ucfw_url_enabled', true ) )
        {
            if ( isset( WC()->session ) && !WC()->session->has_session() )
                WC()->session->set_customer_session_cookie(true);

            if ( empty( WC()->session->get('ucfw_url_coupon') ) )
                WC()->session->set( 'ucfw_url_coupon', $code );

            wp_redirect( get_permalink( get_option( 'woocommerce_shop_page_id', 0 ) ) );
            exit();
        }
    }

    public function addToCart()
    {   
        $sess = WC()->session->get( 'ucfw_url_coupon' );

        if ( !empty($sess) && !WC()->cart->has_discount($sess) )
        {
            WC()->cart->add_discount( $sess );
            WC()->session->__unset( 'ucfw_url_coupon' );
        }
    }

    /**
     * Remove coupon from session
     *
     * @return void
     */
    public function onItemRemoved( $cartItemKey, $cart )
    {
        $sess = WC()->session->get( 'ucfw_url_coupon' );
    
        if ( $cart->has_discount( $sess ) && $cart->is_empty() )
        {
            $cart->remove_coupon($sess);
            WC()->session->__unset( 'ucfw_url_coupon' );
        }
    }
}
