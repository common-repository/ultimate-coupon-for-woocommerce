<?php
/**
 * Plugin Name: Ultimate Coupons for WooCommerce Free
 * Plugin URI: https://jompha.com/ultimate-coupons-for-woocommerce
 * Description: An e-commerce discount and marketing plugin for WooCommerce. Powered by Jompha.
 * Version: 1.2.0
 * Author: Jompha
 * Author URI: https://jompha.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.3
 * Tested up to: 6.4.0
 * WC requires at least: 6.0
 * WC tested up to: 8.4.0
 * 
 * Text Domain: ultimate-coupon-for-woocommerce
 * Domain Path: /languages
 * 
 * @package UCFW
 * @author Jompha
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) )
    exit();

require_once __DIR__ . '/UCFW.php';
\UCFW::getInstance();
