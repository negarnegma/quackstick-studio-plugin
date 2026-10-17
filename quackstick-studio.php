<?php
/**
 * Plugin Name: QuackStick Studio
 * Description: طراح استیکر و قاب گوشی برای ووکامرس + صف چاپ و PDF استاندارد چاپ فقط برای ادمین + اردک مزاحم.
 * Version: 0.3.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: quackstick
 */
defined( 'ABSPATH' ) || exit;

define( 'QS_VER', '0.3.0' );
define( 'QS_DIR', plugin_dir_path( __FILE__ ) );
define( 'QS_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		// HPOS (custom order tables) compatible. Classic cart/checkout is assumed (see README).
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

require_once QS_DIR . 'includes/data.php';
require_once QS_DIR . 'includes/shop.php';
require_once QS_DIR . 'includes/notify.php';
require_once QS_DIR . 'includes/proof.php';
require_once QS_DIR . 'includes/cleanup.php';
require_once QS_DIR . 'includes/pricing.php';
require_once QS_DIR . 'includes/account.php';
require_once QS_DIR . 'includes/admin.php';
require_once QS_DIR . 'includes/settings.php';
require_once QS_DIR . 'includes/front.php';
require_once QS_DIR . 'includes/request.php';
require_once QS_DIR . 'includes/landing.php';

register_activation_hook( __FILE__, function () {
	if ( function_exists( 'qs_ensure_products' ) && class_exists( 'WC_Product_Simple' ) ) { qs_ensure_products(); }
	if ( function_exists( 'qs_ensure_pages' ) ) { qs_ensure_pages( true ); }
	if ( function_exists( 'qs_protect' ) ) { qs_protect(); }
} );
