<?php
defined( 'ABSPATH' ) || exit;

/* ---------- daily clean-up of design files nobody references any more ---------- */
function qs_file_used( $name ) {
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $name ) . '%';
	$wpdb->suppress_errors( true );
	$q = array(
		$wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ('_qs_design','_qs_pv') AND meta_value LIKE %s LIMIT 1", $like ),
		$wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = '_qs_ref' AND meta_value LIKE %s LIMIT 1", $like ),
		$wpdb->prepare( "SELECT 1 FROM {$wpdb->usermeta} WHERE meta_key = '_qs_saved' AND meta_value LIKE %s LIMIT 1", $like ),
		$wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}woocommerce_sessions WHERE session_value LIKE %s LIMIT 1", $like ),
	);
	$used = false;
	foreach ( $q as $sql ) { if ( $wpdb->get_var( $sql ) ) { $used = true; break; } }
	$wpdb->suppress_errors( false );
	return $used;
}
/** @return array{deleted:int,kept:int,bytes:int} */
function qs_cleanup( $dry = false, $days = null ) {
	$days = null === $days ? (int) qs_opt( 'cleanup_days' ) : (int) $days;
	$res  = array( 'deleted' => 0, 'kept' => 0, 'bytes' => 0 );
	if ( $days <= 0 ) { return $res; }
	$limit = time() - $days * DAY_IN_SECONDS;
	foreach ( array( 'sk', 'pv', 'ref' ) as $dir ) {
		foreach ( (array) glob( qs_base() . $dir . '/*.png' ) as $f ) {
			if ( filemtime( $f ) > $limit ) { continue; }
			if ( qs_file_used( basename( $f ) ) ) { $res['kept']++; continue; }
			$res['bytes'] += (int) filesize( $f );
			if ( $dry || @unlink( $f ) ) { $res['deleted']++; }
		}
	}
	return $res;
}
add_action( 'init', function () { if ( ! wp_next_scheduled( 'qs_cleanup_daily' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'qs_cleanup_daily' ); } } );
add_action( 'qs_cleanup_daily', function () { qs_cleanup(); } );
register_deactivation_hook( QS_DIR . 'quackstick-studio.php', function () { wp_clear_scheduled_hook( 'qs_cleanup_daily' ); } );
add_action( 'admin_post_qs_cleanup_run', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_cleanup_run' );
	$r = qs_cleanup( false, max( 1, (int) qs_opt( 'cleanup_days' ) ) );
	wp_safe_redirect( add_query_arg( array( 'page' => 'wc-settings', 'tab' => 'qs', 'section' => 'tools', 'cleaned' => $r['deleted'], 'kept' => $r['kept'], 'kb' => (int) ( $r['bytes'] / 1024 ) ), admin_url( 'admin.php' ) ) );
	exit;
} );
