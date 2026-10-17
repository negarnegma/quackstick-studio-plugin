<?php
defined( 'ABSPATH' ) || exit;

/* ---------- "My designs": saved designs + past custom items, one tap to re-order ---------- */
add_action( 'init', function () {
	add_rewrite_endpoint( 'qs-designs', EP_ROOT | EP_PAGES );
	if ( get_option( 'qs_rw' ) !== QS_VER ) { flush_rewrite_rules( false ); update_option( 'qs_rw', QS_VER ); }
} );
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$n = array();
	foreach ( $items as $k => $v ) { if ( 'customer-logout' === $k ) { $n['qs-designs'] = 'طرح‌های من'; } $n[ $k ] = $v; }
	if ( ! isset( $n['qs-designs'] ) ) { $n['qs-designs'] = 'طرح‌های من'; }
	return $n;
} );

function qs_saved_list( $uid ) { $l = get_user_meta( $uid, '_qs_saved', true ); return is_array( $l ) ? $l : array(); }

/* save from the designer (logged-in customers) */
add_action( 'wc_ajax_qs_save', function () {
	check_ajax_referer( 'qs_add', 'nonce' );
	if ( ! is_user_logged_in() ) { wp_send_json_error( array( 'msg' => 'برای ذخیره‌ی طرح وارد حسابت شو.', 'login' => 1 ) ); }
	$d = qs_clean( wp_unslash( isset( $_POST['design'] ) ? $_POST['design'] : '' ) );
	if ( is_wp_error( $d ) ) { wp_send_json_error( array( 'msg' => $d->get_error_message() ) ); }
	$pv   = qs_save_png( wp_unslash( isset( $_POST['preview'] ) ? $_POST['preview'] : '' ), 'pv' );
	$list = qs_saved_list( get_current_user_id() );
	array_unshift( $list, array( 'id' => wp_generate_password( 10, false ), 'title' => qs_summary( $d ), 'design' => $d, 'pv' => is_wp_error( $pv ) ? '' : $pv, 'ts' => time() ) );
	update_user_meta( get_current_user_id(), '_qs_saved', array_slice( $list, 0, 30 ) );
	wp_send_json_success( array( 'count' => count( $list ), 'url' => wc_get_account_endpoint_url( 'qs-designs' ) ) );
} );

