<?php
namespace UCFW;

class Resources
{
    public static $instance;

    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * Singleton Pattern
     *
     * @return object
     */
    private function __construct()
    {
        if ( is_admin() )
            add_action( 'admin_enqueue_scripts', array( $this, 'register' ), 5 );
        else
            add_action( 'wp_enqueue_scripts', array( $this, 'register' ), 5 );
    }

    /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register()
    {
        $this->registerScripts( $this->scripts() );
        $this->registerStyles( $this->styles() );
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    private function registerScripts( $scripts )
    {
        foreach ( $scripts as $handle => $script )
        {
            $deps      = isset( $script['deps'] )      ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] )   ? $script['version'] : UCFW_VERSION;

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function registerStyles( $styles )
    {
        foreach ( $styles as $handle => $style )
        {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;
            wp_register_style( $handle, $style['src'], $deps, $style['version'] );
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array
     */
    public function scripts()
    {
        $scripts = array(
            'ucfw-admin' => array(
                'src'       => UCFW_RESOURCES . '/js/admin.js',
                'deps'      => array( 'jquery' ),
                'version'   => filemtime( UCFW_PATH . '/resources/js/admin.js' ),
                'in_footer' => true
            ),
            'ucfw-semantic' => array(
                'src'       => UCFW_RESOURCES . '/semantic/semantic.min.js',
                'deps'      => array( 'jquery' ),
                'version'   => filemtime( UCFW_PATH . '/resources/semantic/semantic.min.js' ),
                'in_footer' => true
            ),
            'ucfw-serializejson' => array(
                'src'       => UCFW_RESOURCES . '/js/jquery.serializejson.min.js',
                'deps'      => array( 'jquery' ),
                'version'   => filemtime( UCFW_PATH . '/resources/js/jquery.serializejson.min.js' ),
                'in_footer' => true
            ),
            'ucfw-front-templates' => array(
                'src'       => UCFW_RESOURCES . '/js/front-templates.js',
                'deps'      => array( 'jquery' ),
                'version'   => filemtime( UCFW_PATH . '/resources/js/front-templates.js' ),
                'in_footer' => true
            )
        );

        return $scripts;
    }

    /**
     * Get registered styles
     *
     * @return array
     */
    public function styles()
    {
        $styles = array(
            'ucfw' => array(
                'src'     => UCFW_RESOURCES . '/css/ucfw.css',
                'version' => filemtime( UCFW_PATH . '/resources/css/ucfw.css' ),
            ),
            'ucfw-admin' => array(
                'src'     => UCFW_RESOURCES . '/css/admin.css',
                'version' => filemtime( UCFW_PATH . '/resources/css/admin.css' ),
            ),
            'ucfw-semantic' => array(
                'src'     => UCFW_RESOURCES . '/semantic/semantic.min.css',
                'version' => filemtime( UCFW_PATH . '/resources/semantic/semantic.min.css' ),
            ),
            'ucfw-front' => array(
                'src'     => UCFW_RESOURCES . '/css/front.css',
                'version' => filemtime( UCFW_PATH . '/resources/css/front.css' ),
            ),
            'ucfw-front-templates' => array(
                'src'     => UCFW_RESOURCES . '/css/front-templates.css',
                'version' => filemtime( UCFW_PATH . '/resources/css/front-templates.css' ),
            ),
            'ucfw-animate' => array(
                'src'     => UCFW_RESOURCES . '/css/animate.css',
                'version' => filemtime( UCFW_PATH . '/resources/css/animate.css' ),
            ),
        );

        return $styles;
    }
}
