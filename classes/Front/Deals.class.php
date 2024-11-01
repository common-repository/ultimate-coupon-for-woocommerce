<?php
namespace UCFW\Front;

class Deals
{
    const E_CARTKEY              = false;
    const E_QUANTITY             = 'quantity';
    const E_ADDED_QUANTITY       = 'ucfw_add_quantity';
    const E_VARIATION_GET_PARENT = 258;

    public static $instance;

    private $couponId;
    private $couponCode;

    private $settings;
    private $enabled;
    private $targetSettings;
    private $applySettings;

    private $targetItems;
    private $applyItems;

    private $itemTypes;
    private $targetMatch;
    private $applyType;

    private $targetMatches;
    private $applyTypes;

    private $totalDiscount = 0;

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
     * Constructor
     *
     * @return void
     */
    private function __construct()
    {
        add_action( 'woocommerce_applied_coupon',                  array( $this, 'onCouponApplied' ) );
        add_action( 'woocommerce_cart_item_removed',               array( $this, 'onItemRemoved' ), 9, 2 );
        add_action( 'woocommerce_removed_coupon',                  array( $this, 'onCouponRemoved' ), 10, 1 );
        add_action( 'woocommerce_update_cart_action_cart_updated', array( $this, 'onCartUpdated' ), 10, 1 );
        add_action( 'woocommerce_thankyou',                        array( $this, 'onOrderPlaced' ), 10, 1 );

        add_filter( 'woocommerce_cart_item_subtotal',              array( $this, 'calculateSubtotals' ), 10, 2 );
        add_filter( 'woocommerce_coupon_get_discount_amount',      array( $this, 'updateDiscountAmount' ), 10, 5 );
        add_filter( 'woocommerce_cart_item_class',                 array( $this, 'addApplyItemClass' ), 10, 2 );
    }

    /**
     * Custom class adding after deals applied
     *
     * @return string
     */
    public function addApplyItemClass( $class, $cartItem )
    {
        $itemID = ( !empty($cartItem['variation_id']) ) ? $cartItem['variation_id'] : $cartItem['product_id'];
        $sess   = WC()->session->get( 'ucfw_cart_apply_item_' . $itemID, false );
        
        if ( $sess ) 
            return "{$class} ucfw-deals-cart-apply-item";
        
        return $class;
    }   

    /**
     * Apply on order placed
     *
     * @return void
     */
    public function onOrderPlaced( $orderID )
    { 
        $order   = wc_get_order( $orderID );
        $coupons = $order->get_coupon_codes();
        $userID  = $order->get_user_id();

        foreach( $coupons as $couponCode ) 
            update_user_meta( $userID, '_ucfw_used_coupon_' . $couponCode, 'yes' );
    }

    /**
     * Apply on cart update
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
            $this->setCoupon($code);

            if ( $this->canApplyDeals() )
            {
                $isApplied = WC()->session->get( 'ucfw_cart_coupon_applied_' . $this->couponCode, false );

                if ( !$isApplied )
                    $this->onCouponApplied();
            }
            else
                $this->removeDeals();
        }
        
        return;
    }

    /**
     * Check who can apply deals.
     *
     * @return array
     */
    public function canApplyDeals( $code = null )
    {
        $this->setCoupon($code);

        if ( !$this->enabled )
            return false;

        return $this->hasTargetMatched( $this->getCartItems(), $this->getCartItems( self::E_QUANTITY ), $this->getTargetItems(), $this->getTargetSettings() );
    }

