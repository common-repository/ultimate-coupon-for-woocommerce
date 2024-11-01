<?php
namespace UCFW\Admin;

class Initialize
{
    public static $instance;

    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();
        
        return self::$instance;
    }

    private function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
        add_action( 'admin_footer',          array( $this, 'buyPremiumPopup' ), 1 );

        add_filter( 'woocommerce_coupon_code_generator_character_length', array( $this, 'lengthOverride' ), 11 );  
        add_filter( 'woocommerce_coupon_code_generator_prefix',           array( $this, 'prefixOverride' ), 11 );  
        add_filter( 'woocommerce_coupon_code_generator_suffix',           array( $this, 'suffixOverride' ), 11 );
    }

    public function enqueue()
    {
        $this->styles();
        $this->scripts();
    }

    private function styles()
    {
        wp_enqueue_style( 'ucfw' );

        if ( get_current_screen()->id == 'shop_coupon' || get_current_screen()->id == 'ucfw-settings_page_crb_carbon_fields_container_get_started' || get_current_screen()->id == 'toplevel_page_crb_carbon_fields_container_ucfw_global_settings' )
        {
            wp_enqueue_style( 'ucfw-admin' );
            wp_enqueue_style( 'ucfw-semantic' );
        }
    }

    private function scripts()
    {
        if ( get_current_screen()->id == 'shop_coupon' || get_current_screen()->id == 'toplevel_page_crb_carbon_fields_container_ucfw_global_settings' )
        {
            wp_enqueue_script( 'ucfw-admin' );
            wp_enqueue_script( 'ucfw-semantic' );
        }

        if ( get_current_screen()->id == 'shop_coupon' )
            wp_enqueue_script( 'ucfw-serializejson' );
    }

    public function lengthOverride( $data )
    {
        $couponLength = get_option( '_ucfw_global_settings_clength' );
        $data         = $couponLength ? $couponLength : $data;

        return $data;
    }

    public function prefixOverride( $data )
    {
        $couponPrefix = get_option( '_ucfw_global_settings_cprefix' );
        $data         = $couponPrefix ? $couponPrefix : '';

        return $data;
    }

    public function suffixOverride( $data )
    {
        $couponSuffix = get_option( '_ucfw_global_settings_csuffix' );
        $data         = $couponSuffix ? $couponSuffix : '';

        return $data;
    }

    public function buyPremiumPopup()
    {
        if ( get_current_screen()->id == 'shop_coupon' || get_current_screen()->id == 'toplevel_page_crb_carbon_fields_container_ucfw_global_settings' )
        {
        ?>
        <div class="ui modal tiny ucfw-buy-premium-popup">
            <i class="close icon"></i>
            <div class="content">
                <div class="ui two column grid">
                    <div class="column">
                        <div class="ucfw-pricing-container">
                            <div class="ucfw-pricing-plan">
                            <div class="ucfw-pricing-title"><?php echo esc_html__( 'Individuals', 'ultimate-coupon-for-woocommerce' ); ?></div>
                            <ul class="ucfw-pricing-features">
                                <li><?php echo esc_html__( 'Regular Updates', 'ultimate-coupon-for-woocommerce' ); ?></li>
                                <li><?php echo esc_html__( 'Priority Support', 'ultimate-coupon-for-woocommerce' ); ?></li>
                            </ul>
                                <div class="ucfw-pricing-price">
                                    <span><?php echo esc_html__( '$100', 'ultimate-coupon-for-woocommerce' ); ?></span>
                                    <?php echo esc_html__( '$49', 'ultimate-coupon-for-woocommerce' ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="ucfw-admin-card ucfw-hero">
                            <div class="ucfw-admin-card-section">
                                <img class="ucfw-logo" src="<?php echo esc_url( UCFW_RESOURCES . '/images/UCFW-Logo.png' ); ?>"> 
                                <br><br>
                                <h3>
                                    <strong><?php echo esc_html__( 'Unlock all premium features at a discounted price!', 'ultimate-coupon-for-woocommerce' ); ?></strong>
                                </h3>
                                <a target="_blank" href="<?php echo esc_url( '//jompha.com/ultimate-coupons-for-woocommerce' ); ?>" class="ui positive labeled icon button">
                                    <?php echo esc_html__( 'Get Premium', 'ultimate-coupon-for-woocommerce' ); ?><i class="checkmark icon"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
    }
}
