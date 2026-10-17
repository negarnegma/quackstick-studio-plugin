<?php
defined( 'ABSPATH' ) || exit;

/* ---------- outgoing messages: Telegram bot (admin) + SMS (admin and customer). Sent after the page is served. ---------- */
$GLOBALS['qs_queue'] = array();

function qs_vars( $order, $extra = array() ) {
	return array_merge( array(
		'order'    => $order->get_order_number(),
		'name'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: 'مشتری',
		'total'    => html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total() ) ) ),
		'tracking' => $order->get_meta( '_qs_tracking' ) ? 'کد رهگیری: ' . $order->get_meta( '_qs_tracking' ) : '',
		'link'     => $order->get_meta( '_qs_proof_key' ) ? qs_proof_url( $order ) : '',
	), $extra );
}
function qs_fill( $tpl, $vars ) {
	foreach ( $vars as $k => $v ) { $tpl = str_replace( '{' . $k . '}', (string) $v, $tpl ); }
	return trim( preg_replace( '/\s{2,}/', ' ', $tpl ) );
}
function qs_tg( $text ) { if ( qs_opt( 'bot' ) && qs_opt( 'chat' ) ) { $GLOBALS['qs_queue'][] = array( 'tg', $text, 0 ); } }
function qs_sms( $to, $text, $order_id = 0 ) { $to = qs_phone( $to ); if ( $to && qs_opt( 'sms_provider' ) && $text ) { $GLOBALS['qs_queue'][] = array( 'sms', $text, $order_id, $to ); } }
function qs_notify_admin( $text, $order_id = 0 ) { qs_tg( $text ); qs_sms( qs_opt( 'sms_admin' ), $text, $order_id ); }

add_action( 'shutdown', function () {
	if ( empty( $GLOBALS['qs_queue'] ) ) { return; }
	if ( function_exists( 'fastcgi_finish_request' ) ) { fastcgi_finish_request(); }
	foreach ( $GLOBALS['qs_queue'] as $q ) {
		$ok = false;
		if ( 'tg' === $q[0] ) {
			$r  = wp_remote_post( apply_filters( 'qs_telegram_api_base', 'https://api.telegram.org' ) . '/bot' . qs_opt( 'bot' ) . '/sendMessage', array( 'timeout' => 8, 'body' => array( 'chat_id' => qs_opt( 'chat' ), 'text' => $q[1] ) ) );
			$ok = ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) < 300;
		} else {
			$to = $q[3]; $text = $q[1]; $pv = qs_opt( 'sms_provider' );
			if ( 'kavenegar' === $pv ) {
				$args = array( 'receptor' => $to, 'message' => $text );
				if ( qs_opt( 'sms_sender' ) ) { $args['sender'] = qs_opt( 'sms_sender' ); }
				$r = wp_remote_get( add_query_arg( $args, apply_filters( 'qs_kavenegar_base', 'https://api.kavenegar.com/v1' ) . '/' . qs_opt( 'sms_key' ) . '/sms/send.json' ), array( 'timeout' => 8 ) );
			} else { // generic webhook: placeholders {to} {text} {sender} {key}
				$map = array( 'to' => $to, 'text' => $text, 'sender' => qs_opt( 'sms_sender' ), 'key' => qs_opt( 'sms_key' ) );
				if ( 'POST' === qs_opt( 'sms_method' ) ) {
					$r = wp_remote_post( str_replace( array( '{to}', '{text}', '{sender}', '{key}' ), '', qs_opt( 'sms_url' ) ), array( 'timeout' => 8, 'body' => $map ) );
				} else {
					$u = qs_opt( 'sms_url' );
					foreach ( $map as $k => $v ) { $u = str_replace( '{' . $k . '}', rawurlencode( $v ), $u ); }
					$r = wp_remote_get( $u, array( 'timeout' => 8 ) );
				}
			}
			$ok = ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) < 300;
			if ( $q[2] && ( $o = wc_get_order( $q[2] ) ) ) { $o->add_order_note( 'پیامک به ' . $to . ( $ok ? ' ارسال شد.' : ' ناموفق بود.' ) ); }
		}
		if ( ! $ok ) { error_log( 'QuackStick: ' . $q[0] . ' notification failed' ); }
	}
	$GLOBALS['qs_queue'] = array();
}, 999 );

/* ---------- events ---------- */
function qs_on_new_order( $order ) {
	if ( ! $order || $order->get_meta( '_qs_n_new' ) ) { return; }
	$order->update_meta_data( '_qs_n_new', 1 );
	$order->save();
	if ( qs_opt( 'n_admin_order' ) ) { qs_notify_admin( qs_fill( qs_opt( 't_admin_order' ), qs_vars( $order ) ), $order->get_id() ); }
	if ( function_exists( 'qs_proof_init' ) ) { qs_proof_init( $order ); }
}
add_action( 'woocommerce_checkout_order_processed', function ( $id ) { qs_on_new_order( wc_get_order( $id ) ); }, 20 );
add_action( 'woocommerce_store_api_checkout_order_processed', function ( $order ) { qs_on_new_order( $order ); }, 20 );

function qs_status_sms( $id, $status, $opt, $tpl ) {
	$o = wc_get_order( $id );
	if ( ! $o || ! qs_opt( $opt ) || $o->get_meta( '_qs_n_' . $status ) ) { return; }
	$o->update_meta_data( '_qs_n_' . $status, 1 );
	$o->save();
	qs_sms( $o->get_billing_phone(), qs_fill( qs_opt( $tpl ), qs_vars( $o ) ), $o->get_id() );
}
add_action( 'woocommerce_order_status_processing', function ( $id ) { qs_status_sms( $id, 'processing', 'n_processing', 't_processing' ); } );
add_action( 'woocommerce_order_status_completed', function ( $id ) { qs_status_sms( $id, 'completed', 'n_completed', 't_completed' ); } );

/* tracking code field on the order screen -> customer note (e-mail) + SMS */
add_action( 'woocommerce_process_shop_order_meta', function ( $id ) {
	if ( ! isset( $_POST['qs_tracking'] ) || ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$o = wc_get_order( $id );
	$t = sanitize_text_field( wp_unslash( $_POST['qs_tracking'] ) );
	if ( ! $o || $t === (string) $o->get_meta( '_qs_tracking' ) ) { return; }
	$o->update_meta_data( '_qs_tracking', $t );
	$o->save();
	if ( $t ) {
		$o->add_order_note( 'کد رهگیری ارسال: ' . $t, true ); // customer note -> WooCommerce e-mails it
		if ( qs_opt( 'n_shipped' ) ) { qs_sms( $o->get_billing_phone(), qs_fill( qs_opt( 't_shipped' ), qs_vars( $o ) ), $o->get_id() ); }
	}
} );
