<?php
namespace UCFW\Admin;

class Deals
{
    public static $instance;

    private $couponId;
    private $settings;

    private $itemTypes;
    private $targetMatches;
    private $applyTypes;

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
        $this->couponId = ( isset($_GET['post']) ) ? absint($_GET['post']) : 0;

        $this->setSettings();
        $this->setItemTypes();
        $this->setTargetMatches();
        $this->setApplyTypes();
        $this->setSaveUrl();
        $this->setSearchUrl();

        add_action( 'admin_footer', array( $this, 'footerJs' ), PHP_INT_MAX );
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getEnabled($n = 0)
    {
        if ( !isset($this->settings[$n]) )
            return false;

        $setting = $this->settings[$n];
        
        if ( isset($setting['enabled']) )
            return ($setting['enabled'] === 'yes') ? 'yes' : 'no';

        return false;
    }

    public function getAllowRepeatedUse($n = 0)
    {
        if ( !isset($this->settings[$n]) )
            return false;

        $setting = $this->settings[$n];
        
        if ( isset($setting['allow_repeated_use']) )
            return ($setting['allow_repeated_use'] === 'yes') ? 'yes' : 'no';

        return false;
    }

    public function getTargetSettings($n = 0)
    {
        if ( !isset($this->settings[$n]) )
            return false;

        $setting = $this->settings[$n];
        
        if ( isset($setting['target']) && is_array($setting['target']) )
            return (array) $setting['target'];

        return false;
    }

    public function getApplySettings($n = 0)
    {
        if ( !isset($this->settings[$n]) )
            return false;

        $setting = $this->settings[$n];
        
        if ( isset($setting['apply']) && is_array($setting['apply']) )
            return (array) $setting['apply'];

        return false;
    }

    public function getItemTypes( $section = false )
    {
        if ( !is_array($this->itemTypes) )
            return false;
        
        $itemTypes = array();
        foreach ( $this->itemTypes as $type => $item )
        {
            if ( $item['premiumholder'] )
            {
                $itemTypes[] = array(
                    'text'     => esc_html( $item['name'] ) . ' (' . esc_html__( 'PREMIUM', 'ultimate-coupon-for-woocommerce' ) . ')',
                    'value'    => 'premium',
                    'selected' => false
                );
            }
            else
            {
                $itemTypes[] = array(
                    'text'     => esc_html( $item['name'] ),
                    'value'    => esc_attr( $type ),
                    'selected' => ( $section && $type === $this->getItemType($section) ) ? true : false
                );
            }
        }

        return $itemTypes;
    }

    public function getItemType( $section = false )
    {
        if ( !is_array($this->getSettings()) || !$section )
            return false;
        
        $settings = $this->getSettings();

        if ( isset($settings[$section]['item_type']) )
            return $settings[$section]['item_type'];
        
        return false;
    }

    public function getTargetMatch($n = 0)
    {
        if ( !isset($this->settings[$n]) )
            return null;

        $setting = $this->settings[$n];
        
        if ( isset($setting['target']['match']) )
            return $setting['target']['match'];

        return null;
    }

    public function getTargetMatches()
    {
        if ( !is_array($this->targetMatches) )
            return false;
        
        $targetMatches = array();
        foreach ( $this->targetMatches as $type => $match )
        {
            if ( $match['premiumholder'] )
            {
                $targetMatches[] = array(
                    'text'     => esc_html( $match['name'] ) . ' (' . esc_html__( 'PREMIUM', 'ultimate-coupon-for-woocommerce' ) . ')',
                    'value'    => 'premium',
                    'selected' => false
                );
            }
            else
            {
                $targetMatches[] = array(
                    'text'     => esc_html( $match['name'] ),
                    'value'    => esc_attr( $type ),
                    'selected' => ( $this->getTargetMatch() === $type ) ? true : false
                );
            }
        }

        return $targetMatches;
    }

    public function getApplyType($n = 0)
    {
        if ( !isset($this->settings[$n]) )
            return null;

        $setting = $this->settings[$n];

        if ( isset($setting['apply']['type']) )
            return $setting['apply']['type'];

        return null;
    }

    public function getApplyTypes()
    {
        if ( !is_array($this->applyTypes) )
            return false;
        
        $applyTypes = array();
        foreach ( $this->applyTypes as $type => $apply )
        {
            if ( $apply['premiumholder'] )
            {
                $applyTypes[] = array(
                    'text'     => esc_html( $apply['name'] ) . ' (' . esc_html__( 'PREMIUM', 'ultimate-coupon-for-woocommerce' ) . ')',
                    'value'    => 'premium',
                    'selected' => false
                );
            }
            else
            {
                $applyTypes[] = array(
                    'text'     => esc_html( $apply['name'] ),
                    'value'    => esc_attr( $type ),
                    'selected' => ( $this->getApplyType() === $type ) ? true : false
                );
            }
        }
        
        return $applyTypes;
    }

    public function getSaveUrl()
    {
        return esc_url( $this->saveUrl );
    }

