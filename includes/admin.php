<?php
defined( 'ABSPATH' ) || exit;

/* ---------- settings + print queue pages ---------- */
add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'صف چاپ QuackStick', 'صف چاپ', 'manage_woocommerce', 'qs-print', 'qs_print_page' );
} );
add_action( 'admin_post_qs_seed', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_seed' );
	qs_seed_presets();
	wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=qs&section=tools&seeded=1' ) );
	exit;
} );

/* ---------- helpers ---------- */
function qs_print_url( array $ids ) {
	return add_query_arg( array( 'page' => 'qs-print', 'ids' => implode( ',', array_map( 'absint', $ids ) ), '_wpnonce' => wp_create_nonce( 'qs_print' ) ), admin_url( 'admin.php' ) );
}
function qs_order_payload( $order ) {
	$items = array();
	foreach ( $order->get_items() as $it ) {
		$d = json_decode( (string) $it->get_meta( '_qs_design' ), true );
		if ( is_array( $d ) && ! empty( $d['stickers'] ) ) { $items[] = array( 'name' => $it->get_name(), 'qty' => max( 1, (int) $it->get_quantity() ), 'design' => $d ); }
	}
	return $items ? array( 'id' => $order->get_id(), 'num' => $order->get_order_number(), 'proof' => (string) $order->get_meta( '_qs_proof' ), 'items' => $items ) : null;
}
function qs_ids_from_request() {
	$r = isset( $_GET['ids'] ) ? wp_unslash( $_GET['ids'] ) : array();
	if ( is_string( $r ) ) { $r = explode( ',', $r ); }
	return array_values( array_filter( array_map( 'absint', (array) $r ) ) );
}

