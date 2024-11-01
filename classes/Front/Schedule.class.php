<?php
namespace UCFW\Front;

class Schedule
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

        add_filter( 'woocommerce_coupon_is_valid', array( $this, 'run' ) , 11, 2 );
    }

    /**
     * Validate coupon based on schedule settings
     *
     * @return void
     */
    public function run( $isValid, $coupon )
    {
        if ( !$isValid )
            return false;
        
        $couponID = $coupon->get_id();

        if ( 'yes' !== get_post_meta( $couponID, '_ucfw_schedule_enabled', true ) )
            return $isValid;

        $currentTime = current_time( 'timestamp' );
        $startDate   = get_post_meta( $couponID, '_ucfw_schedule_start_date', true );

        if ( !empty($startDate) )
        {
            $unix = strtotime($startDate);

            if ( $currentTime < $unix )
            {
                throw new \Exception(
                    sprintf( esc_html__( 'Sorry, this coupon will be available after %s.', 'ultimate-coupon-for-woocommerce' ), date('l d F h:i A, Y', $unix ) )
                );
                return false;
            }
        }

        $expireDate = get_post_meta( $couponID, '_ucfw_schedule_expire_date', true );

        if ( !empty($expireDate) )
        {
            $unix = strtotime($expireDate);

            if ( $currentTime > $unix )
            {
                throw new \Exception(
                    sprintf( esc_html__( 'Sorry, this coupon expired on %s.', 'ultimate-coupon-for-woocommerce' ), date('l d F h:i A, Y', $unix ) )
                );
                return false;
            }
        }

        return apply_filters( 'ucfw_schedule_is_valid', $isValid, $coupon );
    }
}