    /**
     * Check if target items exist in cart
     *
     * @return array
     */
    private function hasTargetMatched()
    {
        $matched = false;
        
        $cartItems      = $this->getCartItems();
        $cartQuantities = $this->getCartItems( self::E_QUANTITY );
        $targetSettings = $this->getTargetSettings();
        $targetItems    = $this->getTargetItems();

        if ( !$targetItems )
            return true;

        if ( $targetSettings['match'] === 'all' ) 
        {
            $matched = true;

            foreach ( $targetItems as $targetItem )
            {
                $targetItemID = absint($targetItem['item']['id']);

                if ( !in_array( $targetItemID, $cartItems ) )
                {
                    $matched = false;
                    break;
                }

                $itemQuantity = absint($cartQuantities[ $targetItemID ]);

                if ( absint($targetItem['quantity']['value']) > $itemQuantity )
                {   
                    $matched = false;
                    break;
                }
            } 
        }

        return apply_filters( 'ucfw_deals_has_target_matched', $matched, $cartItems, $cartQuantities, $targetItems, $targetSettings );
    }

    /**
     * Apply if any item removed from cart
     *
     * @return array
     */
    public function onItemRemoved( $cartKey, $instance )
    {
        $cartItem = $instance->removed_cart_contents[$cartKey];
        $itemID   = ( !empty($cartItem['variation_id']) ) ? $cartItem['variation_id'] : $cartItem['product_id'];

        WC()->session->set( 'ucfw_cart_apply_item_' . $itemID, false );

        foreach ( $instance->applied_coupons as $code )
        {
            $this->setCoupon($code);

            if ( !$this->hasTargetMatched() )
            {
                WC()->cart->remove_coupon( $code );
                continue;
            }

            $applyItemIds = $this->getApplyItemIds(); 

            if ( !$applyItemIds )
                continue; 

            $i = 0;

            foreach ( $instance->cart_contents as $item )
            {
                $productID    = $item[ 'product_id' ];
                $isVariation  = boolval( $item['variation_id'] );
                $variationID  = ( $isVariation ) ? $item['variation_id'] : $item['product_id'];
                $cartQuantity = $item[ 'quantity' ];

                if ( !isset( $applyItemIds[$variationID] ) ) 
                    continue;

                $i++;
            }

            if ( 0 == $i )
                WC()->cart->remove_coupon( $code );
        
        }
    }

    /**
     * Get all current items in cart
     *
     * @return array
     */
    private function getCartItems( $type = self::E_CARTKEY )
    {
        $cartItems = array();
        
        foreach( WC()->cart->get_cart() as $cartKey => $item )
        {
            $itemID = ( !empty($item['variation_id']) ) ? $item['variation_id'] : $item['product_id']; 

            if ( $type )
                $cartItems[ $itemID ] = $item[ $type ];
            else
                $cartItems[ $cartKey ] = $itemID;
        }

        return $cartItems;
    }

    /**
     * Apply coupon on cart
     *
     * @return void
     */
    public function onCouponApplied( $code = null )
    {   
        $this->setCoupon( $code );
      
        $userID = wp_get_current_user()->ID;
        $allow  = get_post_meta( $this->couponId, '_ucfw_deals_allow_repeated_use', true );
        $isUsed = get_user_meta( $userID, '_ucfw_used_coupon_' . $this->couponCode, true );

        if ( 'yes' !== $allow && $isUsed )
        {
            wc_add_notice( esc_html__( 'You\'ve already got the deals.', 'ultimate-coupon-for-woocommerce' ), 'error' );
            return;
        }
    
        if ( !in_array( $this->couponCode, WC()->cart->get_applied_coupons() ) )
            return;

        if ( !$this->canApplyDeals() )
            return;
        
        $this->applyDeals();
    }