/* add a stored design back to the cart (prices are recomputed from the current price list) */
add_action( 'wc_ajax_qs_readd', function () {
	if ( ! is_user_logged_in() || ! wp_verify_nonce( isset( $_POST['n'] ) ? $_POST['n'] : '', 'qs_readd' ) ) { wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) ); exit; }
	$src = isset( $_POST['src'] ) ? sanitize_text_field( wp_unslash( $_POST['src'] ) ) : '';
	$d = null; $pv = '';
	if ( 0 === strpos( $src, 's:' ) ) {
		foreach ( qs_saved_list( get_current_user_id() ) as $e ) { if ( $e['id'] === substr( $src, 2 ) ) { $d = $e['design']; $pv = $e['pv']; } }
	} elseif ( preg_match( '/^o:(\d+):(\d+)$/', $src, $m ) ) {
		$o = wc_get_order( (int) $m[1] );
		if ( $o && (int) $o->get_customer_id() === get_current_user_id() && ( $it = $o->get_item( (int) $m[2] ) ) ) { $d = json_decode( (string) $it->get_meta( '_qs_design' ), true ); $pv = (string) $it->get_meta( '_qs_pv' ); }
	}
	$ok = false;
	if ( is_array( $d ) ) {
		$c = qs_clean( wp_json_encode( $d ) );
		if ( ! is_wp_error( $c ) ) {
			foreach ( $c['stickers'] as $s ) { if ( 'sk' === $s['sp']['k'] && ! is_file( qs_base() . $s['sp']['f'] ) ) { $c = null; break; } }
			if ( $c && ( $price = qs_price( $c ) ) > 0 && ( $pid = (int) get_option( 'qs_pid_' . $c['type'] ) ) ) {
				$ok = (bool) WC()->cart->add_to_cart( $pid, 1, 0, array(), array( 'qs' => $c, 'qs_price' => $price, 'qs_pv' => $pv ) );
			}
		}
	}
	wc_clear_notices();
	wc_add_notice( $ok ? 'طرح به سبد اضافه شد.' : 'این طرح دیگه قابل افزودن نیست (فایلش پاک شده یا قیمت‌گذاری عوض شده).', $ok ? 'success' : 'error' );
	wp_safe_redirect( $ok ? wc_get_cart_url() : wc_get_endpoint_url( 'qs-designs', '', wc_get_page_permalink( 'myaccount' ) ) );
	exit;
} );
add_action( 'admin_post_qs_unsave', function () {
	if ( ! is_user_logged_in() ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_unsave' );
	$id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
	update_user_meta( get_current_user_id(), '_qs_saved', array_values( array_filter( qs_saved_list( get_current_user_id() ), function ( $e ) use ( $id ) { return $e['id'] !== $id; } ) ) );
	wp_safe_redirect( wc_get_endpoint_url( 'qs-designs', '', wc_get_page_permalink( 'myaccount' ) ) );
	exit;
} );

function qs_design_card( $src, $title, $pv, $extra = '' ) {
	$form = '<form method="post" action="' . esc_url( WC_AJAX::get_endpoint( 'qs_readd' ) ) . '" style="display:inline"><input type="hidden" name="n" value="' . esc_attr( wp_create_nonce( 'qs_readd' ) ) . '"><input type="hidden" name="src" value="' . esc_attr( $src ) . '"><button class="button" type="submit">افزودن به سبد</button></form>';
	return '<div style="display:flex;gap:12px;align-items:center;border:2px dashed rgba(58,40,23,.4);border-radius:8px;padding:10px;margin:0 0 12px">'
		. ( $pv ? '<img src="' . esc_url( qs_url() . $pv ) . '" alt="" style="width:90px;height:auto;background:#fff;border-radius:6px">' : '' )
		. '<div style="flex:1"><strong>' . esc_html( $title ) . '</strong></div>' . $form . $extra . '</div>';
}
add_action( 'woocommerce_account_qs-designs_endpoint', function () {
	$uid = get_current_user_id();
	echo '<h3>طرح‌های من</h3>';
	$seen = array(); $n = 0;
	foreach ( qs_saved_list( $uid ) as $e ) {
		$seen[ md5( wp_json_encode( $e['design'] ) ) ] = 1; $n++;
		$del = ' <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_unsave&id=' . $e['id'] ), 'qs_unsave' ) ) . '">حذف</a>';
		echo qs_design_card( 's:' . $e['id'], $e['title'], $e['pv'], $del ); // phpcs:ignore
	}
	foreach ( wc_get_orders( array( 'customer_id' => $uid, 'limit' => 15, 'orderby' => 'date', 'order' => 'DESC' ) ) as $o ) {
		foreach ( $o->get_items() as $id => $it ) {
			$d = json_decode( (string) $it->get_meta( '_qs_design' ), true );
			if ( ! $it->get_meta( '_qs_custom' ) || ! is_array( $d ) || isset( $seen[ md5( wp_json_encode( $d ) ) ] ) ) { continue; }
			$seen[ md5( wp_json_encode( $d ) ) ] = 1; $n++;
			echo qs_design_card( 'o:' . $o->get_id() . ':' . $id, $it->get_meta( 'طرح' ) . ' (سفارش #' . $o->get_order_number() . ')', (string) $it->get_meta( '_qs_pv' ) ); // phpcs:ignore
		}
	}
	if ( ! $n ) { echo '<p>هنوز طرحی نداری. توی صفحه‌ی طراحی، «ذخیره‌ی طرح» رو بزن.</p>'; }
} );
