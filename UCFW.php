<?php
/**
 * UCFW class
 *
 * @class UCFW The class that holds the entire UCFW plugin
 */
final class UCFW
{
    public static $instance;

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.1.9';

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = array();

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
     * Constructor for the UCFW class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct()
    {
        $this->defineConstants();

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'bootCarbon' ) );
        add_action( 'plugins_loaded', array( $this, 'run' ) );
        add_action( 'activated_plugin', array($this, 'activationRedirect') );

        add_filter( 'plugin_action_links_' . plugin_basename(__DIR__) . '/ultimate-coupon-for-woocommerce.php', array( $this, 'settingLink' ) );
        add_filter( 'plugin_row_meta', array( $this, 'helpLinks' ), 10, 2);
    }

    /**
     * Define the constants
     * @return void
     */
    public function defineConstants()
    {
        define( 'UCFW_VERSION',       $this->version );
        define( 'UCFW_FILE',          __FILE__ );
        define( 'UCFW_PATH',          dirname( UCFW_FILE ) );
        define( 'UCFW_CLASSES',       UCFW_PATH . '/classes' );
        define( 'UCFW_ADMIN_CLASSES', UCFW_CLASSES . '/Admin' );
        define( 'UCFW_FRONT_CLASSES', UCFW_CLASSES . '/Front' );
        define( 'UCFW_URL',           plugins_url( '', UCFW_FILE ) );
        define( 'UCFW_RESOURCES',     UCFW_URL . '/resources' );
        define( 'UCFW_RENDER',        UCFW_PATH . '/render' );
        define( 'UCFW_RENDER_ADMIN',  UCFW_RENDER . '/Admin' );
        define( 'UCFW_RENDER_FRONT',  UCFW_RENDER . '/Front' );
    }

    /**
     * Boots Carbon
     */
    public function bootCarbon()
    {
        if ( !class_exists('woocommerce') )
        {
            add_action( 'admin_notices', array( $this, 'requiredWoocommerce' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'noticeScripts' ) );
            return;
        }
        
        require_once UCFW_PATH . '/vendor/autoload.php';
        \Carbon_Fields\Carbon_Fields::boot();
    }

    /**
     * Load the plugin after all plugis are loaded
     *
     * @return void
     */
    public function run()
    {
        if ( !class_exists('woocommerce') )
            return;
            
        $this->includes();
        \UCFW\Admin\Fields::getInstance();
        
        // Initialize classes
        add_action( 'init', array( $this, 'init_classes' ) );
        
        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes()
    {
        include_once UCFW_PATH . '/functions.php';

        include_once UCFW_ADMIN_CLASSES . '/Initialize.class.php';
        include_once UCFW_ADMIN_CLASSES . '/Fields.class.php';
        include_once UCFW_ADMIN_CLASSES . '/Conditions.class.php';
        include_once UCFW_ADMIN_CLASSES . '/Deals.class.php';

        include_once UCFW_FRONT_CLASSES . '/Initialize.class.php';
        include_once UCFW_FRONT_CLASSES . '/Conditions.class.php';
        include_once UCFW_FRONT_CLASSES . '/Deals.class.php';
        include_once UCFW_FRONT_CLASSES . '/Templates.class.php';
        include_once UCFW_FRONT_CLASSES . '/Schedule.class.php';
        include_once UCFW_FRONT_CLASSES . '/Restrictions.class.php';
        include_once UCFW_FRONT_CLASSES . '/URL.class.php';
        include_once UCFW_FRONT_CLASSES . '/Additionals.class.php';

        include_once UCFW_CLASSES . '/Helper.class.php';
        include_once UCFW_CLASSES . '/Resources.class.php';
        include_once UCFW_CLASSES . '/API.class.php';
        include_once UCFW_CLASSES . '/Initialize.class.php';
    }

    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes()
    {
        if ( $this->is_request( 'admin' ) )
        {
            \UCFW\Admin\Initialize::getInstance();
            \UCFW\Admin\Deals::getInstance();
            \UCFW\Admin\Conditions::getInstance();
        }

        if ( $this->is_request( 'front' ) )
        {
            \UCFW\Front\Initialize::getInstance();
            \UCFW\Front\Deals::getInstance();
            \UCFW\Front\Conditions::getInstance();
            \UCFW\Front\Restrictions::getInstance();
            \UCFW\Front\Schedule::getInstance();
            \UCFW\Front\Templates::getInstance();
            \UCFW\Front\URL::getInstance();
            \UCFW\Front\Additionals::getInstance();
        }

        \UCFW\API::getInstance();
        \UCFW\Resources::getInstance();
        \UCFW\Initialize::getInstance();
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup()
    {
        load_plugin_textdomain( 'ultimate-coupon-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate()
    {
        $installed = get_option( 'ucfw_installed' );

        if ( !$installed )
            update_option( 'ucfw_installed', current_time('timestamp') );

        update_option( 'ucfw_version', UCFW_VERSION );
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {}

    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or front.
     *
     * @return bool
     */
    private function is_request( $type )
    {
        switch ( $type )
        {
            case 'admin' :
                return is_admin();

            case 'ajax' :
                return defined( 'DOING_AJAX' );

            case 'rest' :
                return defined( 'REST_REQUEST' );

            case 'cron' :
                return defined( 'DOING_CRON' );

            case 'front' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __get( $prop ) 
    {
        if ( array_key_exists($prop, $this->container) )
            return $this->container[ $prop ];

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) 
    {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    /**
     * Enqueue scripts for notice
     */
    public function noticeScripts()
    {   
        wp_enqueue_style( 'ucfw-admin-notice', UCFW_RESOURCES . '/css/admin-notice.css', false, filemtime( UCFW_PATH . '/resources/css/admin-notice.css' ) );
    }

    function requiredWoocommerce()
    {
        if ( !class_exists('woocommerce') )
        { ?>

        <div class="ucfw-plugin-required-notice notice notice-warning">
            <img class="ucfw-logo" src="<?php echo esc_url( UCFW_RESOURCES . '/images/UCFW-Logo.png' ); ?>">
            <div class="ucfw-admin-notice-content">
                <h2><?php echo esc_html__( 'Required dependency.', 'ultimate-coupon-for-woocommerce' ); ?></h2>
                <p><?php echo esc_html__( 'Please ensure you have the WooCommerce plugin installed and activated.', 'ultimate-coupon-for-woocommerce' ); ?></p>
            </div>
        </div>

        <?php 
        }
    }

    /**
     * Redirect to plugin page on activation
     *
     */
    public function activationRedirect( $plugin ) 
    {
        if ( plugin_basename(__DIR__) . '/ultimate-coupon-for-woocommerce.php' == $plugin && class_exists('woocommerce') )
            exit( wp_redirect( admin_url( '/admin.php?page=crb_carbon_fields_container_get_started.php' ) ) );
    }

    /**
     * Setting page link in plugin list
     *
     */
    public function settingLink( $links ) 
    {
	    $settingLink = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( '/admin.php?page=crb_carbon_fields_container_ucfw_global_settings.php' ) ), esc_html__( 'Settings', 'ultimate-coupon-for-woocommerce' ) );

        if ( !class_exists('UCFWP') )
            $premiumLink = sprintf( '<a class="ucfw_get_premium" target="_blank" href="%s">%s</a>', esc_url( '//jompha.com/ultimate-coupons-for-woocommerce' ), esc_html__( 'Get Premium', 'ultimate-coupon-for-woocommerce' ) );
        else
            $premiumLink = sprintf( '<a class="ucfw_get_premium" target="_blank" href="%s">%s</a>', esc_url( '//support.jompha.com' ), esc_html__( 'Get Support', 'ultimate-coupon-for-woocommerce' ) );

	    $links[] = $settingLink;
	    $links[] = $premiumLink;

	    return $links;
	}

    /**
     * Plugin row links
     *
     */
    public function helpLinks( $links, $plugin )
    {
        if ( plugin_basename(__DIR__) . '/ultimate-coupon-for-woocommerce.php' != $plugin )
            return $links;
        
        $docsLink    = sprintf( '<a href="%s">%s</a>', esc_url( '//docs.jompha.com/ultimate-coupons-for-woocommerce' ), esc_html__( 'Docs', 'ultimate-coupon-for-woocommerce' ) );
        $supportLink = sprintf( '<a href="%s">%s</a>', esc_url( '//forum.jompha.com' ), esc_html__( 'Community support', 'ultimate-coupon-for-woocommerce' ) );
        
        $links[] = $docsLink;
        $links[] = $supportLink;
    
        return $links;
    }
}
