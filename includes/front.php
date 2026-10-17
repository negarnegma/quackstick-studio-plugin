<?php
defined( 'ABSPATH' ) || exit;

function qs_enqueue_core() {
	wp_enqueue_style( 'qs-fonts', QS_URL . 'assets/css/fonts.css', array(), QS_VER );
	wp_enqueue_script( 'qs-core', QS_URL . 'assets/js/qs-core.js', array(), QS_VER, true );
}
function qs_enqueue_designer() {
	qs_enqueue_core();
	wp_enqueue_style( 'qs-designer', QS_URL . 'assets/css/qs-designer.css', array( 'qs-fonts' ), QS_VER );
	wp_enqueue_script( 'qs-designer', QS_URL . 'assets/js/qs-designer.js', array( 'qs-core' ), QS_VER, true );
	wp_add_inline_script( 'qs-designer', 'window.QSD=' . wp_json_encode( array(
		'add'      => class_exists( 'WC_AJAX' ) ? wp_make_link_relative( WC_AJAX::get_endpoint( 'qs_add' ) ) : '',
		'nonce'    => wp_create_nonce( 'qs_add' ),
		'sizes'    => (object) qs_sizes(),
		'caseBase' => (float) qs_opt( 'case_base' ),
		'tiers'    => qs_tiers(),
		'sheets'   => (object) qs_sheet_prices(),
		'tiersQty' => qs_tiers_qty(),
		'logged'   => is_user_logged_in(),
		'save'     => class_exists( 'WC_AJAX' ) ? wp_make_link_relative( WC_AJAX::get_endpoint( 'qs_save' ) ) : '',
		'account'  => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '',
		'login'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '',
		'sketchCm' => (float) qs_opt( 'sketch_cm' ),
		'cur'      => html_entity_decode( get_woocommerce_currency_symbol() ),
	) ) . ';', 'before' );
}

add_action( 'wp_enqueue_scripts', function () {
	$shop = function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() );
	if ( $shop ) {
		qs_enqueue_core();
		wp_enqueue_script( 'qs-render', QS_URL . 'assets/js/qs-render.js', array( 'qs-core' ), QS_VER, true );
	}
	if ( (float) qs_opt( 'gift_min' ) > 0 && class_exists( 'WC_AJAX' ) ) {
		$models = array();
		foreach ( qs_models() as $id => $m ) { $models[] = array( $id, $m[0] ); }
		wp_enqueue_style( 'qs-gift', QS_URL . 'assets/css/qs-gift.css', array(), QS_VER );
		wp_enqueue_script( 'qs-gift', QS_URL . 'assets/js/qs-gift.js', array(), QS_VER, true );
		wp_add_inline_script( 'qs-gift', 'window.QSG=' . wp_json_encode( array(
			'state'  => wp_make_link_relative( WC_AJAX::get_endpoint( 'qs_gift_state' ) ),
			'gift'   => wp_make_link_relative( WC_AJAX::get_endpoint( 'qs_gift' ) ),
			'nonce'  => wp_create_nonce( 'qs_gift' ),
			'models' => $models,
			'cur'    => html_entity_decode( get_woocommerce_currency_symbol() ),
		) ) . ';', 'before' );
	}
	if ( qs_opt( 'fa' ) ) { wp_enqueue_script( 'qs-fa', QS_URL . 'assets/js/qs-fa.js', array(), QS_VER, true ); }
	if ( qs_opt( 'craft' ) && 'quackstick-craft' !== get_template() ) { wp_enqueue_style( 'qs-craft', QS_URL . 'assets/css/qs-craft.css', array(), QS_VER ); }
	$quiet = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ); // never get in the way of paying
	if ( qs_opt( 'duck' ) && ! $quiet ) {
		wp_enqueue_style( 'qs-fonts', QS_URL . 'assets/css/fonts.css', array(), QS_VER );
		wp_enqueue_style( 'qs-duck', QS_URL . 'assets/css/qs-duck.css', array(), QS_VER );
		wp_enqueue_script( 'qs-duck', QS_URL . 'assets/js/qs-duck.js', array(), QS_VER, true );
		wp_add_inline_script( 'qs-duck', 'window.QSDUCK=' . wp_json_encode( array( 'name' => qs_opt( 'duck_name' ), 'costume' => qs_opt( 'duck_costume' ), 'tier' => qs_tiers_qty() ? qs_tiers_qty()[0] : null ) ) . ';', 'before' );
	}
} );
add_filter( 'body_class', function ( $c ) { if ( qs_opt( 'craft' ) && 'quackstick-craft' !== get_template() ) { $c[] = 'qs-craft'; } return $c; } );

function qs_enqueue_landing() {
	qs_enqueue_core();
	wp_enqueue_script( 'qs-render', QS_URL . 'assets/js/qs-render.js', array( 'qs-core' ), QS_VER, true );
	wp_enqueue_style( 'qs-landing', QS_URL . 'assets/css/qs-landing.css', array( 'qs-fonts' ), QS_VER );
}
function qs_enqueue_request() {
	wp_enqueue_style( 'qs-fonts', QS_URL . 'assets/css/fonts.css', array(), QS_VER );
	wp_enqueue_style( 'qs-landing', QS_URL . 'assets/css/qs-landing.css', array( 'qs-fonts' ), QS_VER );
	wp_enqueue_script( 'qs-request', QS_URL . 'assets/js/qs-request.js', array(), QS_VER, true );
	wp_add_inline_script( 'qs-request', 'window.QSR=' . wp_json_encode( array(
		'ajax'  => admin_url( 'admin-ajax.php', 'relative' ),
		'nonce' => wp_create_nonce( 'qs_req' ),
		'tg'    => qs_opt( 'tg' ),
	) ) . ';', 'before' );
}

function qs_enqueue_request_css() {
	wp_enqueue_style( 'qs-fonts', QS_URL . 'assets/css/fonts.css', array(), QS_VER );
	wp_enqueue_style( 'qs-landing', QS_URL . 'assets/css/qs-landing.css', array( 'qs-fonts' ), QS_VER );
}