/* ---------- print queue page ---------- */
function qs_print_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	echo '<div class="wrap"><h1>صف چاپ QuackStick</h1>';
	$ids = qs_ids_from_request();
	if ( $ids ) {
		if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'qs_print' ) ) { wp_die( 'لینک منقضی شده. از صف چاپ دوباره وارد شو.' ); }
		?>
		<div id="qs-print" style="max-width:1100px">
			<p>این صفحه فقط برای ادمینه. PDF توی مرورگر خودت ساخته می‌شه و جایی آپلود نمی‌شه.</p>
			<p><label>ورق: <select id="qsp-sheet"><option value="a4">A4</option><option value="a3">A3</option></select></label>
			<button class="button button-primary" id="qsp-pdf" disabled>دانلود PDF چاپ</button>
			<button class="button button-primary" id="qsp-pro" disabled title="CMYK، رنگ اسپات CutContour برای خط برش، TrimBox/BleedBox">PDF حرفه‌ای (CMYK + اسپات برش)</button>
			<button class="button" id="qsp-guide" disabled>راهنمای چسباندن روی قاب (PDF)</button>
			<button class="button" id="qsp-mark" disabled>ثبت به‌عنوان چاپ‌شده</button>
			<button class="button" id="qsp-retry" style="display:none">تلاش دوباره</button></p>
			<p id="qsp-msg"></p><div id="qsp-warn"></div><p id="qsp-info"><strong></strong></p><div id="qsp-sheets"></div>
		</div>
		<?php
	} else {
		$rows = array();
		foreach ( wc_get_orders( array( 'limit' => 60, 'status' => array( 'processing' ), 'orderby' => 'date', 'order' => 'DESC' ) ) as $o ) {
			if ( qs_order_payload( $o ) ) { $rows[] = $o; }
		}
		echo '<p>سفارش‌های «در حال انجام» که استیکر دارن. برای سفارش‌های دیگه از لیست سفارش‌ها عملیات گروهی «ساخت PDF چاپ» رو بزن.</p>';
		if ( ! $rows ) { echo '<p>سفارشی تو صف نیست.</p></div>'; return; }
		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="qs-print">';
		wp_nonce_field( 'qs_print' );
		echo '<table class="widefat striped"><thead><tr><th></th><th>سفارش</th><th>تاریخ</th><th>استیکرها</th><th>تأیید طرح</th><th>وضعیت چاپ</th></tr></thead><tbody>';
		foreach ( $rows as $o ) {
			$p = qs_order_payload( $o ); $n = 0;
			foreach ( $p['items'] as $it ) { foreach ( $it['design']['stickers'] as $st ) { $n += ( isset( $st['n'] ) ? (int) $st['n'] : 1 ) * $it['qty']; } }
			$dt = $o->get_date_created();
			$pk = (string) $o->get_meta( '_qs_proof' ); $okp = ( ! $pk || 'approved' === $pk );
			printf( '<tr><td><input type="checkbox" name="ids[]" value="%d" %s></td><td>#%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
				$o->get_id(), ( $o->get_meta( '_qs_printed' ) || ! $okp ) ? '' : 'checked', esc_html( $o->get_order_number() ), $dt ? esc_html( $dt->date_i18n( 'Y/m/d H:i' ) ) : '', $n, esc_html( $pk ? qs_proof_states()[ $pk ] : '-' ), $o->get_meta( '_qs_printed' ) ? 'چاپ شده' : 'منتظر چاپ' );
		}
		echo '</tbody></table><p><button class="button button-primary">ساخت PDF چاپ برای انتخاب‌شده‌ها</button></p></form></div>';
	}
	if ( $ids ) { echo '</div>'; }
}
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'woocommerce_page_qs-print' !== $hook || ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$ids = qs_ids_from_request();
	if ( ! $ids || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'qs_print' ) ) { return; }
	wp_enqueue_style( 'qs-fonts', QS_URL . 'assets/css/fonts.css', array(), QS_VER );
	wp_enqueue_script( 'qs-jspdf', QS_URL . 'assets/js/jspdf.umd.min.js', array(), '2.5.1', true );
	wp_enqueue_script( 'qs-core', QS_URL . 'assets/js/qs-core.js', array(), QS_VER, true );
	wp_enqueue_script( 'qs-print', QS_URL . 'assets/js/qs-print.js', array( 'qs-jspdf', 'qs-core' ), QS_VER, true );
	$orders = array();
	foreach ( $ids as $id ) { $o = wc_get_order( $id ); $p = $o ? qs_order_payload( $o ) : null; if ( $p ) { $orders[] = $p; } }
	wp_add_inline_script( 'qs-print', 'window.QSP=' . wp_json_encode( array(
		'orders'   => $orders,
		'fileBase' => add_query_arg( array( 'action' => 'qs_file', '_wpnonce' => wp_create_nonce( 'qs_file' ) ), admin_url( 'admin-post.php', 'relative' ) ) . '&f=',
		'ajax'     => admin_url( 'admin-ajax.php', 'relative' ),
		'nonce'    => wp_create_nonce( 'qs_mark' ),
		'sheet'    => qs_opt( 'sheet' ),
	) ) . ';', 'before' );
} );

/* private design files + media-library images: admins only, streamed through PHP (same origin as the admin page) */
add_action( 'admin_post_qs_file', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'forbidden', 403 ); }
	check_admin_referer( 'qs_file' );
	$f = isset( $_GET['f'] ) ? wp_unslash( $_GET['f'] ) : '';
	$p = '';
	$mime = 'image/png';
	if ( preg_match( '#^a:(\d+)$#', $f, $m ) ) { // attachment (product artwork)
		$id = (int) $m[1];
		if ( wp_attachment_is_image( $id ) ) {
			$p    = (string) get_attached_file( $id );
			$mime = (string) get_post_mime_type( $id );
		}
		if ( ! in_array( $mime, array( 'image/png', 'image/jpeg', 'image/webp', 'image/gif' ), true ) ) { $p = ''; }
	} elseif ( preg_match( '#^(sk|ref)/[a-f0-9]{32}\.png$#', $f ) ) {
		$p = qs_base() . $f;
	}
	if ( ! $p || ! is_file( $p ) ) { wp_die( 'not found', 404 ); }
	nocache_headers();
	header( 'Content-Type: ' . $mime );
	header( 'Content-Length: ' . filesize( $p ) );
	readfile( $p );
	exit;
} );
add_action( 'wp_ajax_qs_mark', function () {
	check_ajax_referer( 'qs_mark', 'nonce' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error(); }
	foreach ( array_map( 'absint', explode( ',', (string) ( isset( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : '' ) ) ) as $id ) {
		$o = $id ? wc_get_order( $id ) : null;
		if ( $o ) { $o->update_meta_data( '_qs_printed', time() ); $o->add_order_note( 'PDF چاپ QuackStick ساخته شد.' ); $o->save(); }
	}
	wp_send_json_success();
} );

/* ---------- orders list: column, bulk action, order-screen box (HPOS + legacy) ---------- */
function qs_col_add( $c ) { $c['qs_print'] = 'استیکر'; return $c; }
function qs_col_show( $col, $order ) {
	if ( 'qs_print' !== $col ) { return; }
	$order = is_object( $order ) ? $order : wc_get_order( $order );
	if ( ! $order ) { return; }
	if ( qs_order_payload( $order ) ) {
		echo '<a href="' . esc_url( qs_print_url( array( $order->get_id() ) ) ) . '">PDF چاپ</a>' . ( $order->get_meta( '_qs_printed' ) ? ' ✓' : '' );
	}
	$ps = qs_proof_states(); $pk = (string) $order->get_meta( '_qs_proof' );
	if ( $pk && isset( $ps[ $pk ] ) ) { echo '<br><small>طرح: ' . esc_html( $ps[ $pk ] ) . '</small>'; }
	if ( qs_order_gift( $order ) ) { echo '<br><strong>قاب هدیه: طراحی دستی</strong>'; }
}
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'qs_col_add' );
add_filter( 'manage_edit-shop_order_columns', 'qs_col_add' );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'qs_col_show', 10, 2 );
add_action( 'manage_shop_order_posts_custom_column', 'qs_col_show', 10, 2 );