    /**
     * Apply delas to cart
     *
     * @return void
     */
    private function applyDeals( $code = null )
    {
        $this->setCoupon( $code );
        
        $cartItems      = $this->getCartItems();
        $cartQuantities = $this->getCartItems( self::E_QUANTITY );
        $applyItems     = $this->getApplyItems();
        $applySettings  = $this->getApplySettings();

        if ( $applyItems )
        {
            if ( 'all' === $applySettings['type'] ) 
            {
                foreach ( $applyItems as $item )
                {
                    $parentID    = wp_get_post_parent_id( absint($item['item']['id']) );
                    $isVariation = ( !empty($parentID) ) ? true : false;
                    $itemID      = ( $isVariation ) ? $parentID : absint($item['item']['id']);
                    $product     = wc_get_product( $itemID );
                    $variationId = ( $isVariation ) ? absint($item['item']['id']) : '';
                    $variation   = ( $isVariation ) ? wc_get_product_variation_attributes( absint($item['item']['id']) ) : array();

                    if ( !$product->get_id() )
                        continue;
                    
                    $addQuantity = absint($item['quantity']['value']);
                    
                    if ( isset( $cartQuantities[ absint($item['item']['id']) ] ) )
                    {
                        $cartQuantity  = $cartQuantities[ absint($item['item']['id']) ];
                        $cartQuantity -= $this->getTargetItemQuantity( absint($item['item']['id']) );
                        
                        if ( $addQuantity >= $cartQuantity )
                            $addQuantity -= $cartQuantity;
                    }

                    WC()->session->set( 'ucfw_cart_item_' . $product->get_id() . '_' . $variationId . '_add_quantity', $addQuantity );
                    WC()->session->set( 'ucfw_cart_apply_item_' . absint($item['item']['id']), true );
                    WC()->cart->add_to_cart( $product->get_id(), $addQuantity, $variationId, $variation );
                }
            }

            do_action( 'ucfw_deals_apply', $cartItems, $cartQuantities, $applyItems, $applySettings );

            WC()->session->set( 'ucfw_cart_coupon_applied_' . $this->couponCode, 'yes' );
            wc_add_notice( esc_html__( 'Congratulations! You got a new deal.', 'ultimate-coupon-for-woocommerce' ), 'success' );
        }
    }

    /**
     * Get apply item id
     *
     * @return array
     */
    private function getApplyItemIds( $type = false ) 
    {
        $applyItems = $this->getApplyItems();

        if ( !$applyItems ) 
            return array();

        $ids = array();

        foreach ( $applyItems as $item ) 
        {
            if ( self::E_VARIATION_GET_PARENT === $type )
            {
                $parentID    = wp_get_post_parent_id( absint($item['item']['id']) );
                $isVariation = ( !empty($parentID) ) ? true : false;
                $itemID      = ( $isVariation ) ? $parentID : absint($item['item']['id']);
                $variationId = ( $isVariation ) ? absint($item['item']['id']) : '';

                $ids[ $itemID ] = array(
                    'quantity'  => absint($item['quantity']['value']),
                    'variation' => $variationId
                );
                continue;
            }

            $ids[ $item['item']['id'] ] = array(
                'quantity'  => absint($item['quantity']['value']),
                'variation' => ''
            );
        }

        return $ids;
    }

    /**
     * Get target item
     *
     * @return mixed
     */
    private function getTargetItem( int $id ) 
    {
        $targetItems = $this->getTargetItems();

        if ( !$targetItems ) 
            return false;

        $targetItem = false;

        foreach ( $targetItems as $item ) 
        {
            if ( $id === absint($item['item']['id']) ) 
            {
                $targetItem = $item;
                break;
            }
        }

        return $targetItem;
    }

    /**
     * Get target item quantity
     *
     * @return int
     */
    private function getTargetItemQuantity( int $id ) 
    {
        $targetItem = $this->getTargetItem( $id );

        if ( !$targetItem )
            return 0;

        if ( !isset($targetItem['quantity']['value']) )
            return 0;

        return absint($targetItem['quantity']['value']);
    }

    /**
     * Get apply item
     *
     * @return mixed
     */
    private function getApplyItem( int $id ) 
    {
        $applyItems = $this->getApplyItems();

        if ( !$applyItems ) 
            return false;

        $applyItem = false;

        foreach ( $applyItems as $item ) 
        {
            if ( $id === absint($item['item']['id']) ) 
            {
                $applyItem = $item;
                break;
            }
        }

        return $applyItem;
    }

