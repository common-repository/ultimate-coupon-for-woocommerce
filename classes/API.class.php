<?php
namespace UCFW;

class API extends \WP_REST_Controller
{
    const _namespace = 'UCFW/v1';
    const _base      = 'settings';

    public static $instance;
    private $isAdmin;

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

    /**
     * Construct
     */
    private function __construct()
    {
        $this->isAdmin = current_user_can('administrator') ? true : false;

        add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
    }

    /**
     * Register - API routes
     *
     * @return void
     */
    public function registerRoutes()
    {
        register_rest_route(
            self::_namespace,
            '/' . self::_base . '/search/products/(?P<id>.*?)',
            array(
                'args' => array(
                    'id' => array(
                        'type' => 'string',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'getProducts' ),
                    'permission_callback' => array( $this, 'adminPermissions' ),
                    'args'                => array(),
                )
            )
        );

        register_rest_route(
            self::_namespace,
            '/' . self::_base . '/search/product-categories/(?P<id>[\w]+)',
            array(
                'args' => array(
                    'id' => array(
                        'type' => 'string',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'getProductCategories' ),
                    'permission_callback' => array( $this, 'adminPermissions' ),
                    'args'                => array(),
                )
            )
        );

        register_rest_route(
            self::_namespace,
            '/' . self::_base . '/conditions/(?P<id>[\w]+)',
            array(
                'args' => array(
                    'id' => array(
                        'type' => 'number',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'updateConditions' ),
                    'permission_callback' => array( $this, 'adminPermissions' ),
                    'args'                => array(),
                )
            )
        );

        register_rest_route(
            self::_namespace,
            '/' . self::_base . '/deals/(?P<id>[\w]+)',
            array(
                'args' => array(
                    'id' => array(
                        'type' => 'number',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'updateDeals' ),
                    'permission_callback' => array( $this, 'adminPermissions' ),
                    'args'                => array(),
                )
            )
        );

        register_rest_route(
            self::_namespace,
            '/' . self::_base . '/search/products',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'getProducts' ),
                    'permission_callback' => array( $this, 'adminPermissions' ),
                    'args'                => array(),
                )
            )
        );

        register_rest_route(
            self::_namespace,
            '/' . self::_base . '/search/product-categories',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'getProductCategories' ),
                    'permission_callback' => array( $this, 'adminPermissions' ),
                    'args'                => array(),
                )
            )
        );
    }

    /**
     * Admin permissions 
     *
     * @return array
     */
    public function adminPermissions($request)
    {
        return $this->isAdmin;
    }

    /**
     * Get products
     *
     * @return array
     */
    public function getProducts ( $request = null )
    {
        $id      = (isset($request['id']) ) ? sanitize_text_field(urldecode($request['id'])) : false;
        $results = array();

        if ( $id )
        {
            global $wpdb;
            $query = "
                SELECT `ID`,`post_title` FROM $wpdb->posts
                WHERE post_type IN ('product', 'product_variation')
                AND post_title LIKE '%{$id}%'
                AND post_status = 'publish'
                ";
            
            $data  = $wpdb->get_results($query, ARRAY_A);

            if ( !is_array($data) || empty($data) )
                return $this->noSuccess();
            
            foreach ( $data as $row )
            {
                $name      = sanitize_text_field($row['post_title']);
                $results[] = array(
                    'name'  => $name,
                    'value' => "{$row['ID']}|{$name}"
                );
            }
        }

        $response = rest_ensure_response( array(
            'success' => true,
            'results' => $results
        ) );
        return $response;
    }

    /**
     * Get product categories
     *
     * @return array
     */
    public function getProductCategories ($request)
    {
        $id      = ( isset($request['id']) ) ? sanitize_text_field(urldecode($request['id'])) : false;
        $results = array();

        if ( $id )
        {
            global $wpdb;
            $query = "
                SELECT * FROM $wpdb->terms
                INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id
                WHERE $wpdb->term_taxonomy.taxonomy = 'product_cat'
                AND $wpdb->terms.name LIKE '%{$id}%'
                ORDER BY $wpdb->terms.name DESC
                ";

            foreach ( $wpdb->get_results($query) as $category )
            {
                $results[] = array(
                    'name'  => $category->name,
                    'value' => "{$category->term_id}|{$category->name}"
                );
            }
        }

        $response = rest_ensure_response( array(
            'success' => true,
            'results' => $results
        ) );
        return $response;
    }
    
    /**
     * Update conditions
     *
     * @return array
     */
    public function updateConditions( $request )
    {
        if ( !is_numeric($request['id']) )
            $this->noSuccess();
        
        $id       = absint($request['id']);
        $settings = $request->get_param('_ucfw_groups');
        $enabled  = ($request->get_param('_ucfw_conditions_enabled') === 'yes') ? 'yes' : 'no';

        update_post_meta( $id, '_ucfw_conditions',         \UCFW\Admin\Conditions::processSettings( $settings ) );
        update_post_meta( $id, '_ucfw_conditions_enabled', sanitize_text_field( $enabled ) );
        return true;
    }

    /**
     * Update conditions
     *
     * @return array
     */
    public function updateDeals( $request )
    {
        if ( !is_numeric($request['id']) )
            $this->noSuccess();
        
        $id       = absint($request['id']);
        $settings = $request->get_param('_ucfw_deals');

        update_post_meta( $id, '_ucfw_deals', \UCFW\Admin\Deals::processSettings( $settings ) );
        return true;
    }

    /**
     * No success response
     *
     * @return array
     */
    private function noSuccess()
    {
        return rest_ensure_response(array(
            'success' => false,
            'error'   => true
        ));
    }
}
