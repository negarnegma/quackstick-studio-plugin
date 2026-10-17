<?php
defined( 'ABSPATH' ) || exit;

/* ---------- quantity discount on custom items (pieces across the whole cart) ---------- */
function qs_cart_pieces( $cart ) {
	$n = 0;
	foreach ( $cart->get_cart() as $it ) {
		if ( empty( $it['qs'] ) ) { continue; }
		$n += (int) $it['quantity'] * ( 'sticker' === $it['qs']['type'] ? count( $it['qs']['stickers'] ) : 1 );
	}
	return $n;
}
function qs_discount_pct( $cart ) {
	$n = qs_cart_pieces( $cart );
	$p = 0;
	foreach ( qs_pairs( qs_opt( 'tiers_qty' ) ) as $q => $pc ) { if ( $n >= (float) $q ) { $p = (float) $pc; } }
	return min( 90, $p );
}
function qs_tiers_qty() {
	$t = array();
	foreach ( qs_pairs( qs_opt( 'tiers_qty' ) ) as $q => $p ) { $t[] = array( (float) $q, (float) $p ); }
	return $t;
}
add_filter( 'woocommerce_get_item_data', function ( $data, $it ) {
	if ( ! empty( $it['qs'] ) && WC()->cart && ( $p = qs_discount_pct( WC()->cart ) ) > 0 ) { $data[] = array( 'key' => 'تخفیف تعدادی', 'value' => $p . '٪' ); }
	return $data;
}, 20, 2 );

/* ---------- coupon helper ---------- */
add_action( 'admin_post_qs_coupon', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_coupon' );
	if ( ! wc_get_coupon_id_by_code( 'duck10' ) ) {
		$c = new WC_Coupon();
		$c->set_code( 'duck10' );
		$c->set_discount_type( 'percent' );
		$c->set_amount( 10 );
		$c->set_usage_limit_per_user( 1 );
		$c->set_description( 'کد تخفیف نمونه‌ی QuackStick' );
		$c->set_date_expires( time() + 90 * DAY_IN_SECONDS );
		$c->save();
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'wc-settings', 'tab' => 'qs', 'section' => 'tools', 'coupon' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* ---------- shipping method with per-city / per-province prices ---------- */
add_action( 'woocommerce_shipping_init', function () { require_once QS_DIR . 'includes/class-qs-shipping.php'; } );
add_filter( 'woocommerce_shipping_methods', function ( $m ) { $m['qs_ship'] = 'QS_Shipping_Method'; return $m; } );
function qs_norm( $s ) {
	$s = mb_strtolower( trim( (string) $s ) );
	$s = strtr( $s, array( 'ي' => 'ی', 'ك' => 'ک', 'ۀ' => 'ه', 'ة' => 'ه', '‌' => '', ' ' => '', 'ـ' => '' ) );
	return preg_replace( '/^استان/u', '', $s );
}