    /**
     * Apply discount on amount
     *
     * @return mixed
     */
    public function updateDiscountAmount( $discount, $totalPrice, $item, $single, $coupon )
    {
        $this->couponId = $coupon->get_id();
        $this->setSettings();

        if ( !$this->canApplyDeals() )
            return $discount;

        $applyItems   = $this->getApplyItems();
        $isVariation  = boolval( $item['variation_id'] );
        $variationID  = ( $isVariation ) ? $item['variation_id'] : $item['product_id'];
        $applyItemIds = array_keys( $this->getApplyItemIds() );

        $discount = $coupon->get_discount_type() === 'fixed_cart' ? floatval($coupon->get_amount()) / ( count(WC()->cart->get_cart_contents()) - count($applyItems) ) : $discount;

        if ( !in_array( $variationID, $applyItemIds ))
            return $discount;

        $itemPrice  = $item['data']->get_price();
        $applyItem  = $this->getApplyItem( $variationID );

        if ( !$applyItem ) 
            return $discount;

        switch( $applyItem['discount']['type'] )
        {
            case 'fixed':
                $discount = absint($applyItem['discount']['value']) * absint($applyItem['quantity']['value']);
                break;
            case 'percent':
                $discount = $itemPrice * ( absint($applyItem['discount']['value']) / 100 ) * absint($applyItem['quantity']['value']);
                break;
            default: 
                $discount = ($itemPrice - absint($applyItem['discount']['value'])) * absint($applyItem['quantity']['value']);
                break;
        }

        return $discount;
    }

    /**
     * Updating subtotal of cart items
     *
     * @return integer
     */
    public function calculateSubtotals( $price, $item )
    {
        $appliedCoupons = WC()->cart->get_applied_coupons();

        if ( empty( $appliedCoupons ) )
            return $price;

        $appliedCoupons = array_values($appliedCoupons);
        $this->setCoupon( $appliedCoupons[0] );
        
        if( !$this->canApplyDeals() )
            return $price;

        $applyItems  = $this->getApplyItems();
        $isVariation = boolval( $item['variation_id'] );
        $variationID = ( $isVariation ) ? $item['variation_id'] : $item['product_id'];

        if ( !in_array( $variationID, array_keys( $this->getApplyItemIds() ) ))
            return $price;
        
        $itemPrice   = $item['data']->get_price();
        $totalPrice  = $itemPrice * $item['quantity'];
        $applyItem   = $this->getApplyItem( $variationID );
        $maxQuantity = ( $item['quantity'] > absint($applyItem['quantity']['value']) ) ? absint($applyItem['quantity']['value']) : $item['quantity'];

        if ( !$applyItem ) 
            return $price;

        switch( $applyItem['discount']['type'] )
        {
            case 'fixed':
                $discountPrice = absint($applyItem['discount']['value']) * $maxQuantity;
                $newPrice      = $totalPrice - $discountPrice;
                break;

            case 'percent':                    
                $discountPrice = $itemPrice * ( absint($applyItem['discount']['value']) / 100 ) * $maxQuantity;
                $newPrice      = $totalPrice - $discountPrice;
                break;

            default: 
                $discountPrice = ($itemPrice - absint($applyItem['discount']['value'])) * $maxQuantity;
                $newPrice      = $totalPrice - $discountPrice;
                break;
        }

        if ( $newPrice < 0 )
            $newPrice = 0;

        $currencySymbol = get_woocommerce_currency_symbol();
        $output         = '<del><span>';
        $output        .= $currencySymbol . number_format( floatval( $totalPrice ), 2, '.', '' );
        $output        .= '</span></del>';  
        $output        .= '<span>';  
        $output        .= $currencySymbol . number_format( floatval( $newPrice ), 2, '.', '' );
        $output        .= '</span>';

        return $output;
    }
    
