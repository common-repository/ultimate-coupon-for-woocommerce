<?php
namespace UCFW\Front;

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
      
        add_action( 'wp_enqueue_scripts',                     array( $this, 'enqueue' ) );
        add_action( 'wp_head',                                array( $this, 'inlineStyles' ) );
    }
   
    public function enqueue()
    {
        $this->styles();
        $this->scripts();
    }

    private function styles()
    {
        wp_enqueue_style( 'ucfw' );
        wp_enqueue_style( 'ucfw-front' );
        wp_enqueue_style( 'ucfw-front-templates' );
        wp_enqueue_style( 'ucfw-animate' );
    }

    private function scripts()
    {
        wp_enqueue_script( 'ucfw-serializejson' );
        wp_enqueue_script( 'ucfw-front-templates' );
    }

    /**
     * Inline styles
     *
     * @return array
     */
    public function inlineStyles()
    { 
        $borderWidth     = get_option( '_ucfw_deals_apply_item_border_width', '5px' );
        $borderColor     = get_option( '_ucfw_deals_apply_item_border_color', '#000000' );
        $backgroundColor = get_option( '_ucfw_deals_apply_item_background_color', '#ffffff' );

        if ( empty($borderWidth) )
            $borderWidth = '5px';
        
        if ( empty($borderColor) )
            $borderColor = '#000000';
        
        if ( empty($backgroundColor) )
            $backgroundColor = '#ffffff';
    ?>
    <style>
        .ucfw-deals-cart-apply-item {
            border-left: <?php echo wp_kses( $borderWidth, array() ); ?> solid <?php echo wp_kses( $borderColor, array() ); ?>;
            background-color: <?php echo wp_kses( $backgroundColor, array() ); ?>;
        }
    </style>
    <?php
    }
}
