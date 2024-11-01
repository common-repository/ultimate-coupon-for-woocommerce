<?php
namespace UCFW\Admin;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Fields
{
    public static $instance;
    private $helper;

    public $singleFields;
    public $globalFields;

    public static function getInstance()
    {
        if ( !self::$instance instanceof self )
            self::$instance = new self();
        
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->helper = \UCFW\Helper::getInstance();

        add_action( 'carbon_fields_register_fields',   array( $this, 'globalFields' ) );
        add_action( 'carbon_fields_register_fields',   array( $this, 'singleCoupon' ) );
        add_action( 'admin_menu',                      array( $this, 'globalSettingSubmenu' ) );
        add_action( 'save_post',                       array( $this, 'saveCoupon' ) );
    }

    /**
     * Buy premium action
     *
     * @return array
     */
    public function buyPremium()
    {
        return '<span class="ucfw-buy-premium">' . esc_html__( 'Buy Premium', 'ultimate-coupon-for-woocommerce' ) . '</span>';
    }

    /**
     * Premium placeholder
     *
     * @return array
     */
    public function premiumPlaceholder()
    {
        return esc_attr__( 'Premium Feature', 'ultimate-coupon-for-woocommerce' );
    }

    /**
     * Single settings
     *
     * @return void
     */
    public function singleCoupon()
    {
        $postID       = ( isset($_GET['post']) ) ? absint($_GET['post']) : 0;
        $referralLink = '';
 
        // Share via url logic
        if ( $postID > 0 )
        {
            $customCode   = get_post_meta( $postID, '_ucfw_url_custom_code', true);
            $defaultCode  = isset( get_post($postID)->post_title ) ? esc_html( get_post( $postID )->post_title ) : '';
            $referralLink = ( !empty($customCode) ) ? esc_url(get_site_url(). '/coupon/' . strtolower($customCode)) : esc_url(get_site_url(). '/coupon/' . strtolower($defaultCode));
        }

        $this->singleFields = Container::make( 'post_meta', esc_html__( 'Ultimate Coupon Settings', 'ultimate-coupon-for-woocommerce' ) );
        $this->singleFields->where( 'post_type', '=', 'shop_coupon' );

        /**
         * Deals
         */
        $this->singleFields->add_tab( esc_html__( 'Deals', 'ultimate-coupon-for-woocommerce' ), array(

            Field::make( 'html', 'ucfw_deals_html')
                ->set_html( '<div id="ucfw-deals-setting">
                    <div class="ucfw-loader">
                        <div class="ucfw-loader-circle"></div>
                    </div>
                    <div id="ucfw-deals-0">
                        <div class="ucfw-deals-enabled-container">
                                <input type="checkbox" value="yes" name="_ucfw_deals[0][enabled]" id="ucfw-deals-0-enabled-checkbox">
                                <label for="ucfw-deals-0-enabled-checkbox">' . esc_html__( 'Enable Deals', 'ultimate-coupon-for-woocommerce' ) . '</label>
                                <p class="description">' . esc_html__( 'Check this box to turn on all deals settings for this coupon.', 'ultimate-coupon-for-woocommerce' ) . '</p>
                        </div>
                        <div class="ui divider"></div>
                        <div class="ucfw-deals-allow-repeated-use-container">
                                <input type="checkbox" value="yes" name="_ucfw_deals[0][allow_repeated_use]" id="ucfw-deals-0-allow-repeated-use-checkbox">
                                <label for="ucfw-deals-0-allow-repeated-use-checkbox">' . esc_html__( 'Allow Repeated Use', 'ultimate-coupon-for-woocommerce' ) . '</label>
                                <p class="description">' . esc_html__( 'Check if you want users to be able to avail these deals not just once but every time the coupon is used.', 'ultimate-coupon-for-woocommerce' ) . '</p>
                        </div>
                        <div class="ui divider"></div>
                        <div class="ucfw-deals-target-container"> 
                            <h4 class="ui header">' . esc_html__( 'Target Items', 'ultimate-coupon-for-woocommerce' ) . '</h4>
                            <div class="ucfw-deals-target-type">
                                <select id="ucfw-deals-target-item-types" class="ucfw-deals-item-types ui dropdown" name="_ucfw_deals[0][target][item_type]" data-section="target" data-item-type="products"></select>
                                &nbsp;<span class="ucfw-valign-middle ucfw-color-gray ucfw-font-s16 dashicons dashicons-arrow-right-alt"></span>&nbsp;
                                <select id="ucfw-deals-target-match" class="ui dropdown" name="_ucfw_deals[0][target][match]"></select>
                            </div>
                            <div class="ucfw-deals-types-description">
                                <ul>
                                    <li>' . esc_html__( 'Match All – If all target', 'ultimate-coupon-for-woocommerce' ) . '<span class="ucfw-deals-target-items-type ucfw-text-lowercase">' . esc_html__( 'products', 'ultimate-coupon-for-woocommerce' ) . '</span>' . esc_html__( ' with specified quantity exist in cart, trigger the deal.', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                    <li>' . esc_html__( 'Match Any – If any of the target', 'ultimate-coupon-for-woocommerce' ) . ' <span class="ucfw-deals-target-items-type ucfw-text-lowercase">' . esc_html__( 'products', 'ultimate-coupon-for-woocommerce' ) . '</span> ' . esc_html__( 'match with specified quantity exist in cart, trigger the deal.', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                </ul>
                            </div>
                            <div class="ui divider"></div>
                            <div id="ucfw-target-items-group">
                                <table class="ui compact celled table">
                                    <thead>
                                        <tr>
                                            <th class="ucfw-deals-target-item-type">' . esc_html__( 'Product', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                            <th>' . esc_html__( 'Quantity', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                            <th>' . esc_html__( 'Action', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ucfw-deals-target-items"></tbody>
                                </table>
                                <div id="ucfw-deals-btn-add-target-item" class="ui labeled button" tabindex="0">
                                    <div class="ui teal button">
                                        <i class="add icon"></i>
                                    </div>
                                    <a class="ui basic teal left pointing label">' . esc_html__( 'Add', 'ultimate-coupon-for-woocommerce' ) . '&nbsp;<span class="ucfw-deals-target-item-type">' . esc_html__( 'Product', 'ultimate-coupon-for-woocommerce' ) . '</span></a>
                                </div>
                            </div>
                        </div>
                        <div class="ui divider"></div>
                        <div class="ucfw-deals-apply-container">
                            <h4 class="ui header">' . esc_html__( 'Apply Items', 'ultimate-coupon-for-woocommerce' ) . '</h4>
                            <div class="ucfw-deals-apply-type">
                                <select id="ucfw-deals-apply-item-types" class="ucfw-deals-item-types ui dropdown" name="_ucfw_deals[0][apply][item_type]" data-section="apply" data-item-type="products"></select>
                                &nbsp;<span class="ucfw-valign-middle ucfw-color-gray ucfw-font-s16 dashicons dashicons-arrow-right-alt"></span>&nbsp;
                                <select id="ucfw-deals-apply-types" class="ui dropdown" name="_ucfw_deals[0][apply][type]"></select>
                            </div>
                            <div class="ucfw-deals-types-description">
                                <ul>
                                    <li>' . esc_html__( 'Apply All – Apply all of the', 'ultimate-coupon-for-woocommerce' ) . '<span class="ucfw-deals-target-item-type ucfw-text-lowercase">'. esc_html__('product', 'ultimate-coupon-for-woocommerce') .'</span>' . esc_html__( 'items in the list with specified quantity if deal is triggered.', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                    <li>' . esc_html__( 'Apply Cheapest – Apply only the cheapest ', 'ultimate-coupon-for-woocommerce' ) . '<span class="ucfw-deals-target-item-type ucfw-text-lowercase">' . esc_html__( 'products', 'ultimate-coupon-for-woocommerce' ) . '</span>' . esc_html__( ' item from the list with specified quantity if deal is triggered.', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                    <li>' . esc_html__( 'Apply Random – Apply a random ', 'ultimate-coupon-for-woocommerce' ) . '<span class="ucfw-deals-target-item-type ucfw-text-lowercase">' . esc_html__( 'products', 'ultimate-coupon-for-woocommerce' ) . '</span> ' . esc_html__( 'item from the list with specified quantity if deal is triggered.', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                </ul>
                            </div>
                            <div class="ui divider"></div>
                            <div id="ucfw-apply-items-group">
                                <table class="ui compact celled table">
                                    <thead>
                                        <tr>
                                            <th class="ucfw-deals-apply-item-type">' . esc_html__( 'Product', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                            <th>' . esc_html__( 'Quantity', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                            <th>' . esc_html__( 'Discount', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                            <th>' . esc_html__( 'Action', 'ultimate-coupon-for-woocommerce' ) . '</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ucfw-deals-apply-items"></tbody>
                                </table>
                                <div id="ucfw-deals-btn-add-apply-item" class="ui labeled button" tabindex="0">
                                    <div class="ui teal button">
                                        <i class="add icon"></i>
                                    </div>
                                    <a class="ui basic teal left pointing label">' . esc_html__( 'Add', 'ultimate-coupon-for-woocommerce' ) . '&nbsp;<span class="ucfw-deals-apply-item-type">' . esc_html__( 'Product', 'ultimate-coupon-for-woocommerce' ) . '</span></a>
                                </div>
                                <button id="ucfw-btn-save-deals" class="blue ui button">' . esc_html__( 'Save', 'ultimate-coupon-for-woocommerce' ) . '</button>
                            </div>
                        </div>
                    </div>
                </div>' ),
        ) );

        /**
         * Conditions
         */
        $this->singleFields->add_tab( esc_html__( 'Conditions', 'ultimate-coupon-for-woocommerce' ), array(

            Field::make( 'html', 'ucfw_conditions_html' )
            ->set_html( '<div id="ucfw-conditions-container">
                <div id="ucfw-conditions-enabled-container">
                    <input type="checkbox" value="yes" name="_ucfw_conditions_enabled" id="ucfw-conditions-enabled-checkbox">
                    <label for="ucfw-conditions-enabled-checkbox">' . esc_html__( 'Enable Conditions', 'ultimate-coupon-for-woocommerce' ) . '</label>
                    <p class="description">' . esc_html__( 'Check this box to turn on all conditions settings for this coupon.', 'ultimate-coupon-for-woocommerce' ) . '</p>
                </div>
                <div class="ui divider"></div>
                <div class="ui form">
                    <div id="ucfw-conditional-settings">
                        <div id="ucfw-groups"></div>
                    </div>
                    <div class="ucfw-conditions-bottom">
                        <div id="ucfw_btn_add_group" class="ui labeled button" tabindex="0">
                            <div class="ui teal button">
                                <i class="add icon"></i>
                            </div>
                            <a class="ui basic teal left pointing label">' . esc_html__( 'Add Group', 'ultimate-coupon-for-woocommerce' ) . '</a>
                        </div>
                        <button id="ucfw-btn-save-conditions" class="blue ui button">' . esc_html__( 'Save', 'ultimate-coupon-for-woocommerce' ) . '</button>
                    </div>
                </div>
            </div>' ),

        ));

        /**
         * Scheduling
         */
        $this->singleFields->add_tab( esc_html__( 'Schedule', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_fields_schedule', array(

            Field::make( 'checkbox', 'ucfw_schedule_enabled', esc_html__( 'Enable Schedule', 'ultimate-coupon-for-woocommerce' ) )
                ->set_option_value( 'yes' )
                ->set_help_text( esc_html__( 'Check this box to turn on all schedule settings for this coupon.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'date_time', 'ucfw_schedule_start_date', esc_html__( 'Starts From', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_help_text( esc_html__( 'The starting date and time from which the coupon can be applicable.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'date_time', 'ucfw_schedule_expire_date', esc_html__( 'Expires On', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_help_text( esc_html__( 'The expire date and time after which the coupon can no longer be used.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'text', 'ucfw_schedule_active_from', esc_html__( 'Daily Active Hours (Start)', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'The starting time from when the couple can be applied each day', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'text', 'ucfw_schedule_active_till', esc_html__( 'Daily Active Hours (End)', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'The ending time after when the couple cannot be applied for the day', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'text', 'ucfw_schedule_inactive_days', esc_html__( 'Inactive Days', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'Days in a week when the coupon cannot be applied.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'html', 'ucfw_schedule_buy_premium')
                ->set_html( $this->buyPremium() )

        ) ) );

        /**
         * Share via url
         */
        $this->singleFields->add_tab( esc_html__( 'URL', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_fields_url', array(

            Field::make( 'checkbox', 'ucfw_url_enabled', esc_html__( 'Enable URL', 'ultimate-coupon-for-woocommerce' ) )
                ->set_option_value( 'yes' )
                ->set_help_text( esc_html__('Check this box to turn on all URL settings for this coupon.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'html', 'ucfw_url_link', esc_html__( 'Link', 'ultimate-coupon-for-woocommerce' ) )
                ->set_html('
                    <label><strong>' . esc_html__( 'Link', 'ultimate-coupon-for-woocommerce' ) .'</strong></label>
                    <input type="text" class="cf-text__input" value="' . esc_url( $referralLink ) . '" readonly>
                ')
                ->set_help_text( esc_html__('Use this link to directly shop with this coupon.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'text', 'ucfw_url_custom_code', esc_html__( 'Custom Code', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__('Use this link to directly shop with this coupon.', 'ultimate-coupon-for-woocommerce' ) ),
            
            Field::make( 'html', 'ucfw_url_buy_premium' )
                ->set_html( $this->buyPremium() )

        ) ) );
        
        /**
         * User restrictions
         */
        $this->singleFields->add_tab( esc_html__( 'Restrictions', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_fields_restrictions', array(

            Field::make( 'checkbox', 'ucfw_restriction_enabled', esc_html__( 'Enable Restriction', 'ultimate-coupon-for-woocommerce' ) )
                ->set_option_value( 'yes' )
                ->set_help_text( esc_html__('Check this box to turn on all restriction settings for this coupon.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'select', 'ucfw_restriction_user_allow_type', esc_html__( 'Allow/Disallow', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->add_options( array(
                    'allowed'  => esc_html__( 'Allow User', 'ultimate-coupon-for-woocommerce' ),
                    'disallow' => esc_html__( 'Disallow User', 'ultimate-coupon-for-woocommerce' ),
                ) ),

            Field::make( 'multiselect', 'ucfw_restriction_user_roles', esc_html__( 'User Roles', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->add_options( $this->helper->getUserRoles( true ) ),

            Field::make( 'text', 'ucfw_restriction_shipping', esc_html__( 'Shipping Method Restriction', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'Shipping methods that cannot be used with this coupon.', 'ultimate-coupon-for-woocommerce' ) ),
            
            Field::make( 'text', 'ucfw_restriction_payment', esc_html__( 'Payment Method Restriction', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute('readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'Payment methods that cannot be used with this coupon.', 'ultimate-coupon-for-woocommerce' ) ),
            
            Field::make( 'html', 'ucfw_restriction_buy_premium' )
                ->set_html( $this->buyPremium() )
          
        ) ) );

        /**
         * Additionals
         * 
         */
        $this->singleFields->add_tab( esc_html__( 'Additionals', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_fields_additionals', array(

            Field::make( 'text', 'ucfwp_shipping_fee', esc_html__( 'Shipping Fee', 'ultimate-coupons-for-woocommerce-premium' ) )
                ->set_attribute('readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'Shipping fee amount to override.', 'ultimate-coupon-for-woocommerce' ) ),
 
            Field::make( 'text', 'ucfwp_max_discount', esc_html__( 'Maximum Discount', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute('readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'Maximum discount amount this coupon can provide. Works with percentage discount type.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'html', 'ucfw_additionals_buy_premium' )
                ->set_html( $this->buyPremium() )
          
        ) ) );
    }

    /**
     * Global settings
     *
     * @return void
     */
    public function globalFields()
    {   
        $this->globalFields = Container::make( 'theme_options', 'ucfw_global_settings', esc_html__( 'UCFW Settings', 'ultimate-coupon-for-woocommerce' ) );
        $this->globalFields->set_page_menu_position( 58 );
        $this->globalFields->set_icon( esc_url( UCFW_RESOURCES . '/images/ucfw-icon.png' ) );

        /**
         * Get started
         */
        Container::make( 'theme_options', esc_html__( 'Get Started', 'ultimate-coupon-for-woocommerce' ) )
            ->set_page_parent( $this->globalFields )
            ->set_page_menu_position( 1 )
            ->add_fields( array(
                Field::make( 'html', 'ucfw_global_get_started_wrap' )
                ->set_html( '<div id="ucfw_get_started">
                    <div class="ucfw-admin-card ucfw-hero">
                        <div class="ucfw-admin-card-section">
                            <img class="ucfw-logo" src="' . esc_url( UCFW_RESOURCES . '/images/UCFW-Logo.png' ) . '">
                            <h3>' . esc_html__( 'An e-commerce discount and marketing plugin for WooCommerce. Powered by Jompha.', 'ultimate-coupon-for-woocommerce' ) . '</h3>
                        </div>
                    </div>
                    <div class="ucfw-admin-card">
                        <div class="ucfw-admin-card-section">
                            <ul class="ucfw-useful-links">
                                <li><span class="dashicons dashicons-align-right"></span> <a target="_blank" href="' . esc_url( '//docs.jompha.com/ultimate-coupons-for-woocommerce' ) . '">' . esc_html__( 'Docs', 'ultimate-coupon-for-woocommerce' ) . '</a></li>
                                <li><span class="dashicons dashicons-groups"></span> <a target="_blank" href="' . esc_url( '//forum.jompha.com' ) . '">' . esc_html__( 'Community', 'ultimate-coupon-for-woocommerce' ) . '</a></li>
                                <li><span class="dashicons dashicons-video-alt3"></span> <a target="_blank" href="' . esc_url( '//www.youtube.com/channel/UCuQ4vwfvcVHQNci8YPZObNw' ) . '">' . esc_html__( 'Video Tutorials', 'ultimate-coupon-for-woocommerce' ) . '</a></li>
                                <li><span class="dashicons dashicons-format-chat"></span> <a target="_blank" href="' . esc_url( '//support.jompha.com' ) . '">' . esc_html__( 'Premium Support', 'ultimate-coupon-for-woocommerce' ) . '</a></li>
                            </ul>
                            <iframe width="100%" height="500" src="' . esc_url( '//www.youtube.com/embed/videoseries?list=PLTPbwDUoP5WQQ4bowW9T0voOpFC6VaI5O' ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

                        </div>
                    </div>
                    <div class="ucfw-admin-card">
                        <div class="ucfw-admin-card-section ucfw-pricing">
                            <div class="ucfw-pricing-container">
                                <div class="ucfw-pricing-plan">
                                    <div class="ucfw-pricing-title">' . esc_html__( 'Individuals', 'ultimate-coupon-for-woocommerce' ) . '</div>
                                    <ul class="ucfw-pricing-features">
                                        <li>' . esc_html__( '1 Site License', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                        <li>' . esc_html__( '1 Year Updates', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                        <li>' . esc_html__( 'Priority Support', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                    </ul>
                                    <div class="ucfw-pricing-price"><span> ' . esc_html__( '$100', 'ultimate-coupon-for-woocommerce' ) . '</span>' . esc_html__( '$49', 'ultimate-coupon-for-woocommerce' ) . '</div>
                                </div>
                                <div class="ucfw-pricing-plan">
                                    <div class="ucfw-pricing-title">' . esc_html__( 'Freelancers', 'ultimate-coupon-for-woocommerce' ) . '</div>
                                    <ul class="ucfw-pricing-features">
                                        <li>' . esc_html__( '5 Site License', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                        <li>' . esc_html__( '1 Year Updates', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                        <li>' . esc_html__( 'Priority Support', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                    </ul>
                                    <div class="ucfw-pricing-price"><span>' . esc_html__( '$300', 'ultimate-coupon-for-woocommerce' ) . '</span>' . esc_html__( '$149', 'ultimate-coupon-for-woocommerce' ) . '</div>
                                </div>
                                <div class="ucfw-pricing-plan">
                                    <div class="ucfw-pricing-title">' . esc_html__( 'Agency', 'ultimate-coupon-for-woocommerce' ) . '</div>
                                    <ul class="ucfw-pricing-features">
                                        <li>' . esc_html__( '12 Site License', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                        <li>' . esc_html__( '1 Year Updates', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                        <li>' . esc_html__( 'Priority Support', 'ultimate-coupon-for-woocommerce' ) . '</li>
                                    </ul>
                                    <div class="ucfw-pricing-price"><span>' . esc_html__( '$600', 'ultimate-coupon-for-woocommerce' ) . '</span>' . esc_html__( '$299', 'ultimate-coupon-for-woocommerce' ) . '</div>
                                </div>
                            </div>
                            <div class="ucfw-buttom">
                                <a target="_blank" href="' . esc_url( '//jompha.com/ultimate-coupons-for-woocommerce' ) . '">' . esc_html__( 'Get Premium', 'ultimate-coupon-for-woocommerce' ) . '</a>
                            </div>
                            <img class="ucfw-pricing-moneyback" src="' . esc_url( UCFW_RESOURCES . '/images/14days-moneyback-guarantee.png' ) . '">
                        </div>
                    </div>
                </div>' ),
            ) );

        /**
         * General
         */
        $this->globalFields->add_tab( esc_html__( 'General', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_global_general', array(

            Field::make( 'text', 'ucfw_global_settings_cprefix', esc_html__( 'Coupon Prefix', 'ultimate-coupon-for-woocommerce' ) ),
            
            Field::make( 'text', 'ucfw_global_settings_csuffix', esc_html__( 'Coupon Suffix', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'text', 'ucfw_global_settings_clength', esc_html__( 'Coupon Length', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'separator', 'ucfw_global_deals_intro', esc_html__( 'Deals', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'text', 'ucfw_deals_apply_item_background_color', esc_html__( 'Cart Apply Item Background Color', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() ),

            Field::make( 'text', 'ucfw_deals_apply_item_border_color', esc_html__( 'Cart Apply Item Border Color', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() ),
            
            Field::make( 'text', 'ucfw_deals_apply_item_border_width', esc_html__( 'Cart Apply Item Border Width', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() ),

            Field::make( 'html', 'ucfw_global_general_buy_premium' )
                ->set_html( $this->buyPremium() ),
        
        ) ) );

        /**
         * Templates
         */
        $this->globalFields->add_tab( esc_html__( 'Templates', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_global_templates', $this->templateFields() ) );

        /**
         * Wheel game
         */
        $this->globalFields->add_tab( esc_html__( 'Wheel', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_global_wheel', array(

            Field::make( 'html', 'ucfw_wheel_options', esc_html__( 'Wheel Options', 'ultimate-coupon-for-woocommerce' ) )
            ->set_html(
                sprintf(
                    '<h1>%s</h1>
                    <img src="%s">',
                    esc_html__( 'Wheel', 'ultimate-coupon-for-woocommerce' ),
                    esc_url( UCFW_RESOURCES . '/images/wheel_banner.png' )
                )
            ),

            //Premium placeholder 
            Field::make( 'text', 'ucfw_wheel_shortcode', esc_html__( 'Shortcode', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() ),

            Field::make( 'text', 'ucfw_wheel_tries_limit', esc_html__( 'Limit', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() ),

            Field::make( 'complex', 'ucfw_wheel_slots', esc_html__( 'Slots', 'ultimate-coupon-for-woocommerce' ) )
                ->setup_labels( array(
                    'plural_name' => esc_html__( 'Slots', 'ultimate-coupon-for-woocommerce' ),
                    'singular_name' => esc_html__( 'Slot', 'ultimate-coupon-for-woocommerce' ),
                ) )
                ->set_max( 1 )
                ->add_fields( array(

                    Field::make( 'text', 'ucfw_slot_text', esc_html__( 'Text', 'ultimate-coupon-for-woocommerce' ) )
                        ->set_attribute( 'readOnly', true )
                        ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                        ->set_width( 50 ),

                    Field::make( 'text', 'ucfw_slot_coupon', esc_html__( 'Coupon', 'ultimate-coupon-for-woocommerce' ) )
                        ->set_attribute( 'readOnly', true )
                        ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                        ->set_width( 50 ),

                    Field::make( 'text', 'ucfw_slot_message', esc_html__( 'Message', 'ultimate-coupon-for-woocommerce' ) )
                        ->set_attribute( 'readOnly', true )
                        ->set_attribute( 'placeholder', $this->premiumPlaceholder() ),

                ) ),

            Field::make( 'html', 'ucfw_global_wheel_buy_premium')
                ->set_html( $this->buyPremium() ),
        
        ) ) );

        /**
         * Bulk generation
         */
        $this->globalFields->add_tab( esc_html__( 'Bulk', 'ultimate-coupon-for-woocommerce' ), apply_filters( 'ucfw_global_generate_bulk', array(
            
            //Premium placeholder 
            Field::make( 'html', 'ucfw_global_generate_bulk_premium_placeholder')
                ->set_html( 
                    sprintf('
                        <div class="premium_overlay">
                            <img src="%s">
                            <div class="overlay">%s</div>
                        </div>', 
                        esc_url( UCFW_RESOURCES . '/images/bulk-placeholder.png' ), 
                        $this->buyPremium()
                    ) 
                ),    

        ) ) );
    }

    /**
     * Templates Fields
     */
    public function templateFields()
    {
        $fields = array(
            Field::make( 'checkbox', 'ucfw_template_enabled', esc_html__( 'Enable Templates', 'ultimate-coupon-for-woocommerce' ) )
                ->set_option_value( 'yes' )
                ->set_help_text( esc_html__( 'Enable templates for Coupons (Popup / Header / Footer)', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'select', 'ucfw_template_coupons', esc_html__( 'Select Coupon', 'ultimate-coupon-for-woocommerce' ) )
                ->add_options( array_merge( array( '' => esc_html__( 'Select', 'ultimate-coupon-for-woocommerce' ) ), $this->helper->getAllCoupons() ) )
                ->set_width( 50 ),

            Field::make( 'text', 'ucfw_template_restricted_user_roles_premium', esc_html__( 'Restricted Roles', 'ultimate-coupon-for-woocommerce' ) )
                ->set_attribute( 'readOnly', true )
                ->set_attribute( 'placeholder', $this->premiumPlaceholder() )
                ->set_help_text( esc_html__( 'Select restricted user roles.', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 ),
            
            Field::make( 'select', 'ucfw_template_type', esc_html__( 'Display Type', 'ultimate-coupon-for-woocommerce' ) )
                ->add_options( array(
                    'popup'  => esc_html__( 'Popup', 'ultimate-coupon-for-woocommerce' ),
                    'header' => esc_html__( 'Header', 'ultimate-coupon-for-woocommerce' ),
                    'footer' => esc_html__( 'Footer', 'ultimate-coupon-for-woocommerce' ),
                ) )
                ->set_width( 50 ),
            
            Field::make( 'text', 'ucfw_template_title', esc_html__( 'Primary Text', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_help_text( esc_html__( 'Main text for your template, use [[YOUR TEXT]] for highlighted texts', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'date_time', 'ucfw_template_start_time', esc_html__( 'Starts From', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_help_text( esc_html__( 'The starting date and time from which the template will be shown.', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'date_time', 'ucfw_template_expire_time', esc_html__( 'Expires On', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 50 )
                ->set_help_text( esc_html__( 'The expire date and time after which the template will no longer be visible', 'ultimate-coupon-for-woocommerce' ) ),

            Field::make( 'radio_image', 'ucfw_template_popupdesign', esc_html__( 'Styles', 'ultimate-coupons-for-woocommerce' ) )
                ->set_options( apply_filters( 'ucfw_template_popup_options', array(
                    'blackfriday-1' => esc_url( UCFW_RESOURCES . '/images/preview-template-popup-blackfriday-1.jpg' ),
                    'premium-1' => esc_url( UCFW_RESOURCES . '/images/preview-template-popup-blackfriday-2.jpg' ),
                    'premium-2' => esc_url( UCFW_RESOURCES . '/images/preview-template-popup-cybermonday-1.jpg' ),
                    'premium-3' => esc_url( UCFW_RESOURCES . '/images/preview-template-popup-cybermonday-2.jpg' ),
                ) ) )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_type',
                            'compare' => 'IN',
                            'value'   => array( 'popup' )
                        )
                    )
                )
                ->set_width( 100 ),

            Field::make( 'radio_image', 'ucfw_template_bardesign', esc_html__( 'Styles', 'ultimate-coupons-for-woocommerce-premium' ) )
                ->set_options( apply_filters( 'ucfw_template_snackbar_options', array(
                    'blackfriday-1' => esc_url( UCFW_RESOURCES . '/images/preview-template-snackbar-blackfriday-1.png' ),
                    'premium-1'   => esc_url( UCFW_RESOURCES . '/images/preview-template-snackbar-christmas-1.png' ),
                    'premium-2' => esc_url( UCFW_RESOURCES . '/images/preview-template-snackbar-cybermonday-1.png' ),
                    'premium-3'   => esc_url( UCFW_RESOURCES . '/images/preview-template-snackbar-christmas-2.png' ),
                ) ) )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_type',
                            'compare' => 'IN',
                            'value'   => array( 'footer','header' )
                        )
                    )
                )
                ->set_width( 100 ),

            Field::make( 'textarea', 'ucfw_template_description', esc_html__( 'Short Description', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 33 ),
                
            Field::make( 'text', 'ucfw_template_goshop_text', esc_html__( 'Go To Shop Button Text', 'ultimate-coupon-for-woocommerce' ) )
                ->set_width( 33 ),

            Field::make( 'text', 'ucfw_template_goshop_url', esc_html__( 'URL', 'ultimate-coupon-for-woocommerce' ) )
            ->set_width( 33 ),

            Field::make( 'image', 'ucfw_template_image', esc_html__( 'Photo', 'ultimate-coupon-for-woocommerce' ) )
                ->set_value_type( 'url' )
                ->set_width( 33 ),

            Field::make( 'separator', 'color_separator', esc_html__( 'Colors', 'ultimate-coupons-for-woocommerce-premium' ) ),

            Field::make( 'radio', 'ucfw_template_color_set', __( 'Choose Color Set' ) )
            ->set_options( array(
                'default' => __('Default Colors', 'ultimate-coupons-for-woocommerce'),
                'custom' => __('Custom Colors', 'ultimate-coupons-for-woocommerce'),
            ) ),

            Field::make( 'color', 'ucfw_template_background_color', esc_html__('Background Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),

            Field::make( 'color', 'ucfw_template_text_color', esc_html__('Text Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_description_text_color', esc_html__('Description Text Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_coupon_background_color', esc_html__('Coupon Background Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_coupon_text_color', esc_html__('Coupon Text Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_goshop_background_color', esc_html__('Go Shop Background Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_goshop_text_color', esc_html__('Go Shop Text Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_countdown_background_color', esc_html__('Countdown Background Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_countdown_text_color', esc_html__('Countdown Text Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),

            Field::make( 'color', 'ucfw_template_countdown_interval_background_color', esc_html__('Countdown Interval Background Color (Days, Hours, etc)', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'color', 'ucfw_template_countdown_interval_text_color', esc_html__('Countdown Interval Text Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                ),
            
            Field::make( 'html', 'ucfw_templates_buy_premium' )
                ->set_html( $this->buyPremium() ),
        );

        $spliceArray = array();
        $title = get_option( '_ucfw_template_title', '' );
        preg_match_all('/\[\[(.*?)\]\]/i', $title, $matches);

        for ($i = 1; $i <= count($matches[0]); $i++)
        { 
            $spliceArray[] = Field::make( 'color', 'ucfw_template_highlighted_text_' . $i . '_color', esc_html__('Highlighted Text ' . $i . ' Color', 'ultimate-coupons-for-woocommerce') )
                ->set_width( 50 )
                ->set_conditional_logic( 
                    array(
                        array(
                            'field'   => 'ucfw_template_color_set',
                            'compare' => '=',
                            'value'   => 'custom'
                        )
                    )
                );
        }

        array_splice( $fields, 17, 0, $spliceArray );

        return $fields;
    }
  
    /**
     * Global sub menu pages 
     */
    public function globalSettingSubmenu() 
    {
        add_submenu_page(
            'crb_carbon_fields_container_ucfw_global_settings.php',
            esc_html__( 'UCFW Settings', 'ultimate-coupon-for-woocommerce' ),
            esc_html__( 'Settings', 'ultimate-coupon-for-woocommerce' ),
            'manage_options',
            'crb_carbon_fields_container_ucfw_global_settings.php'
        );

        add_submenu_page(
            'crb_carbon_fields_container_ucfw_global_settings.php',
            esc_html__( 'Coupons', 'ultimate-coupon-for-woocommerce' ),
            esc_html__( 'Coupons', 'ultimate-coupon-for-woocommerce' ),
            'manage_options',
            esc_url( '/edit.php?post_type=shop_coupon' )
        );

        if ( !class_exists('UCFWP') )
        {
            add_submenu_page(
                'crb_carbon_fields_container_ucfw_global_settings.php',
                '',
                '<span class="dashicons dashicons-superhero-alt"></span>' . esc_html__( 'Get Premium', 'ultimate-coupon-for-woocommerce' ),
                'manage_options',
                esc_url( 'https://jompha.com/ultimate-coupons-for-woocommerce' ),
                ''
            );
        }

        // Submenu from Premium
        do_action( 'ucfw_global_submenu' );
    }

    /**
    * Ajax save
    */
    public function saveCoupon( $couponId )
    {
        if ( !is_admin() || 'shop_coupon' !== get_post_type($couponId) )
            return false;

        $conditionEnabled  = ( isset($_POST['_ucfw_conditions_enabled']) && sanitize_text_field($_POST['_ucfw_conditions_enabled']) === 'yes' ) ? 'yes' : 'no';

        $conditionSettings = ( isset($_POST['_ucfw_groups']) && is_array($_POST['_ucfw_groups']) ) ? \UCFW\Admin\Conditions::processSettings($_POST['_ucfw_groups']) : false;
        $dealsSettings     = ( isset($_POST['_ucfw_deals']) && is_array($_POST['_ucfw_deals']) )   ? \UCFW\Admin\Deals::processSettings($_POST['_ucfw_deals']) : false;

        $saveData = array(
            '_ucfw_conditions_enabled' => $conditionEnabled,
            '_ucfw_conditions'         => $conditionSettings,
            '_ucfw_deals'              => $dealsSettings,
        );

        foreach ( $saveData as $key => $value )
            update_post_meta( absint($couponId), sanitize_text_field($key), $value );

        return true;
    }
}