    public function getSearchUrl()
    {
        return esc_url( $this->searchUrl );
    }

    public function setSettings()
    {
        $this->settings = false;
        $settings       = get_post_meta( $this->couponId, '_ucfw_deals', true );

        if ( isset($settings) && is_array($settings) )
            $this->settings = (array) $settings;
    }

    public function setItemTypes()
    {
        $this->itemTypes = apply_filters( 'ucfw_deals_item_types', array(
            'products' => array(
                'name'          => esc_html__( 'Products', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => false
            )
        ) );
    }

    public function setTargetMatches()
    {
        $this->targetMatches = apply_filters( 'ucfw_deals_target_matches', array(
            'all' => array(
                'name'          => esc_html__( 'Match All', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => false
            ),
            'any' => array(
                'name'          => esc_html__( 'Match Any', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            )
        ) );
    }

    public function setApplyTypes()
    {
        $this->applyTypes = apply_filters( 'ucfw_deals_apply_types', array(
            'all' => array(
                'name'          => esc_html__( 'Apply All', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => false
            ),
            'cheapest' => array(
                'name'          => esc_html__( 'Apply Cheapest', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            ),
            'random' => array(
                'name'          => esc_html__( 'Apply Random', 'ultimate-coupon-for-woocommerce' ),
                'premiumholder' => true
            )
        ) );
    }

    public function setSaveUrl()
    {
        $couponId      = ( isset( $_GET['post'] ) ) ? absint($_GET['post']) : 0;
        $this->saveUrl = get_site_url() . '/wp-json/' . \UCFW\Api::_namespace . '/' . \UCFW\Api::_base . '/deals/' . $couponId;
    }

    public function setSearchUrl()
    {
        $this->searchUrl = get_site_url() . '/wp-json/' . \UCFW\Api::_namespace . '/' . \UCFW\Api::_base . '/search';
    }

    /**
     * Render conditions js code in footer
     *
     * @return void
     */
    public function footerJs()
    {
        if ( get_current_screen()->id === 'shop_coupon' )
            include_once UCFW_RENDER_ADMIN . '/js/deals.js.php';
    }

    /**
     * Process and save settings
     *
     * @return mixed array|boolean
     */
    public static function processSettings( $settings )
    {
        if ( !is_array($settings) || empty($settings) )
            return false;

        $sets = array();
        
        foreach ( $settings as $setting )
        {
            $enabled = (isset($setting['enabled']) && $setting['enabled'] === 'yes') ? 'yes' : 'no';
            $allow   = (isset($setting['allow_repeated_use']) && $setting['allow_repeated_use'] === 'yes') ? 'yes' : 'no';
            $target  = array();
            $apply   = array();

            if ( isset($setting['target']) && is_array($setting['target']) )
            {
                $target['item_type'] = ( isset( $setting['target']['item_type'] ) ) ? sanitize_text_field( $setting['target']['item_type'] ) : '';
                $target['match']     = ( isset( $setting['target']['match'] ) )     ? sanitize_text_field( $setting['target']['match'] ) : '';
                $targetItems         = array();

                if ( isset($setting['target']['items']) && is_array($setting['target']['items']) )
                {
                    foreach ( $setting['target']['items'] as $item )
                    {
                        if ( empty($item['item']) )
                            continue; 

                        $itemX         = explode('|', $item['item']);
                        $targetItems[] = array(
                            'item'     => array(
                                'id'   => absint( $itemX[0] ),
                                'name' => sanitize_text_field( $itemX[1] )
                            ),
                            'quantity' => array(
                                'value' => absint($item['quantity']['value'])
                            )
                        );
                    }
                }

                $target['items'] = $targetItems;
            }

            if ( isset($setting['apply']) && is_array($setting['apply']) )
            {
                $apply['item_type'] = ( isset( $setting['apply']['item_type'] ) ) ? sanitize_text_field( $setting['apply']['item_type'] ) : '';
                $apply['type']      = ( isset( $setting['apply']['type'] ) )      ? sanitize_text_field( $setting['apply']['type'] )      : '';
                $applyItems         = array();

                if ( isset($setting['apply']['items']) && is_array( $setting['apply']['items'] ) )
                {
                    foreach ($setting['apply']['items'] as $item)
                    {
                        if ( empty( $item['item'] ) )
                            continue; 
                        
                        $itemX     = explode('|', $item['item']);
                        $applyItems[] = array(
                            'item'  => array(
                                'id'   => absint($itemX[0]),
                                'name' => sanitize_text_field($itemX[1])
                            ),
                            'quantity' => array(
                                'value' => absint($item['quantity']['value'])
                            ),
                            'discount' => array(
                                'type'  => sanitize_text_field($item['discount']['type']),
                                'value' => absint($item['discount']['value'])
                            )
                        );
                    }
                }

                $apply['items'] = $applyItems;
            }

            $sets[] = array(
                'enabled'            => $enabled,
                'allow_repeated_use' => $allow,
                'target'             => $target,
                'apply'              => $apply
            );
        }

        return $sets;
    }
}
