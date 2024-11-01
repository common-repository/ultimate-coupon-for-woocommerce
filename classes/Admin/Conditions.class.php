<?php
namespace UCFW\Admin;

class Conditions
{
    public static $instance;

    private $couponId;
    private $enabled;
    private $settings;
    private $configurations;

    private $saveUrl;
    private $searchUrl;

    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();
        
        return self::$instance;
    }

    private function __construct()
    {
        $action = ( isset($_GET['action']) ) ? sanitize_text_field( $_GET['action'] ) : '';

        if ( 'edit' !== $action || !isset( $_GET['post'] )  )
            return;

        $this->couponId = ( isset($_GET['post']) ) ? absint($_GET['post']) : 0;

        $this->setConfigurations();
        $this->setSettings();

        $this->setSaveUrl();
        $this->setSearchUrl();

        add_action( 'admin_footer', array( $this, 'footerJs' ), PHP_INT_MAX );
    }

    public function getEnabled()
    {
        return $this->enabled;
    }
    
    public function getSaveUrl()
    {
        return esc_url( $this->saveUrl );
    }

    public function getSearchUrl()
    {
        return esc_url( $this->searchUrl );
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getConfigurations()
    {
        return $this->configurations;
    }

    public function setSaveUrl()
    {
        $couponId      = ( isset( $_GET['post'] ) ) ? absint($_GET['post']) : 0;
        $this->saveUrl = get_site_url() . '/wp-json/' . \UCFW\Api::_namespace . '/' . \UCFW\Api::_base . '/conditions/' . $couponId;
    }

    public function setSearchUrl()
    {
        $this->searchUrl = get_site_url() . '/wp-json/' . \UCFW\Api::_namespace . '/' . \UCFW\Api::_base . '/search';
    }

    public function setSettings()
    {
        $enabled       = get_post_meta( $this->couponId, '_ucfw_conditions_enabled', true );
        $this->enabled = ($enabled === 'yes') ? true : false;

        $this->settings = false;
        $settings       = get_post_meta( $this->couponId, '_ucfw_conditions', true );

        if (isset($settings) && is_array($settings))
        {
            $settings = $settings;
            $count    = count($settings);

            if ($count > 0)
                $this->settings = $settings;
        }
    }

    public function setConfigurations()
    {
        global $wp_roles;
        
        $compare = array(
            'eq' => esc_html__( 'EXACTLY', 'ultimate-coupon-for-woocommerce' ),
            'nt' => esc_html__( 'ANYTHING BUT', 'ultimate-coupon-for-woocommerce' ),
            'mt' => esc_html__( 'MORE THAN', 'ultimate-coupon-for-woocommerce' ),
            'lt' => esc_html__( 'LESS THAN', 'ultimate-coupon-for-woocommerce' )
        );
        $userRoles = array_merge( array( 'guest' => esc_html__( 'Guest', 'ultimate-coupon-for-woocommerce' ) ), $wp_roles->get_names() );

        $this->configurations = array(
            'product_category' => array(
                'id'     => 'product-category',
                'name'   => esc_html__( 'Product Categories in Cart', 'ultimate-coupon-for-woocommerce' ),
                'inputs' => array(
                    array(
                        'tag'         => 'select',
                        'options'     => array(),
                        'search'      => 'product-categories',
                        'placeholder' => esc_attr__( 'Search for categories by name', 'ultimate-coupon-for-woocommerce' ),
                        'multiple'    => true,
                        'id'          => 'categories'
                    ),
                    array(
                        'tag'     => 'select',
                        'options' => $compare,
                        'id'      => 'compare'
                    ),
                    array(
                        'tag'         => 'input',
                        'type'        => 'number',
                        'placeholder' => esc_attr__( 'Number of products of this category', 'ultimate-coupon-for-woocommerce' ),
                        'id'          => 'value'
                    )
                )
            ),
            'allowed_users' => array(
                'id'     => 'allowed-users',
                'name'   => esc_html__( 'Allowed Users', 'ultimate-coupon-for-woocommerce' ),
                'inputs' => array(
                    array(
                        'tag'         => 'select',
                        'options'     => $userRoles,
                        'type'        => 'user-roles',
                        'placeholder' => esc_attr__( 'Choose roles', 'ultimate-coupon-for-woocommerce' ),
                        'multiple'    => true
                    )
                )
            ),
            'disallowed_users' => array(
                'id'     => 'disallowed-users',
                'name'   => esc_html__( 'Disallowed Users', 'ultimate-coupon-for-woocommerce' ),
                'inputs' => array(
                    array(
                        'tag'         => 'select',
                        'options'     => $userRoles,
                        'type'        => 'user-roles',
                        'placeholder' => esc_attr__( 'Choose roles', 'ultimate-coupon-for-woocommerce' ),
                        'multiple'    => true
                    )
                )
            ),
            'logged_status' => array(
                'id'     => 'logged-status',
                'name'   => esc_html__( 'User Logged Status', 'ultimate-coupon-for-woocommerce' ),
                'inputs' => array(
                    array(
                        'tag'     => 'select',
                        'options' => array(
                            'yes' => esc_html__( 'Logged In', 'ultimate-coupon-for-woocommerce' ),
                            'no'  => esc_html__( 'Guest', 'ultimate-coupon-for-woocommerce' )
                        ),
                        'placeholder' => esc_attr__( 'Choose logged status', 'ultimate-coupon-for-woocommerce' )
                    )
                )
            ),
            'first_purchase' => array(
                'id'     => 'first-purchase',
                'name'   => esc_html__( 'User First Purchase Only', 'ultimate-coupon-for-woocommerce' ),
                'inputs' => array(
                    array(
                        'tag'     => 'select',
                        'options' => array(
                            'yes' => esc_html__( 'Yes', 'ultimate-coupon-for-woocommerce' ),
                            'no'  => esc_html__( 'No', 'ultimate-coupon-for-woocommerce' )
                        ),
                        'placeholder' => esc_attr__( 'Yes or No', 'ultimate-coupon-for-woocommerce' )
                    )
                )
            ),
            'cart_quantity' => array(
                'id'     => 'cart-quantity',
                'name'   => esc_html__( 'Cart Quantity', 'ultimate-coupon-for-woocommerce' ),
                'inputs' => array(
                    array(
                        'tag'     => 'select',
                        'options' => $compare,
                        'id'      => 'compare'
                    ),
                    array(
                        'tag'         => 'input',
                        'type'        => 'number',
                        'id'          => 'value',
                        'placeholder' => esc_attr__( 'Number of products in cart', 'ultimate-coupon-for-woocommerce' )
                    )
                )
            ),
            'cart_subtotal' => array(
                'id'     => 'cart-subtotal',
                'name'   => esc_html__( 'Cart Subtotal', 'ultimate-coupon-for-woocommerce' ),
                'label'  => sprintf( esc_html__( 'Cart Subtotal (%s)', 'ultimate-coupon-for-woocommerce' ), get_woocommerce_currency_symbol() ),
                'inputs' => array(
                    array(
                        'tag'     => 'select',
                        'options' => $compare,
                        'id'      => 'compare'
                    ),
                    array(
                        'tag'         => 'input',
                        'type'        => 'number',
                        'id'          => 'value',
                        'placeholder' => sprintf( esc_attr__( '%s amount in cart', 'ultimate-coupon-for-woocommerce' ), get_woocommerce_currency_symbol() )
                    )
                )
            ),
            'product_quantity' => array(
                'id'            => 'product-quantity',
                'name'          => esc_html__( 'Product Quantity in Cart', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'order_count' => array(
                'id'            => 'order-count',
                'name'          => esc_html__( 'User Order Count', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'total_spent' => array(
                'id'            => 'total-spent',
                'name'          => esc_html__( 'User Total Spent', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'hours_after_registration' => array(
                'id'            => 'hours-after-registration',
                'name'          => esc_html__( 'Hours After User Registration', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'hours_after_last_order' => array(
                'id'            => 'hours-after-last-order',
                'name'          => esc_html__( 'Hours After User Last Order', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'has_ordered_before' => array(
                'id'            => 'has-ordered-before',
                'name'          => esc_html__( 'Has User Ordered Before', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'shipping_methods' => array(
                'id'            => 'shipping-methods',
                'name'          => esc_html__( 'Shipping Methods', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'payment_methods' => array(
                'id'            => 'payment-methods',
                'name'          => esc_html__( 'Payment Methods', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'custom_user_meta' => array(
                'id'            => 'custom-user-meta',
                'name'          => esc_html__( 'Custom User Meta', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'custom_product_meta' => array(
                'id'            => 'custom-product-meta',
                'name'          => esc_html__( 'Custom Product Meta', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            )
        );
        $this->configurations = apply_filters( 'ucfw_conditions_configurations', $this->configurations );
    }

    /**
     * Render conditions js code in footer
     *
     * @return void
     */
    public function footerJs()
    {
        if ( get_current_screen()->id === 'shop_coupon' )
            include_once UCFW_RENDER_ADMIN . '/js/conditions.js.php';
    }

    /**
     * Process and save settings
     *
     * @return mixed array|boolean
     */
    public static function processSettings($settings)
    {
        if ( !is_array($settings) || empty($settings) )
            return false;

        $groups = array();

        foreach ($settings as $groupSetting)
        {
            if ( !is_array($groupSetting['conditions']) || empty($groupSetting['conditions']) )
                continue;

            if ( isset($groupSetting['logic']) )
            {
                $groups[] = array(
                    'type' => 'logic',
                    'logic' => sanitize_text_field( $groupSetting['logic'] )
                );
                unset($groupSetting['logic']);
            }

            $group = array(
                'type'       => 'group',
                'conditions' => array()
            );

            foreach ( $groupSetting['conditions'] as $condition )
            {
                if ( !isset($condition['data']) || empty($condition['data']) )
                    continue;

                if (isset($condition['logic']))
                {
                    $group['conditions'][] = array(
                        'type' => 'logic',
                        'logic' => sanitize_text_field( $condition['logic'] )
                    );
                    unset($condition['logic']);
                }

                $values = $condition['data'];

                if (is_array($values))
                {
                    $newValues = array();

                    foreach ($values as $i => $value)
                    {
                        if (is_array($value))
                        {
                            foreach ($value as $j => $val)
                            {
                                if ( !preg_match('/^(.*?)\|(.*?)$/i', trim($val), $matches2) )
                                {
                                    if (is_numeric($val))
                                        $val = intval($val);

                                    elseif (is_string($val))
                                        $val = sanitize_text_field($val);

                                    $newValues[$i][$j] = $val;
                                    continue;
                                }

                                $newValues[$i][$j] = array(
                                    'value' => sanitize_text_field( $matches2[1] ),
                                    'text'  => sanitize_text_field( $matches2[2] )
                                );
                            }
                        }
                        else
                        {
                            if ( !preg_match('/^(.*?)\|(.*?)$/i', trim($value), $matches) )
                            {
                                if (is_numeric($value))
                                    $value = intval($value);
                                    
                                elseif (is_string($value))
                                    $value = sanitize_text_field($value);

                                $newValues[$i] = $value;
                                continue;
                            }

                            $newValues[$i] = array(
                                'value' => sanitize_text_field( $matches[1] ),
                                'text'  => sanitize_text_field( $matches[2] )
                            );
                        }
                    }
                }
                else
                {
                    if ( !preg_match('/^(.*?)\|(.*?)$/i', trim($values), $matches) )
                    {
                        if (is_numeric($values))
                            $values = intval($values);
                            
                        elseif (is_string($values))
                            $values = sanitize_text_field($values);
                        
                        $newValues = $values;
                        continue;
                    }

                    $newValues = array(
                        'value' => sanitize_text_field( $matches[1] ),
                        'text'  => sanitize_text_field( $matches[2] )
                    );
                }

                $group['conditions'][] = array(
                    'type' => sanitize_text_field( $condition['type'] ),
                    'data' => $newValues
                );
            }

            $groups[] = $group;
        }

        return $groups;
    }
}
