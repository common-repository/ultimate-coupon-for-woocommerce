<?php
namespace UCFW;

class Helper
{
    public static $instance;

    /**
     * Get instance
     *
     * @return object
     */
    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();

        return self::$instance;
    }

    public function __construct(){}

    /**
     * Get all coupons
     *
     * @return array
     */
    public function getAllCoupons()
    {
        global $wpdb;
        $query = "SELECT `post_name`,`post_title` FROM $wpdb->posts
                WHERE post_type = 'shop_coupon'
                AND post_status = 'publish'";

        $results = $wpdb->get_results($query, ARRAY_A);
        $options = array();

        if ( !is_array($results) || empty($results) )
            return $options;

        foreach ( $results as $row )
            $options[ sanitize_text_field($row['post_title']) ] = sanitize_text_field($row['post_title']);
      
        return $options;
    }

    /**
     * Get all user roles with guest
     *
     * @return array
     */
    public function getUserRoles( $guest = false )
    {   
        global $wp_roles;
        $roles = $wp_roles->get_names();

        if ( $guest ) 
            $roles['guest'] = esc_html__( 'Guest', 'ultimate-coupon-for-woocommerce' );

        return $roles;
    }

    /**
     * Get available shipping methods
     *
     * @return array
     */
    public static function getShippingMethods()
    {
        $methods = WC()->shipping->load_shipping_methods();
        $format  = array();

        if ( !empty($methods) ) 
        {
            foreach ($methods as $key => $value) 
                $format[$key] = $value->method_title;
        }

        return $format;
    }

    /**
     * Get available shipping zones
     *
     * @return array
     */
    public static function getShippingZones()
    {
        $zones = \WC_Shipping_Zones::get_zones();

        if ( empty($zones) )
            return array();

        $format = array();
        foreach ($zones as $zone) 
            $format[ $zone['id'] ] = $zone['zone_name'];

        return $format;
    }

    /**
     * Get available payment methods
     *
     * @return array
     */
    public static function getPaymentMethods()
    {
        $paymentMethods = WC()->payment_gateways->payment_gateways();
        $passMethods    = array();

        if ( !empty($paymentMethods) )  
        {
            foreach ($paymentMethods as $key => $value) 
                $passMethods[$key] = $value->method_title;
        }

        return $passMethods;
    }

    /**
     * Get current user roles
     *
     * @return array
     */
    public function getCurrentUserRoles()
    {
        return wp_get_current_user()->ID ? wp_get_current_user()->roles : array('guest');
    }
}
