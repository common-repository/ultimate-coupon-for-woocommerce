<?php
namespace UCFW\Front;

class Conditions
{
    const GROUP     = 3762;
    const CONDITION = 2529;

    public static $instance;

    public $couponId;
    public $groups;

    public $data;
    public $compare;
    public $cartData;

    public $group;
    public $condition;

    public static function getInstance()
    {
        if ( ! self::$instance instanceof self )
            self::$instance = new self();
        
        return self::$instance;
    }

    private function __construct()
    {
        add_filter( 'woocommerce_coupon_is_valid' ,                array( $this, 'run' ) , 12, 2 );
        add_action( 'woocommerce_update_cart_action_cart_updated', array( $this, 'onCartUpdated' ), 9, 1 );
    }

    /**
     * Check coupon validity on cart update
     *
     * @return array
     */
    public function onCartUpdated( $cartUpdated )
    {
        $coupons = WC()->cart->get_applied_coupons();

        if (  0 == count($coupons) )
            return;

        foreach ( $coupons as $code )
        {
            $coupon  = new \WC_Coupon( $code );
            $enabled = get_post_meta( $coupon->get_id(), '_ucfw_conditions_enabled', true );

            if ( 'yes' !== $enabled )
                continue;

            if ( !$coupon->is_valid_for_cart() )
            {
                WC()->cart->remove_coupon( $code );
                wc_add_notice( esc_html__('Cart conditions no longer match for this coupon. It has been removed.', 'ultimate-coupon-for-woocommerce'), 'error' );
            }
        }
        
        return;
    }

    /**
     * Run conditions validation
     *
     * @return bool
     */
    public function run( $isValid, $coupon )
    {
        if ( !$isValid )
            return false;
        
        $this->couponId = $coupon->get_id();
        $enabled  = get_post_meta( $this->couponId, '_ucfw_conditions_enabled', true );

        if ( 'yes' !== $enabled )
            return $isValid;

        $settings = get_post_meta( $this->couponId, '_ucfw_conditions', true );

        if ( empty($settings) || !isset($settings) || empty($settings) )
            return $isValid;
        
        $this->groups = $settings;
        $count        = count($this->groups);
        
        if ( 0 == $count )
            return false;
        
        elseif ( 1 == $count )
        {
            $this->group = $this->groups[0];
            $groupResult = $this->groupResult( $this->group );
        }
        else
        {
            foreach ( $this->groups as $i => $group )
            {
                $this->group = $group;

                if ( !$this->isLogic(self::GROUP) )
                    continue;
                
                $prevGroup = $this->groups[ ($i - 1) ];
                $nextGroup = $this->groups[ ($i + 1) ];

                $prevResult = (isset($groupResult)) ? $groupResult : $this->groupResult( $prevGroup );
                $nextResult = $this->groupResult( $nextGroup );

                $groupResult = $this->matchLogic( $prevResult, sanitize_text_field( $this->group['logic'] ), $nextResult );
            }
        }

        if ( !$groupResult )
        {
            throw new \Exception( esc_html__( 'Cart conditions do not match for this coupon!', 'ultimate-coupon-for-woocommerce' ) );
            return false;
        }
        
        return $groupResult;
    }

    /**
     * Check if item is logic
     *
     * @return boolean
     */
    private function isLogic( $t )
    {
        if ( self::GROUP == $t )
            return ( $this->group['type'] == 'logic' ) ? true : false;
        
        elseif ( self::CONDITION == $t )
            return ( $this->condition['type'] == 'logic' ) ? true : false;
        
        return false;
    }

    /**
     * Get the result of a group of conditions
     *
     * @return bool
     */
    public function groupResult( $group )
    {
        $result = false;
        $l      = 0;
    
        foreach ( $group['conditions'] as $i => $condition )
        {
            $this->condition = (array) $condition;
            
            if ( !$this->isLogic(self::CONDITION) )
                continue;
            
            $prevCondition = $group['conditions'][ ($i - 1) ];
            $prevResult    = (isset($conditionResult)) ? $conditionResult : $this->conditionResult( $prevCondition );

            $nextCondition = $group['conditions'][ ($i + 1) ];
            $nextResult    = $this->conditionResult( $nextCondition );
    
            $logic           = sanitize_text_field( $condition['logic'] );
            $conditionResult = $this->matchLogic( $prevResult, $logic, $nextResult );
    
            $l++;
        }
    
        if ( 0 == $l )
        {
            $condition       = $group['conditions'][0];
            $conditionResult = $this->conditionResult( $condition );
        }
    
        $result = $conditionResult;
        return $result;
    }

    /**
     * Bind two conditions and apply logic
     *
     * @return bool
     */
    public function matchLogic( $condition1 = false, $logic = 'and', $condition2 = false )
    {
        if ($condition1 === false || $condition2 === false)
            return;
        
        if ( 'or' == $logic )
        {
            if ( $condition1 || $condition2 )
                return true;
        }
        elseif ( 'and' == $logic )
        {
            if ( $condition1 && $condition2 )
                return true;
        }

        return false;
    }