function qs_bulk_add( $a ) { $a['qs_print'] = 'ساخت PDF چاپ QuackStick'; return $a; }
function qs_bulk_do( $redirect, $action, $ids ) { return 'qs_print' === $action ? qs_print_url( (array) $ids ) : $redirect; }
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'qs_bulk_add' );
add_filter( 'bulk_actions-edit-shop_order', 'qs_bulk_add' );
add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', 'qs_bulk_do', 10, 3 );
add_filter( 'handle_bulk_actions-edit-shop_order', 'qs_bulk_do', 10, 3 );

add_action( 'add_meta_boxes', function () {
	$s = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box( 'qs_print', 'QuackStick: فایل چاپ', function ( $p ) {
		$o = $p instanceof WP_Post ? wc_get_order( $p->ID ) : $p;
		if ( ! $o ) { return; }
		$g = qs_order_gift( $o );
		if ( $g ) { echo '<p><strong>قاب هدیه (طراحی دستی):</strong> ' . esc_html( $g['model'] ) . '<br>تم: ' . esc_html( $g['theme'] ) . '</p>'; }
		$pk = (string) $o->get_meta( '_qs_proof' ); $ps = qs_proof_states();
		if ( $pk ) {
			echo '<p><strong>تأیید طرح:</strong> ' . esc_html( $ps[ $pk ] ) . ( $o->get_meta( '_qs_proof_note' ) ? '<br>«' . esc_html( $o->get_meta( '_qs_proof_note' ) ) . '»' : '' ) . '</p>';
			$ba = function ( $act, $label ) use ( $o ) { return '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qs_proof_admin&a=' . $act . '&o=' . $o->get_id() ), 'qs_proof_admin' ) ) . '">' . $label . '</a> '; };
			echo '<p>' . ( 'approved' !== $pk ? $ba( 'approve', 'تأیید دستی' ) : '' ) . $ba( 'resend', 'ارسال دوباره‌ی لینک' ) . '</p>';
		}
		echo '<p><label>کد رهگیری ارسال:<br><input type="text" name="qs_tracking" value="' . esc_attr( $o->get_meta( '_qs_tracking' ) ) . '" style="width:100%;direction:ltr"></label></p>';
		if ( ! qs_order_payload( $o ) ) { if ( ! $g && ! $pk ) { echo '<p>این سفارش استیکر سفارشی نداره.</p>'; } return; }
		echo '<a class="button button-primary" href="' . esc_url( qs_print_url( array( $o->get_id() ) ) ) . '">ساخت PDF چاپ</a>';
		if ( $o->get_meta( '_qs_printed' ) ) { echo '<p>قبلاً چاپ شده ✓</p>'; }
	}, $s, 'side', 'high' );
} );
