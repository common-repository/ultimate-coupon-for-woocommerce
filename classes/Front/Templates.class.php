<?php
namespace UCFW\Front;

class Templates
{
    public static $instance;
    private $helper;

    private $enabled;
    private $couponCode;
    private $layout;
    private $restrictions;
    private $title;
    private $description;
    private $goToShop;
    private $image;
    private $url;
    private $colorSet;
    private $startTime     = null;
    private $expireTime    = null;
    private $showCountdown = false;

    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();
        
        return self::$instance;
    }

    public function __construct()
    {
        $this->helper = \UCFW\Helper::getInstance();

        $this->enabled        = get_option( '_ucfw_template_enabled', false );
        $this->layout         = carbon_get_theme_option( 'ucfw_template_type' );
        $this->restrictions   = carbon_get_theme_option( 'ucfw_template_restricted_user_roles' );
        $this->title          = get_option( '_ucfw_template_title', '' );
        $this->description    = get_option( '_ucfw_template_description', '' );
        $this->goToShop       = get_option( '_ucfw_template_goshop_text', 'Go To Shop' );
        $this->url            = get_option( '_ucfw_template_goshop_url', '#' );
        $this->image          = get_option( '_ucfw_template_image', false );
        $this->colorSet       = get_option( '_ucfw_template_color_set', 'default' );

        $this->couponCode = get_option( '_ucfw_template_coupons', false );
        if (empty($this->couponCode))
            $this->couponCode = false;

        $startTime  = get_option( '_ucfw_template_start_time', null );
        $expireTime = get_option( '_ucfw_template_expire_time', null );

        $this->startTime = ( null == $startTime ) ? (current_time('timestamp') - 1) : strtotime($startTime);
        $this->expireTime = ( empty($expireTime) || null == $expireTime ) ? null : strtotime($expireTime);

        if ( $this->startTime > current_time('timestamp') )
            return;
            
        if ( null != $this->expireTime )
        {
            $this->showCountdown = true;

            if ( current_time('timestamp') > $this->expireTime )
                return;
        }

        $formattedTitle = $this->title;
        preg_match_all('/\[\[(.*?)\]\]/i', $this->title, $matches);

        foreach ($matches[0] as $i => $code)
        {
            $text = $matches[1][$i];
            $formattedTitle = str_ireplace( $code, '<span class="highlighted-text-' . ($i + 1) . '">' . $text . '</span>', $formattedTitle );
        }

        $this->formattedTitle = $formattedTitle;

        add_action( 'wp_head', array( $this, 'variables' ), 0 );
        add_action( 'wp_head', array( $this, 'output' ) );
    }

    /**
     * Popup styles
     *
     * @return void
     */
    private function popup() 
    {
        $path = apply_filters( 'ucfw_template_popup_path', UCFW_RENDER_FRONT . '/markup/template-popup-blackfriday-1.php', carbon_get_theme_option( 'ucfw_template_popupdesign' ) );

        if ( !file_exists( $path ) )
            return;
    ?>
        <div id="ucfw-template-popup-container">
            <div class="ucfw-template-popup-overlay"></div>
            <?php include_once $path; ?>
        </div>
    <?php
        $this->countdown();
    }

    /*
     * Nofication Bar Templates
    */
    private function snackbar( $class )
    {
        $path = apply_filters( 'ucfw_template_snackbar_path', UCFW_RENDER_FRONT . '/markup/template-snackbar-blackfriday-1.php', carbon_get_theme_option( 'ucfw_template_bardesign' ) );

        if (! file_exists( $path ) )
            return;

        ?>
        <div id="ucfw-template-snackbar-<?php echo $class; ?>-container">
            <?php include_once $path; ?>
        </div>
        <?php
            $this->countdown();

        if ( 'header' === $class ) 
        {
        ?>
        <script>
            jQuery(function()
            {
                'use strict';
                jQuery('body').css('margin-top', jQuery('#ucfw-template-snackbar-header-container').height() + 'px');
            });
        </script>
        <?php 
        }
    }

    /**
    * Handle output of banner
    *
    * @return void
    */
    public function output()
    {   
        $restriction = apply_filters( 'ucfw_template_user_restriction', false );

        if ( 'yes' === $this->enabled && !$restriction ) 
        { 
            $cookieChecker = isset($_COOKIE['popup-bar']) ? sanitize_text_field( $_COOKIE['popup-bar'] ) : '';

            if ('popup' === $this->layout )
            {
                if ( 'popup-seen' !== $cookieChecker )
                    $this->popup();
            }
            elseif ( 'header' === $this->layout  )
            {
                if ( 'bar-seen' != $cookieChecker )
                    $this->snackbar('header');
            }
            elseif ( 'footer' === $this->layout )
            {
                if ( 'bar-seen' != $cookieChecker )
                    $this->snackbar('footer');
            }

            apply_filters( 'ucfw_template_popup_html', '', $this->layout, $cookieChecker );
       }
    }

    /**
    * Handle output of banner
    *
    * @return void
    */
    public function variables()
    {   
        if ( 'yes' !== $this->enabled ) 
            return;
    ?>
    <style>
    :root {
        --ucfw-template-background-color: <?php echo wp_kses( get_option( '_ucfw_template_background_color', '#ffffff' ), array() ); ?>;
        --ucfw-template-text-color: <?php echo wp_kses( get_option( '_ucfw_template_text_color', '#bcbad0' ), array() ); ?>;
        --ucfw-template-highlighted-text-1-color: <?php echo wp_kses( get_option( '_ucfw_template_highlighted_text_1_color', '#be0017' ), array() ); ?>;
        --ucfw-template-description-text-color: <?php echo wp_kses( get_option( '_ucfw_template_description_text_color', '#000000' ), array() ); ?>;
        --ucfw-template-coupon-background-color: <?php echo wp_kses( get_option( '_ucfw_template_coupon_background_color', '#be0017' ), array() ); ?>;
        --ucfw-template-coupon-text-color: <?php echo wp_kses( get_option( '_ucfw_template_coupon_text_color', '#ffffff' ), array() ); ?>;
        --ucfw-template-goshop-background-color: <?php echo wp_kses( get_option( '_ucfw_template_goshop_background_color', '#202020' ), array() ); ?>;
        --ucfw-template-goshop-text-color: <?php echo wp_kses( get_option( '_ucfw_template_goshop_text_color', '#ffffff' ), array() ); ?>;
        --ucfw-template-countdown-background-color: <?php echo wp_kses( get_option( '_ucfw_template_countdown_background_color', '#ffffff' ), array() ); ?>;
        --ucfw-template-countdown-text-color: <?php echo wp_kses( get_option( '_ucfw_template_countdown_text_color', '#222222' ), array() ); ?>;
        --ucfw-template-countdown-interval-background-color: <?php echo wp_kses( get_option( '_ucfw_template_countdown_interval_background_color', '#ffffff' ), array() ); ?>;
        --ucfw-template-countdown-interval-text-color: <?php echo wp_kses( get_option( '_ucfw_template_countdown_interval_text_color', '#555555' ), array() ); ?>;
    }
    </style>
    <?php
    }


    public function countdown()
    {
        if (! $this->showCountdown)
            return;
    ?>
    <script>
        var ucfw_countdown_date = <?php echo (is_null($this->expireTime)) ? 0 : absint( $this->expireTime ) * 1000; ?>;
        var ucfw_countdown = setInterval(function()
        {
            var now               = new Date().getTime(),
                ucfw_distance     = ucfw_countdown_date - now,
                ucfw_days_int     = Math.floor(ucfw_distance / (1000 * 60 * 60 * 24)),
                ucfw_hours_int    = Math.floor((ucfw_distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
                ucfw_minutes_int  = Math.floor((ucfw_distance % (1000 * 60 * 60)) / (1000 * 60)),
                ucfw_seconds_int  = Math.floor((ucfw_distance % (1000 * 60)) / 1000),

                ucfw_days         = (ucfw_days_int < 10)    ? '0' + ucfw_days_int.toString() : ucfw_days_int.toString(),
                ucfw_hours        = (ucfw_hours_int < 10)   ? '0' + ucfw_hours_int.toString() : ucfw_hours_int.toString(),
                ucfw_minutes      = (ucfw_minutes_int < 10) ? '0' + ucfw_minutes_int.toString() : ucfw_minutes_int.toString(),
                ucfw_seconds      = (ucfw_seconds_int < 10) ? '0' + ucfw_seconds_int.toString() : ucfw_seconds_int.toString(),

                ucfw_days_html    = '',
                ucfw_hours_html   = '',
                ucfw_minutes_html = '',
                ucfw_seconds_html = '';

            jQuery.each( ucfw_days.split(''), function(di, dv){
                ucfw_days_html += '<span>' + dv + '</span> ';
            });

            jQuery.each( ucfw_hours.split(''), function(hi, hv){
                ucfw_hours_html += '<span>' + hv + '</span> ';
            });

            jQuery.each( ucfw_minutes.split(''), function(mi, mv){
                ucfw_minutes_html += '<span>' + mv + '</span> ';
            });

            jQuery.each( ucfw_seconds.split(''), function(si, sv){
                ucfw_seconds_html += '<span>' + sv + '</span> ';
            });

            if ( ucfw_distance >= 0 )
            {
                jQuery('.ucfw-template-countdown .days').html(ucfw_days_html);
                jQuery('.ucfw-template-countdown .hours').html(ucfw_hours_html);
                jQuery('.ucfw-template-countdown .minutes').html(ucfw_minutes_html);
                jQuery('.ucfw-template-countdown .seconds').html(ucfw_seconds_html);
            }
            else
            {
                clearInterval(ucfw_countdown);
                //jQuery('.counter').html( '<?php echo ucfw_esc_js( 'EXPIRED', 'ultimate-coupons-for-woocommerce-premium' ); ?>' );
            }

        }, 1000);
    </script>
    <?php
    }
}
