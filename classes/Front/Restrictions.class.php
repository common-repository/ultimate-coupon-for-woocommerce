<?php
namespace UCFW\Front;

class Restrictions
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

        add_filter('woocommerce_coupon_is_valid', array( $this, 'run' ), 10, 2);
    }

    /**
     * Validate coupon based on restriction settings
     *
     * @return void
     */
    public function run( $isValid, $coupon )
    {
        if ( !$isValid )
            return false;
        
        $couponID = $coupon->get_id();
        
        if ( 'yes' !== get_post_meta( $couponID, '_ucfw_restriction_enabled', true ) )
            return $isValid;
        
        $restrictionType  = carbon_get_post_meta( $couponID, 'ucfw_restriction_user_allow_type' );
        $restrictionRoles = carbon_get_post_meta( $couponID, 'ucfw_restriction_user_roles' );
        $restrictionRoles = empty($restrictionRoles) ? array() : $restrictionRoles;
        $userRoles        = $this->helper->getCurrentUserRoles();
        $intersect        = array_intersect($userRoles, $restrictionRoles);

        if ( !empty($restrictionRoles) && ( ( 'allowed' === $restrictionType && empty($intersect)) || ( 'allowed' !== $restrictionType && !empty($intersect) ) ) )
        {
            throw new \Exception( esc_html__( 'Sorry, you are not eligible to use this coupon.', 'ultimate-coupon-for-woocommerce' ) );
            return false;
        }

        return apply_filters( 'ucfw_restrictions_is_valid', $isValid, $coupon );
    }
}