    /**
     * Remove coupon from cart
     *
     * @return void
     */
    public function onCouponRemoved( $code = null )
    {
        $this->setCoupon( $code );
        $appliedCoupons = WC()->cart->get_applied_coupons();   

        if ( !in_array( $this->couponCode, $appliedCoupons ) )
            $this->removeDeals();
    }

    /**
     * Remove deals from cart
     *
     * @return void
     */
    public function removeDeals( $code = null )
    {
        $this->setCoupon( $code );

        $appliedCoupons = WC()->cart->get_applied_coupons();   
        $applyItems     = $this->getApplyItems();
        $cartItems      = $this->getCartItems();
        $cartQuantities = $this->getCartItems( self::E_QUANTITY );

        if ( !$applyItems || !is_array($applyItems) )
            return;

        foreach ( $applyItems as $item )
        {
            $itemHash = array_search( absint($item['item']['id']), $cartItems );

            if ( !$itemHash )
                continue;
                
            $parentID    = wp_get_post_parent_id( absint($item['item']['id']) );
            $isVariation = ( !empty($parentID) ) ? true : false;
            $itemID      = ( $isVariation ) ? $parentID : absint($item['item']['id']);
            $variationId = ( $isVariation ) ? absint($item['item']['id']) : '';

            $addQuantity  = WC()->session->get( 'ucfw_cart_item_' . $itemID . '_' . $variationId . '_add_quantity', 0);
            $cartQuantity = $cartQuantities[ absint($item['item']['id']) ] - $addQuantity;
            
            WC()->session->set( 'ucfw_cart_apply_item_' . absint($item['item']['id']), false );
            WC()->cart->set_quantity( $itemHash, $cartQuantity, true );
        }

        WC()->session->set( 'ucfw_cart_coupon_applied_' . $this->couponCode, null );
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getTargetSettings()
    {
        return $this->targetSettings;
    }

    public function getApplySettings()
    {
        return $this->applySettings;
    }

    public function getTargetItems()
    {
        return $this->targetItems;
    }

    public function getApplyItems()
    {
        return $this->applyItems;
    }

    public function getItemType($section = false)
    {
        if ( !is_array( $this->getSettings() ) || !$section )
            return false;
            
        $settings = $this->getSettings();

        if ( isset($settings[$section]['item_type']) )
            return esc_html($settings[$section]['item_type']);
        
        return false;
    }

    public function getTargetMatch()
    {
        return $this->targetMatch;
    }

    public function getApplyType()
    {
        return $this->applyType;
    }

    /**
     * Set settings
     *
     * @return void
     */
    public function setSettings()
    {
        $this->settings       = false;
        $this->enabled        = false;
        $this->targetSettings = false;
        $this->applySettings  = false;
        $settings             = get_post_meta( $this->couponId, '_ucfw_deals', true );

        if ( isset($settings[0]) && is_array($settings[0]) )
        {
            $this->settings = $settings[0];
            $this->enabled  = $this->settings['enabled'] === 'yes' ? true : false;

            if ( isset($this->settings['target']) && is_array($this->settings['target']) )
            {
                $this->targetSettings = $this->settings['target'];
                $this->targetMatch    = $this->targetSettings['match'];
                $this->targetItems    = $this->targetSettings['items'];
            }
            
            if ( isset($this->settings['apply']) && is_array($this->settings['apply']) )
            {
                $this->applySettings = $this->settings['apply'];
                $this->applyType     = $this->applySettings['type'];
                $this->applyItems    = $this->applySettings['items'];
            }
        }
    }

    private function setCoupon( $code = null )
    {
        if ( $code == null || $this->couponCode == $code )
            return;
        
        $coupon           = new \WC_Coupon( sanitize_text_field($code) );
        $this->couponId   = $coupon->get_id();
        $this->couponCode = sanitize_text_field($code);
        $this->setSettings();
    }
}
