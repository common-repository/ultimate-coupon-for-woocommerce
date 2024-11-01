<?php
namespace UCFW;

class Initialize
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

        add_action( 'init', [ $this, 'restructure_117' ] );
        add_action( 'admin_init', [ $this, 'restructure_117' ] );
    }

    public function restructure_117()
    {
        $d = get_option( '_ucfw_res_version_117', false );

        if ( $d )
            return;
        
        foreach ( $this->helper->getAllCoupons() as $code )
        {
            $coupon      = new \WC_Coupon( $code );
            $maxDiscount = $coupon->get_meta( 'ucfw_max_discount' );
            add_post_meta( $coupon->get_id(), '_ucfw_max_discount', $maxDiscount, true );
        }

        add_option( '_ucfw_res_version_117', 'yes' );
        return;
    }
}