    /**
     * Get condition value
     *
     * @return mixed
     */
    public function getValue( $val = false )
    {
        if ( !$val )
            $val = $this->data;
        
        if ( is_array( $val ) )
        {
            if ( isset( $val[0] ) )
            {
                $multiArray = $val;
                $values     = array();

                foreach ( $multiArray as $array )
                    $values[] = $array['value'];

                return $values;
            }
            else
                return $val['value'];
        }
        
        return $val;
    }
    
    /**
     * Get the result of a condition
     *
     * @return bool
     */
    public function conditionResult( $condition )
    {
        $type = sanitize_text_field( $condition['type'] );
        $data = $condition['data'];

        $this->data = $data;

        if ( 'product_category' === $type )
            return $this->categoryExistsInCart();

        elseif ( 'allowed_users' === $type )
            return $this->allowedUsers();

        elseif ( 'disallowed_users' === $type )
            return $this->disallowedUsers();

        elseif ( 'logged_status' === $type )
            return $this->loggedStatus();

        elseif ( 'first_purchase' === $type )
            return $this->firstPurchase();

        elseif ( 'cart_quantity' === $type )
            return $this->cartQuantity();

        elseif ( 'cart_subtotal' === $type )
            return $this->cartSubtotal();

        return apply_filters('ucfw_conditions_logic', false, $this->data, $type);
    }

    /**
     * Set cart data
     *
     * @return bool
     */
    public function setCartData( $data )
    {
        $this->cartData = $data;
    }

    /**
     * Get cart data
     *
     * @return bool
     */
    public function getCartData()
    {
        return $this->cartData;
    }

    /**
     * Check if category exists in cart
     *
     * @return bool
     */
    public function categoryExistsInCart()
    {
        if ( !isset($this->data['categories']) )
            return false;
        
        $userCategories = $this->getValue( $this->data['categories'] );
        $cartCategories = array();

        foreach ( WC()->cart->get_cart_contents() as $cartItem )
        {
            foreach ( $cartItem['data']->get_category_ids() as $catId )
            {
                if ( !isset($cartCategories[$catId]) )
                {
                    $cartCategories[$catId] = $cartItem['quantity'];
                    continue;
                }

                $cartCategories[$catId] += $cartItem['quantity'];
            }
        }

        foreach ( $userCategories as $userCat )
        {
            if ( !isset($cartCategories[$userCat]) )
                return false;
            
            $this->setCartData( $cartCategories[$userCat] );
            
            if ( !$this->compare() )
                return false;
        }

        return true;
    }

    /**
     * Check if user is allowed
     *
     * @return bool
     */
    public function allowedUsers()
    {
        $this->setCartData( get_current_user_id() ? wp_get_current_user()->roles : array('guest') );
        return ( count( array_intersect( $this->getValue(), $this->getCartData() ) ) > 0 ) ? true : false;
    }

    /**
     * Check if user is not allowed
     *
     * @return bool
     */
    public function disallowedUsers()
    {
        $this->setCartData( get_current_user_id() ? wp_get_current_user()->roles : array('guest') );
        return ( count( array_intersect( $this->getValue(), $this->getCartData() ) ) > 0 ) ? false : true;
    }

    /**
     * Match user logged status with condition settings
     *
     * @return bool
     */
    public function loggedStatus()
    {
        $this->setCartData( is_user_logged_in() ? 'yes' : 'no' );
        return ( $this->getValue() === $this->getCartData() ) ? true : false;
    }

    /**
     * Match user first purchase status with condition settings
     *
     * @return bool
     */
    public function firstPurchase()
    {
        if ( 'yes' !== $this->getValue()  )
            return true;
        
        $this->setCartData( wc_get_customer_order_count( get_current_user_id() ) == 0 ? 'yes' : 'no' );
        return ( $this->getValue() === $this->getCartData() ) ? true : false;
    }

    /**
     * Match cart quantity with condition settings
     *
     * @return bool
     */
    public function cartQuantity()
    {
        $cartQuantity = 0;

        foreach ( WC()->cart->get_cart_contents() as $cartItem )
            $cartQuantity += $cartItem['quantity'];
        
        $this->setCartData( $cartQuantity );
        return $this->compare();
    }

    /**
     * Match cart subtotal with condition settings
     *
     * @return bool
     */
    public function cartSubtotal()
    {
        $this->setCartData( (double) WC()->cart->subtotal );
        return $this->compare();
    }

    /**
     * Compare setting
     *
     * @return bool
     */
    public function compare( $value = false )
    {
        $this->compare = ( is_array( $this->data['compare'] ) ) ? $this->data['compare']['value'] : $this->data['compare'];

        if ( !$value )
            $value = $this->getValue();

        switch ( $this->compare )
        {
            case 'mt':
                if ( $this->getCartData() > $value )
                    return true;
                break;
            
            case 'lt':
                if ( $this->getCartData() < $value )
                    return true;
                break;
            
            case 'nt':
                if ( $this->getCartData() != $value )
                    return true;
                break;
            
            default:
                if ( $this->getCartData() == $value )
                    return true;
                break;
        }

        return false;
    }
}
