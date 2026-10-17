<?php
defined( 'ABSPATH' ) || exit;

/* Settings live inside WooCommerce > Settings > QuackStick (a native tab). */
add_filter( 'woocommerce_get_settings_pages', function ( $pages ) {
	if ( class_exists( 'WC_Settings_Page' ) ) {
		require_once QS_DIR . 'includes/class-qs-settings.php';
		$pages[] = new QS_Settings_Page();
	}
	return $pages;
} );
add_filter( 'plugin_action_links_' . plugin_basename( QS_DIR . 'quackstick-studio.php' ), function ( $l ) {
	array_unshift( $l, '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=qs' ) ) . '">تنظیمات</a>' );
	return $l;
} );

/* block cart/checkout -> classic (most Iranian gateways need the classic shortcodes) */
function qs_uses_blocks() {
	if ( ! function_exists( 'wc_get_page_id' ) ) { return false; }
	$c = get_post( wc_get_page_id( 'cart' ) );
	$k = get_post( wc_get_page_id( 'checkout' ) );
	return ( $c && has_block( 'woocommerce/cart', $c ) ) || ( $k && has_block( 'woocommerce/checkout', $k ) );
}
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	if ( isset( $_GET['seeded'] ) ) { echo '<div class="notice notice-success"><p>اردک‌های آماده ساخته و منتشر شدن.</p></div>'; }
	if ( 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
		echo '<div class="notice notice-warning"><p><strong>فروشگاه الان توی حالت «به‌زودی» (Coming soon) است:</strong> فروشگاه، سبد، تسویه حساب و حساب من برای بازدیدکننده‌ها فقط پیام «Great things are on the horizon» رو نشون می‌دن (مدیرها همه‌چیز رو کامل می‌بینن). <a class="button" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ) . '">تغییر به «زنده»</a></p></div>';
	}
	if ( qs_uses_blocks() ) {
		echo '<div class="notice notice-warning"><p>سبد یا تسویه حساب شما «بلوکی» است. بیشتر افزونه‌های درگاه ایرانی (و کارت‌به‌کارت) با حالت کلاسیک کار می‌کنن. <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_classic' ), 'qs_classic' ) ) . '">تبدیل به کلاسیک</a></p></div>';
	}
} );
add_action( 'admin_post_qs_classic', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_classic' );
	wp_update_post( array( 'ID' => wc_get_page_id( 'cart' ), 'post_content' => '[woocommerce_cart]' ) );
	wp_update_post( array( 'ID' => wc_get_page_id( 'checkout' ), 'post_content' => '[woocommerce_checkout]' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=qs' ) );
	exit;
} );
